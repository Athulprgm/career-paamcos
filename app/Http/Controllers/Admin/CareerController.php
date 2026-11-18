<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\PromotionRequest;
use App\Services\CareerPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CareerController extends Controller
{
    public function __construct(
        protected CareerPerformanceService $careerService
    ) {}

    protected function getRestrictedBranchId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->branch_id ?? $user->staffProfile?->branch_id ?? $user->agentProfile?->branch_id ?? null;
    }

    protected function getRestrictedAgentId(): ?int
    {
        $user = auth()->user();
        if ($user->isAgent() && ! $user->isAdmin()) {
            $agent = Agent::where('user_id', $user->id)->first();

            return $agent?->id ?? -1;
        }

        return null;
    }

    protected function authorizeAgentAccess(Agent $agent): void
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId !== null && $agent->branch_id !== null && (int) $agent->branch_id !== (int) $restrictedBranchId) {
            abort(403, 'Unauthorized access: Cross-branch career data access is strictly prohibited.');
        }

        $restrictedAgentId = $this->getRestrictedAgentId();
        if ($restrictedAgentId !== null && (int) $agent->id !== (int) $restrictedAgentId) {
            abort(403, 'Unauthorized access: You are only permitted to view your own performance records.');
        }
    }

    protected function authorizeRequestAccess(PromotionRequest $promotionRequest): void
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId !== null && (int) $promotionRequest->branch_id !== (int) $restrictedBranchId) {
            abort(403, 'Unauthorized access: Cross-branch promotion access is strictly prohibited.');
        }

        $restrictedAgentId = $this->getRestrictedAgentId();
        if ($restrictedAgentId !== null && (int) $promotionRequest->agent_id !== (int) $restrictedAgentId) {
            abort(403, 'Unauthorized access: You are only permitted to view your own promotion requests.');
        }
    }

    /**
     * Career Dashboard Overview with real statistics.
     */
    public function overview(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $agentQuery = Agent::query();
        if ($restrictedBranchId !== null) {
            $agentQuery->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $agentQuery->where('branch_id', $request->branch_id);
        }

        if ($restrictedAgentId !== null) {
            $agentQuery->where('id', $restrictedAgentId);
        } elseif ($request->filled('agent_id')) {
            $agentQuery->where('id', $request->agent_id);
        }

        $totalAgents = (clone $agentQuery)->count();
        $activeAgents = (clone $agentQuery)->active()->count();
        $careerLevelsCount = CareerLevel::active()->count();

        // Promotion requests count
        $requestQuery = PromotionRequest::query();
        if ($restrictedBranchId !== null) {
            $requestQuery->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $requestQuery->where('branch_id', $request->branch_id);
        }

        if ($restrictedAgentId !== null) {
            $requestQuery->where('agent_id', $restrictedAgentId);
        }

        $pendingPromotionsCount = (clone $requestQuery)->where('status', PromotionRequest::STATUS_PENDING_REVIEW)->count();
        $approvedPromotionsCount = (clone $requestQuery)->whereIn('status', [PromotionRequest::STATUS_APPROVED, PromotionRequest::STATUS_PROMOTED])->count();

        // Recent promotions
        $recentPromotions = (clone $requestQuery)->with(['agent', 'currentLevel', 'proposedLevel', 'requester', 'reviewer'])
            ->latest()
            ->limit(5)
            ->get();

        // Eligible agents calculation across agents in scope (sample/subset for dashboard performance)
        $agentsInScope = (clone $agentQuery)->active()->with(['careerLevel'])->limit(50)->get();
        $eligibleAgentsCount = 0;
        foreach ($agentsInScope as $ag) {
            $res = $this->careerService->checkEligibility($ag);
            if ($res['is_eligible']) {
                $eligibleAgentsCount++;
            }
        }

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $careerLevels = CareerLevel::active()->ordered()->get();

        return view('admin.career.overview', compact(
            'totalAgents',
            'activeAgents',
            'careerLevelsCount',
            'pendingPromotionsCount',
            'approvedPromotionsCount',
            'eligibleAgentsCount',
            'recentPromotions',
            'branches',
            'careerLevels',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * Agent Performance Matrix.
     */
    public function performance(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = Agent::with(['branch', 'careerLevel']);

        if ($restrictedBranchId !== null) {
            $query->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($restrictedAgentId !== null) {
            $query->where('id', $restrictedAgentId);
        } elseif ($request->filled('agent_id')) {
            $query->where('id', $request->agent_id);
        }

        if ($request->filled('level_id')) {
            $query->where('rank_id', $request->level_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $period = $request->input('period', now()->format('Y-m'));
        $agents = $query->orderBy('name')->paginate(15)->withQueryString();

        // Calculate performance for each agent on page
        $performances = [];
        foreach ($agents as $agent) {
            $perf = $this->careerService->calculateAgentPerformance($agent, $period);
            $elig = $this->careerService->checkEligibility($agent, null, $period);
            $targetAchievement = $this->careerService->calculateTargetAchievement($agent, $period);
            $performances[$agent->id] = [
                'metrics' => $perf,
                'eligibility' => $elig,
                'target_achievement' => $targetAchievement,
            ];
        }

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $careerLevels = CareerLevel::active()->ordered()->get();

        return view('admin.career.performance', compact(
            'agents',
            'performances',
            'branches',
            'careerLevels',
            'period',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * Deep-dive single agent performance scorecard and criteria checklist.
     */
    public function agentPerformance(Agent $agent, Request $request)
    {
        $this->authorizeAgentAccess($agent);

        // Automatically promote agent if all promotional targets are complete
        $this->careerService->checkAndAutoPromote($agent);
        $agent->refresh();

        $period = $request->input('period', now()->format('Y-m'));
        $performance = $this->careerService->calculateAgentPerformance($agent, $period);
        $eligibility = $this->careerService->checkEligibility($agent, null, $period);
        $targetAchievement = $this->careerService->calculateTargetAchievement($agent, $period);

        $careerLevels = CareerLevel::active()->ordered()->get();
        $promotionRequests = $agent->promotionRequests()->with(['proposedLevel', 'reviewer'])->latest()->get();
        $careerHistories = $agent->careerHistories()->with(['previousLevel', 'newLevel', 'approver'])->get();

        return view('admin.career.agent-performance', compact(
            'agent',
            'period',
            'performance',
            'eligibility',
            'targetAchievement',
            'careerLevels',
            'promotionRequests',
            'careerHistories'
        ));
    }

    /**
     * List of promotion requests.
     */
    public function promotionRequests(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = PromotionRequest::with(['agent.branch', 'currentLevel', 'proposedLevel', 'requester', 'reviewer']);

        if ($restrictedBranchId !== null) {
            $query->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($restrictedAgentId !== null) {
            $query->where('agent_id', $restrictedAgentId);
        } elseif ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        return view('admin.career.requests.index', compact(
            'requests',
            'branches',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * Approved / Promoted records list.
     */
    public function approvedPromotions(Request $request)
    {
        $request->merge(['status' => PromotionRequest::STATUS_PROMOTED]);

        return $this->promotionRequests($request);
    }

    /**
     * Promotion Request Detail page.
     */
    public function showRequest(PromotionRequest $promotionRequest)
    {
        $this->authorizeRequestAccess($promotionRequest);

        $promotionRequest->load(['agent.branch', 'currentLevel', 'proposedLevel', 'requester', 'reviewer', 'careerHistory']);

        return view('admin.career.requests.show', compact('promotionRequest'));
    }

    /**
     * Form to create a promotion request for an agent.
     */
    public function createRequest(Agent $agent)
    {
        $this->authorizeAgentAccess($agent);

        $period = now()->format('Y-m');
        $eligibility = $this->careerService->checkEligibility($agent, null, $period);
        $nextLevel = $eligibility['target_level'];

        return view('admin.career.requests.create', compact('agent', 'eligibility', 'nextLevel', 'period'));
    }

    /**
     * Store new promotion request.
     */
    public function storeRequest(Request $request, Agent $agent)
    {
        $this->authorizeAgentAccess($agent);

        $validated = $request->validate([
            'proposed_level_id' => ['nullable', 'exists:ranks,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'period' => ['nullable', 'string', 'max:20'],
        ]);

        $proposedLevel = ! empty($validated['proposed_level_id'])
            ? CareerLevel::findOrFail($validated['proposed_level_id'])
            : null;

        try {
            $promotionRequest = $this->careerService->submitPromotionRequest(
                agent: $agent,
                proposedLevel: $proposedLevel,
                remarks: $validated['remarks'] ?? null,
                period: $validated['period'] ?? null,
                requester: auth()->user()
            );

            if ($promotionRequest->isPromoted()) {
                $agent->refresh();

                return redirect()->route('admin.career.requests.show', $promotionRequest)
                    ->with('success', "Agent {$agent->name} has completed all promotional targets and has been automatically promoted to {$promotionRequest->proposedLevel->name}!");
            }

            return redirect()->route('admin.career.requests.show', $promotionRequest)
                ->with('success', "Promotion request [{$promotionRequest->request_number}] submitted successfully for {$agent->name}.");
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve and execute promotion.
     */
    public function approve(PromotionRequest $promotionRequest, Request $request)
    {
        $this->authorizeRequestAccess($promotionRequest);

        // Only administrators can approve promotions!
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action: Only administrators can approve promotion requests.');
        }

        // Self-approval prohibition
        if ($promotionRequest->agent?->user_id && (int) $promotionRequest->agent->user_id === (int) auth()->id()) {
            abort(403, 'Self-approval is prohibited: You cannot approve your own promotion request.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->careerService->approveAndPromote($promotionRequest, auth()->user(), $validated['reason'] ?? null);

            return redirect()->route('admin.career.requests.show', $promotionRequest)
                ->with('success', "Promotion request [{$promotionRequest->request_number}] has been approved! {$promotionRequest->agent->name} is now promoted to {$promotionRequest->proposedLevel->name}.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject promotion request.
     */
    public function reject(PromotionRequest $promotionRequest, Request $request)
    {
        $this->authorizeRequestAccess($promotionRequest);

        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action: Only administrators can review promotion requests.');
        }

        if ($promotionRequest->agent?->user_id && (int) $promotionRequest->agent->user_id === (int) auth()->id()) {
            abort(403, 'Self-action is prohibited: You cannot review your own promotion request.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->careerService->rejectPromotion($promotionRequest, $validated['rejection_reason'], auth()->user());

            return redirect()->route('admin.career.requests.show', $promotionRequest)
                ->with('success', "Promotion request [{$promotionRequest->request_number}] has been rejected.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Permanent Career History Log.
     */
    public function history(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = CareerHistory::with(['agent.branch', 'previousLevel', 'newLevel', 'approver', 'promotionRequest']);

        if ($restrictedBranchId !== null) {
            $query->byBranch($restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $query->byBranch($request->branch_id);
        }

        if ($restrictedAgentId !== null) {
            $query->byAgent($restrictedAgentId);
        } elseif ($request->filled('agent_id')) {
            $query->byAgent($request->agent_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $histories = $query->latest('promotion_date')->latest('id')->paginate(15)->withQueryString();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        return view('admin.career.history', compact(
            'histories',
            'branches',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }
}
