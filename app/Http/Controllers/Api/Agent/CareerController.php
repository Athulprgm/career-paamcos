<?php

namespace App\Http\Controllers\Api\Agent;

use App\Models\AgentRank;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\PromotionRequest;
use App\Services\CareerPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareerController extends BaseAgentApiController
{
    public function __construct(
        protected CareerPerformanceService $careerService
    ) {}

    /**
     * Career progression evaluation (composite score, targets, and criteria).
     */
    public function evaluation(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();
        $period = $request->input('period', now()->format('Y-m'));

        $performance = $this->careerService->calculateAgentPerformance($agent, $period);
        $achievement = [];
        try {
            $achievement = $this->careerService->calculateTargetAchievement($agent, null, $period);
        } catch (\Throwable) {
        }

        // RD vs FD breakdown for deposit composition rule
        $rdDeposits = (float) Deposit::where('agent_id', $agent->id)
            ->whereHas('scheme', fn ($q) => $q->where('scheme_type', 'RD'))
            ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED])
            ->sum('amount');

        $fdDeposits = (float) Deposit::where('agent_id', $agent->id)
            ->whereHas('scheme', fn ($q) => $q->where('scheme_type', 'FD'))
            ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED])
            ->sum('amount');

        $grossDeposits = (float) ($achievement['total_deposits'] ?? ($rdDeposits + $fdDeposits));
        $targetAmount = (float) ($achievement['target_amount'] ?? 0.0);

        // Current & Next Rank
        $currentRank = [
            'id' => (int) ($agent->rank?->id ?? 1),
            'name' => (string) ($agent->rank?->name ?? 'Advisor'),
            'code' => (string) ($agent->rank?->code ?? 'ADV'),
            'level' => (int) ($agent->rank?->level ?? $agent->rank?->sort_order ?? 1),
        ];

        $nextRankModel = isset($achievement['next_rank']['id'])
            ? AgentRank::find($achievement['next_rank']['id'])
            : AgentRank::where('level', '>', $currentRank['level'])->orderBy('level')->first();

        $nextRank = $nextRankModel ? [
            'id' => (int) $nextRankModel->id,
            'name' => (string) $nextRankModel->name,
            'code' => (string) ($nextRankModel->code ?? 'RNK'.$nextRankModel->id),
            'level' => (int) ($nextRankModel->level ?? $nextRankModel->sort_order ?? ($currentRank['level'] + 1)),
        ] : null;

        // Pending promotion check
        $hasPendingRequest = PromotionRequest::where('agent_id', $agent->id)
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
            ->exists();

        $progressPercentage = (float) ($achievement['progress_percent'] ?? 0.0);
        $compositeScore = (float) ($achievement['composite_score'] ?? $progressPercentage);

        $eligibilityBadge = 'IN PROGRESS';
        if ($hasPendingRequest) {
            $eligibilityBadge = 'UNDER REVIEW';
        } elseif (($achievement['is_eligible'] ?? false) || $progressPercentage >= 100) {
            $eligibilityBadge = 'ELIGIBLE FOR PROMOTION';
        }

        // Eligibility criteria metrics
        $reqTeamDeposit = (float) ($achievement['criteria']['team_deposit']['target'] ?? ($targetAmount * 0.5));
        $actTeamDeposit = (float) ($achievement['criteria']['team_deposit']['actual'] ?? ($grossDeposits * 0.4));
        $reqIndivDeposit = (float) ($achievement['criteria']['individual_deposit']['target'] ?? $targetAmount);
        $actIndivDeposit = (float) ($achievement['criteria']['individual_deposit']['actual'] ?? $grossDeposits);

        return response()->json([
            'period' => (string) $period,
            'current_rank' => $currentRank,
            'next_rank' => $nextRank,
            'composite_score' => (float) $compositeScore,
            'eligibility_badge' => $eligibilityBadge,
            'has_pending_promotion_request' => (bool) $hasPendingRequest,
            'target_achievement' => [
                'target_amount' => $targetAmount,
                'gross_deposits' => $grossDeposits,
                'raw_rd_amount' => $rdDeposits,
                'raw_fd_amount' => $fdDeposits,
            ],
            'performance_metrics' => [
                'tenure_months' => (int) ($performance['tenure_months'] ?? 0),
                'active_customers' => (int) ($performance['active_customers'] ?? 0),
                'total_customers' => (int) ($performance['total_customers'] ?? 0),
                'total_deposits_count' => (int) ($performance['deposit_count'] ?? 0),
                'total_deposits_amount' => (float) ($performance['deposit_amount'] ?? 0.0),
                'paid_collections_amount' => (float) ($performance['collection_amount'] ?? 0.0),
                'total_commissions_earned' => (float) ($performance['commission_amount'] ?? 0.0),
            ],
            'eligibility_criteria' => [
                'required_team_deposit' => $reqTeamDeposit,
                'actual_team_deposit' => $actTeamDeposit,
                'is_team_deposit_met' => $actTeamDeposit >= $reqTeamDeposit && $reqTeamDeposit > 0,
                'required_individual_deposit' => $reqIndivDeposit,
                'actual_individual_deposit' => $actIndivDeposit,
                'is_individual_deposit_met' => $actIndivDeposit >= $reqIndivDeposit && $reqIndivDeposit > 0,
                'required_qualifying_facilitators' => (int) ($achievement['criteria']['facilitators']['target'] ?? 2),
                'actual_qualifying_facilitators' => (int) ($achievement['criteria']['facilitators']['actual'] ?? 1),
                'is_facilitators_met' => (bool) ($achievement['criteria']['facilitators']['met'] ?? false),
            ],
            'requested_at' => $hasPendingRequest ? now()->toISOString() : null,
            'updated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Career rank change history and promotion requests history.
     */
    public function history(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $careerHistories = CareerHistory::where('agent_id', $agent->id)
            ->latest('promotion_date')
            ->get()
            ->map(function ($h) {
                $level = CareerLevel::find($h->new_level_id);

                return [
                    'id' => (int) $h->id,
                    'period' => (string) ($h->performance_period ?? now()->format('Y-m')),
                    'rank_id' => $h->new_level_id ? (int) $h->new_level_id : null,
                    'rank_name' => (string) ($level?->name ?? 'Rank Change'),
                    'rank_level' => (int) ($level?->level ?? $level?->sort_order ?? 1),
                    'changed_at' => $h->promotion_date ? $h->promotion_date->toISOString() : $h->created_at?->toISOString(),
                    'remarks' => $h->remarks ?? null,
                    'type' => (string) ($h->action ?? 'PROMOTION'),
                ];
            })
            ->values()
            ->all();

        $requests = PromotionRequest::where('agent_id', $agent->id)
            ->with(['proposedLevel', 'currentLevel'])
            ->latest()
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'period' => (string) ($r->performance_period ?? now()->format('Y-m')),
                    'requested_rank_id' => $r->proposed_level_id ? (int) $r->proposed_level_id : null,
                    'requested_rank_name' => $r->proposedLevel?->name ?? null,
                    'requested_rank_level' => $r->proposedLevel ? (int) ($r->proposedLevel->level ?? $r->proposedLevel->sort_order ?? 1) : null,
                    'status' => (string) ($r->status ?? 'PENDING_REVIEW'),
                    'remarks' => $r->remarks ?? null,
                    'review_remarks' => $r->review_reason ?? $r->rejection_reason ?? null,
                    'reviewed_at' => $r->reviewed_at ? $r->reviewed_at->toISOString() : null,
                    'created_at' => $r->created_at ? $r->created_at->toISOString() : null,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'career_history' => $careerHistories,
            'promotion_requests' => $requests,
        ]);
    }

    /**
     * Submit a promotion request.
     */
    public function storePromotionRequest(Request $request): JsonResponse
    {
        $agent = $this->getAuthenticatedAgent();

        $validated = $request->validate([
            'proposed_level_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $proposedLevel = ! empty($validated['proposed_level_id'])
            ? CareerLevel::find($validated['proposed_level_id'])
            : null;

        $promotionRequest = $this->careerService->submitPromotionRequest(
            agent: $agent,
            proposedLevel: $proposedLevel,
            remarks: $validated['remarks'] ?? 'Promotion request submitted via mobile app.',
            period: now()->format('Y-m'),
            requester: auth()->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Promotion request submitted successfully.',
            'request_id' => $promotionRequest->id,
        ], 201);
    }
}
