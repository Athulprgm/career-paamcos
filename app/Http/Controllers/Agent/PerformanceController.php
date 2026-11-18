<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\CareerLevel;
use App\Models\PromotionRequest;
use App\Services\CareerPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PerformanceController extends Controller
{
    public function __construct(
        protected CareerPerformanceService $careerService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $agent = $user->agentProfile;

        if (! $agent) {
            abort(403, 'No agent profile linked.');
        }

        $period = $request->input('period', now()->format('Y-m'));

        // Automatically promote agent if all promotional targets are complete
        $this->careerService->checkAndAutoPromote($agent);
        $agent->refresh();

        // Calculate performance and criteria eligibility using existing service
        $performance = $this->careerService->calculateAgentPerformance($agent, $period);
        $eligibility = $this->careerService->checkEligibility($agent, null, $period);
        $targetAchievement = $this->careerService->calculateTargetAchievement($agent, $period);

        $careerLevels = CareerLevel::active()->ordered()->get();
        $promotionRequests = $agent->promotionRequests()->with(['proposedLevel', 'reviewer'])->latest()->get();
        $careerHistories = $agent->careerHistories()->with(['previousLevel', 'newLevel', 'approver'])->get();
        $pendingRequest = $agent->promotionRequests()->where('status', PromotionRequest::STATUS_PENDING_REVIEW)->first();

        return view('agent.performance', compact(
            'agent',
            'period',
            'performance',
            'eligibility',
            'targetAchievement',
            'careerLevels',
            'promotionRequests',
            'careerHistories',
            'pendingRequest'
        ));
    }

    /**
     * Submit an agent promotion request after validating eligibility.
     */
    public function requestPromotion(Request $request)
    {
        $user = auth()->user();
        $agent = $user->agentProfile;

        if (! $agent) {
            abort(403, 'No agent profile linked.');
        }

        if (! $agent->isActive()) {
            return redirect()->back()->with('error', 'Only active agents are eligible to submit promotion requests.');
        }

        // Prevent duplicate concurrent requests
        $hasPending = $agent->promotionRequests()
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
            ->exists();

        if ($hasPending) {
            return redirect()->back()->with('error', 'You already have an active promotion request awaiting review.');
        }

        $period = now()->format('Y-m');
        $eligibility = $this->careerService->checkEligibility($agent, null, $period);

        if (! $eligibility['is_eligible']) {
            $reasons = implode(' ', $eligibility['reasons'] ?? ['Eligibility criteria not yet achieved.']);

            return redirect()->back()->with('error', "You are not currently eligible for promotion: {$reasons}");
        }

        $targetLevel = $eligibility['target_level'];
        if (! $targetLevel) {
            return redirect()->back()->with('error', 'You are already at the highest available career rank.');
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $promotionRequest = $this->careerService->submitPromotionRequest(
                agent: $agent,
                proposedLevel: $targetLevel,
                remarks: $validated['remarks'] ?? 'Promotion request submitted by agent.',
                period: $period,
                requester: $user
            );

            if ($promotionRequest->isPromoted()) {
                $agent->refresh();

                return redirect()->back()->with('success', "Congratulations! You have completed all promotional targets and have been automatically promoted to {$targetLevel->name}!");
            }

            return redirect()->back()->with('success', "Promotion request [{$promotionRequest->request_number}] for advancement to {$targetLevel->name} has been submitted successfully for branch review.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display permanent promotion and rank elevation history for the agent.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $agent = $user->agentProfile;

        if (! $agent) {
            abort(403, 'No agent profile linked.');
        }

        $histories = $agent->careerHistories()
            ->with(['previousLevel', 'newLevel', 'approver', 'promotionRequest'])
            ->paginate(15);

        $promotionRequests = $agent->promotionRequests()
            ->with(['proposedLevel', 'reviewer'])
            ->latest()
            ->get();

        return view('agent.promotion-history', compact('agent', 'histories', 'promotionRequests'));
    }
}
