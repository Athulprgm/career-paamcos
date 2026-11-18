<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CareerPerformanceService
{
    /**
     * Guard against recursive auto-promotion cascades by tracking active agent IDs.
     *
     * @var array<int, int>
     */
    protected static array $autoPromotingAgentIds = [];

    /**
     * Determine if an agent (or any agent) is currently undergoing auto-promotion.
     */
    public static function isAutoPromoting(?int $agentId = null): bool
    {
        if ($agentId !== null) {
            return in_array($agentId, static::$autoPromotingAgentIds, true);
        }

        return ! empty(static::$autoPromotingAgentIds);
    }

    public function __construct(
        protected ?PromotionEligibilityService $eligibilityService = null
    ) {
        $this->eligibilityService = $this->eligibilityService ?: app(PromotionEligibilityService::class);
    }

    /**
     * Calculate comprehensive real-world performance metrics for an agent.
     */
    public function calculateAgentPerformance(Agent $agent, ?string $period = null): array
    {
        $tenureDays = $agent->joining_date ? (int) $agent->joining_date->diffInDays(now()) : 0;
        $tenureMonths = $agent->joining_date ? (int) $agent->joining_date->diffInMonths(now()) : 0;

        // 1. Customers
        $activeCustomers = Customer::where('agent_id', $agent->id)
            ->where('status', Customer::STATUS_ACTIVE)
            ->count();

        $totalCustomers = Customer::where('agent_id', $agent->id)->count();

        // 2. Deposits (active, approved, matured - exclude unapproved/draft/rejected)
        $depQuery = Deposit::where('agent_id', $agent->id)
            ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED]);

        if ($period) {
            $depQuery->where('start_date', 'like', "{$period}%");
        }

        $depositCount = (clone $depQuery)->count();
        $depositAmount = (float) (clone $depQuery)->sum('amount');

        // 3. Paid Collections
        $collQuery = DepositCollection::where('agent_id', $agent->id)
            ->where('status', DepositCollection::STATUS_PAID);

        if ($period) {
            $collQuery->where('collection_date', 'like', "{$period}%");
        }

        $collectionCount = (clone $collQuery)->count();
        $collectionAmount = (float) (clone $collQuery)->sum('paid_amount');

        // 4. Commission
        $commQuery = Commission::where('agent_id', $agent->id)
            ->where('status', '!=', Commission::STATUS_REJECTED);

        if ($period) {
            $commQuery->where('period', $period);
        }

        $commissionCount = (clone $commQuery)->count();
        $commissionAmount = (float) (clone $commQuery)->sum('commission_amount');

        // 5. Performance Score (Real composite formula out of 100)
        // Weighted breakdown based on real productivity metrics:
        $custScore = min(25.0, $activeCustomers * 5.0);
        $depScore = min(25.0, ($depositAmount / 10000.0) * 5.0);
        $collScore = min(30.0, ($collectionAmount / 5000.0) * 5.0);
        $tenureScore = min(20.0, ($tenureDays / 30.0) * 5.0);

        $performanceScore = round($custScore + $depScore + $collScore + $tenureScore, 2);

        return [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'period' => $period,
            'tenure_days' => $tenureDays,
            'tenure_months' => $tenureMonths,
            'active_customers' => $activeCustomers,
            'total_customers' => $totalCustomers,
            'customer_count' => $totalCustomers,
            'deposit_count' => $depositCount,
            'deposit_amount' => $depositAmount,
            'collection_count' => $collectionCount,
            'collection_amount' => $collectionAmount,
            'commission_count' => $commissionCount,
            'commission_amount' => $commissionAmount,
            'performance_score' => $performanceScore,
        ];
    }

    /**
     * Check if an agent is eligible for promotion to the target career level.
     */
    public function checkEligibility(
        Agent $agent,
        ?CareerLevel $targetLevel = null,
        ?string $period = null,
        ?int $excludeRequestId = null
    ): array {
        $currentLevel = $agent->careerLevel;

        if (! $targetLevel) {
            if ($currentLevel) {
                $targetLevel = $currentLevel->nextLevel();
            } else {
                $targetLevel = CareerLevel::ordered()->first();
            }
        }

        // 1. Dynamic Promotion Rule Check (Super Admin Configured)
        $dynamicRule = $this->eligibilityService->findApplicableRule($agent, $targetLevel);
        if ($dynamicRule) {
            return $this->eligibilityService->evaluateEligibility($agent, $dynamicRule, $targetLevel, $excludeRequestId);
        }

        // If at top level or no dynamic rule, evaluate target metrics
        if (! $targetLevel && ! $currentLevel?->nextLevel()) {
            return $this->eligibilityService->evaluateEligibility($agent, null, null, $excludeRequestId);
        }

        // 2. Fallback to Legacy Career Level Static Criteria
        $performance = $this->calculateAgentPerformance($agent, $period);

        // Edge case: Inactive agent cannot be promoted
        if (! $agent->isActive()) {
            return [
                'is_eligible' => false,
                'current_level' => $currentLevel,
                'target_level' => $targetLevel,
                'performance' => $performance,
                'criteria' => [],
                'reasons' => ['Agent account is currently inactive.'],
            ];
        }

        // Edge case: Agent is already at top rank
        if (! $targetLevel) {
            return [
                'is_eligible' => false,
                'current_level' => $currentLevel,
                'target_level' => null,
                'performance' => $performance,
                'criteria' => [],
                'reasons' => ['Agent is already at the highest configured career level.'],
            ];
        }

        // Check for existing pending request
        $pendingQuery = PromotionRequest::where('agent_id', $agent->id)
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW);
        if ($excludeRequestId) {
            $pendingQuery->where('id', '!=', $excludeRequestId);
        }
        $hasPendingRequest = $pendingQuery->exists();

        $criteria = [
            'service_days' => [
                'label' => 'Minimum Service Duration',
                'unit' => 'days',
                'required' => (int) $targetLevel->min_service_days,
                'actual' => (int) $performance['tenure_days'],
                'achieved' => (int) $performance['tenure_days'] >= (int) $targetLevel->min_service_days,
                'met' => (int) $performance['tenure_days'] >= (int) $targetLevel->min_service_days,
            ],
            'active_customers' => [
                'label' => 'Active Customer Base',
                'unit' => 'customers',
                'required' => (int) $targetLevel->min_active_customers,
                'actual' => (int) $performance['active_customers'],
                'achieved' => (int) $performance['active_customers'] >= (int) $targetLevel->min_active_customers,
                'met' => (int) $performance['active_customers'] >= (int) $targetLevel->min_active_customers,
            ],
            'deposit_count' => [
                'label' => 'Deposit Accounts Count',
                'unit' => 'deposits',
                'required' => (int) $targetLevel->min_deposit_count,
                'actual' => (int) $performance['deposit_count'],
                'achieved' => (int) $performance['deposit_count'] >= (int) $targetLevel->min_deposit_count,
                'met' => (int) $performance['deposit_count'] >= (int) $targetLevel->min_deposit_count,
            ],
            'deposit_amount' => [
                'label' => 'Deposit Business Volume',
                'unit' => '₹',
                'required' => (float) $targetLevel->min_deposit_amount,
                'actual' => (float) $performance['deposit_amount'],
                'achieved' => (float) $performance['deposit_amount'] >= (float) $targetLevel->min_deposit_amount,
                'met' => (float) $performance['deposit_amount'] >= (float) $targetLevel->min_deposit_amount,
            ],
            'collection_amount' => [
                'label' => 'Total Collection Volume',
                'unit' => '₹',
                'required' => (float) $targetLevel->min_collection_amount,
                'actual' => (float) $performance['collection_amount'],
                'achieved' => (float) $performance['collection_amount'] >= (float) $targetLevel->min_collection_amount,
                'met' => (float) $performance['collection_amount'] >= (float) $targetLevel->min_collection_amount,
            ],
            'commission_amount' => [
                'label' => 'Earned Commission Volume',
                'unit' => '₹',
                'required' => (float) $targetLevel->min_commission_amount,
                'actual' => (float) $performance['commission_amount'],
                'achieved' => (float) $performance['commission_amount'] >= (float) $targetLevel->min_commission_amount,
                'met' => (float) $performance['commission_amount'] >= (float) $targetLevel->min_commission_amount,
            ],
            'performance_score' => [
                'label' => 'Minimum Performance Score',
                'unit' => 'pts',
                'required' => (float) $targetLevel->min_performance_score,
                'actual' => (float) $performance['performance_score'],
                'achieved' => (float) $performance['performance_score'] >= (float) $targetLevel->min_performance_score,
                'met' => (float) $performance['performance_score'] >= (float) $targetLevel->min_performance_score,
            ],
        ];

        // Overall eligibility: ALL configured criteria (where required > 0) must be achieved
        $allAchieved = true;
        $unmetCriteria = [];

        foreach ($criteria as $key => $item) {
            if ($item['required'] > 0 && ! $item['achieved']) {
                $allAchieved = false;
                $unmetCriteria[] = "{$item['label']}: Required {$item['required']} {$item['unit']}, Actual: {$item['actual']} {$item['unit']}";
            }
        }

        $reasons = [];
        if ($hasPendingRequest) {
            $reasons[] = 'Agent already has an active promotion request pending review.';
        }
        if (! $allAchieved) {
            $reasons = array_merge($reasons, $unmetCriteria);
        }

        return [
            'is_eligible' => $allAchieved && ! $hasPendingRequest,
            'has_pending_request' => $hasPendingRequest,
            'current_level' => $currentLevel,
            'target_level' => $targetLevel,
            'performance' => $performance,
            'criteria' => $criteria,
            'reasons' => $reasons,
        ];
    }

    /**
     * Calculate comprehensive target management metrics for an agent,
     * including 10% RD + 90% FD composition, eligible deposits, achievement,
     * remaining amount, progress, promotion eligibility, and unmet conditions.
     */
    public function calculateTargetAchievement(
        Agent $agent,
        CareerLevel|string|null $targetLevelOrPeriod = null,
        ?string $period = null,
        ?int $excludeRequestId = null
    ): array {
        $targetLevel = null;
        if ($targetLevelOrPeriod instanceof CareerLevel) {
            $targetLevel = $targetLevelOrPeriod;
        } elseif (is_string($targetLevelOrPeriod)) {
            $period = $targetLevelOrPeriod;
        }

        // Auto-promote agent if all promotional targets are complete and no approval is required
        if (! static::isAutoPromoting($agent->id) && $agent->isActive()) {
            $this->checkAndAutoPromote($agent);
            $agent->refresh();
        }

        $eligibility = $this->eligibilityService->evaluateEligibility($agent, null, $targetLevel, $excludeRequestId);

        $currentRank = $eligibility['current_level'] ?? $agent->careerLevel;
        $nextRank = $eligibility['target_level'] ?? $currentRank?->nextLevel();
        $targetAmount = (float) ($eligibility['target_amount'] ?? 0.0);
        $hasTarget = $nextRank !== null && $targetAmount > 0;

        return [
            'has_target' => $hasTarget,
            'current_rank' => $currentRank ? [
                'id' => $currentRank->id,
                'name' => $currentRank->name,
                'code' => $currentRank->code,
                'level' => $currentRank->level ?? $currentRank->sort_order,
                'target_deposit' => (float) ($currentRank->minimum_deposit_amount ?? $currentRank->min_deposit_amount ?? 0),
            ] : null,
            'next_rank' => $nextRank ? [
                'id' => $nextRank->id,
                'name' => $nextRank->name,
                'code' => $nextRank->code,
                'level' => $nextRank->level ?? $nextRank->sort_order,
                'target_deposit' => (float) ($nextRank->minimum_deposit_amount ?? $nextRank->min_deposit_amount ?? 0),
            ] : null,
            'target_amount' => $targetAmount,
            'total_deposits' => (float) ($eligibility['total_deposits'] ?? $eligibility['gross_deposits'] ?? 0.0),
            'credited_deposits' => (float) ($eligibility['credited_deposits'] ?? $eligibility['total_deposits'] ?? 0.0),
            'eligible_deposits' => (float) ($eligibility['eligible_deposits'] ?? 0.0),
            'achievement_amount' => (float) ($eligibility['achievement_amount'] ?? 0.0),
            'remaining_amount' => (float) ($eligibility['remaining_amount'] ?? 0.0),
            'progress_percentage' => (float) ($eligibility['progress_percent'] ?? 0.0),
            'progress_percent' => (float) ($eligibility['progress_percent'] ?? 0.0),
            'rd_progress' => [
                'actual' => (float) ($eligibility['rd_actual'] ?? 0.0),
                'required' => (float) ($eligibility['rd_target'] ?? 0.0),
                'eligible' => (float) ($eligibility['rd_eligible'] ?? 0.0),
                'remaining' => (float) ($eligibility['rd_remaining'] ?? 0.0),
                'is_met' => ! empty($eligibility['rd_met']),
            ],
            'fd_progress' => [
                'actual' => (float) ($eligibility['fd_actual'] ?? 0.0),
                'capped_eligible' => (float) ($eligibility['fd_eligible'] ?? 0.0),
                'cap_amount' => (float) ($eligibility['fd_target'] ?? 0.0),
                'remaining' => (float) ($eligibility['fd_remaining'] ?? 0.0),
                'is_met' => ! empty($eligibility['fd_met']),
            ],
            'dd_progress' => [
                'actual' => (float) ($eligibility['dd_actual'] ?? 0.0),
            ],
            'recruits_progress' => [
                'actual' => (int) ($eligibility['qualifying_agents']['count'] ?? $eligibility['metrics']['facilitators']['count'] ?? 0),
                'required' => (int) ($eligibility['qualifying_agents']['required'] ?? $eligibility['metrics']['facilitators']['required'] ?? 0),
                'is_met' => ! empty($eligibility['qualifying_agents']['met'] ?? $eligibility['metrics']['facilitators']['met']),
                'target_rank_name' => $eligibility['qualifying_agents']['target_rank']?->name ?? 'Direct Agent',
            ],
            'apply_tenure_targets' => ! empty($eligibility['apply_tenure_targets']),
            'rd_percent' => (float) ($eligibility['rd_percent'] ?? 10.0),
            'fd_percent' => (float) ($eligibility['fd_percent'] ?? 90.0),
            'is_promotable' => ! empty($eligibility['is_eligible']),
            'unmet_conditions' => $eligibility['unmet_conditions'] ?? $eligibility['reasons'] ?? [],
            'eligibility' => $eligibility,
        ];
    }

    /**
     * Submit a promotion request for review.
     */
    public function submitPromotionRequest(
        Agent $agent,
        ?CareerLevel $proposedLevel = null,
        ?string $remarks = null,
        ?string $period = null,
        ?User $requester = null
    ): PromotionRequest {
        if (! $agent->isActive()) {
            throw ValidationException::withMessages([
                'agent' => 'Cannot create promotion request for an inactive agent.',
            ]);
        }

        $currentLevel = $agent->careerLevel;
        if (! $proposedLevel) {
            $proposedLevel = $currentLevel?->nextLevel();
        }

        if (! $proposedLevel) {
            throw ValidationException::withMessages([
                'proposed_level' => 'No valid higher career progression level found for this agent.',
            ]);
        }

        // Sequential promotion enforcement: never skip ranks
        $expectedNextLevel = $currentLevel ? $currentLevel->nextLevel() : CareerLevel::ordered()->first();
        if ($expectedNextLevel && (int) $proposedLevel->id !== (int) $expectedNextLevel->id) {
            throw ValidationException::withMessages([
                'proposed_level' => "Promotions must be sequential. You cannot skip career ranks. Expected next rank: {$expectedNextLevel->name}.",
            ]);
        }

        if ($currentLevel && (int) $proposedLevel->sort_order <= (int) $currentLevel->sort_order) {
            throw ValidationException::withMessages([
                'proposed_level' => 'Proposed career level must be higher than current level in sort order.',
            ]);
        }

        // Prevent duplicate concurrent requests
        $hasPending = PromotionRequest::where('agent_id', $agent->id)
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'promotion' => 'Agent already has a promotion request currently awaiting review.',
            ]);
        }

        $period = $period ?: now()->format('Y-m');
        $eligibility = $this->checkEligibility($agent, $proposedLevel, $period);

        $rule = $eligibility['rule'] ?? null;
        $requiresApproval = $rule ? (bool) $rule->requires_approval : true;

        // If a dynamic promotion rule exists and does not require approval, automatically promote upon eligibility
        if ($rule && ! $requiresApproval && ! empty($eligibility['is_eligible'])) {
            return $this->executePromotionRecord(
                agent: $agent,
                newLevel: $proposedLevel,
                rule: $rule,
                eligibility: $eligibility,
                reviewer: $requester,
                reason: $remarks ?? "Promoted to {$proposedLevel->name} upon achieving promotional targets.",
                period: $period
            );
        }

        return DB::transaction(function () use ($agent, $currentLevel, $proposedLevel, $period, $eligibility, $remarks, $requester) {
            $requestNumber = PromotionRequest::generateRequestNumber();

            $rule = $eligibility['rule'] ?? null;
            $ruleSnapshot = $eligibility['rule_snapshot'] ?? ($rule instanceof PromotionRule ? $rule->toCriteriaSnapshot() : null);

            $promotionRequest = PromotionRequest::create([
                'request_number' => $requestNumber,
                'agent_id' => $agent->id,
                'branch_id' => $agent->branch_id,
                'current_level_id' => $currentLevel?->id ?? $proposedLevel->id,
                'proposed_level_id' => $proposedLevel->id,
                'promotion_rule_id' => $rule?->id,
                'rule_snapshot' => $ruleSnapshot,
                'performance_period' => $period,
                'eligibility_result' => $eligibility,
                'status' => PromotionRequest::STATUS_PENDING_REVIEW,
                'remarks' => $remarks,
                'requested_by' => $requester?->id ?? auth()->id(),
                'request_date' => now()->toDateString(),
            ]);

            AuditLog::record(
                event: 'promotion_request_submitted',
                description: "Submitted promotion request [{$promotionRequest->request_number}] for Agent [{$agent->name}] to {$proposedLevel->name}.",
                auditable: $promotionRequest,
                newValues: [
                    'request_number' => $promotionRequest->request_number,
                    'agent_id' => $agent->id,
                    'current_level' => $currentLevel?->name ?? 'None',
                    'proposed_level' => $proposedLevel->name,
                    'promotion_rule_id' => $rule?->id,
                    'status' => PromotionRequest::STATUS_PENDING_REVIEW,
                ]
            );

            return $promotionRequest;
        });
    }

    /**
     * Check if an agent has completed all promotional targets, and if so,
     * promote them automatically without requiring manual approval.
     */
    public function checkAndAutoPromote(Agent $agent, ?User $triggerUser = null, ?string $reason = null): ?PromotionRequest
    {
        if (in_array($agent->id, static::$autoPromotingAgentIds, true)) {
            return null;
        }

        static::$autoPromotingAgentIds[] = $agent->id;

        try {
            $agent = $agent->fresh();
            if (! $agent) {
                return null;
            }
            if (! $agent->isActive()) {
                return null;
            }

            $latestPromotion = null;
            $promotedAny = false;
            $depth = 0;

            while ($depth < 10) {
                $depth++;
                $currentLevel = $agent->careerLevel;
                if (! $currentLevel) {
                    break;
                }

                $rule = $this->eligibilityService->findApplicableRule($agent);
                $targetLevel = $rule?->toRank ?? $currentLevel->nextLevel();

                if (! $targetLevel) {
                    break; // Max career level reached
                }

                // If rule explicitly requires approval, stop auto-promotion
                if ($rule && $rule->requires_approval) {
                    break;
                }

                // Check eligibility excluding existing pending request block
                $eligibility = $this->eligibilityService->evaluateEligibility(
                    agent: $agent,
                    rule: $rule,
                    targetLevel: $targetLevel,
                    excludeRequestId: -1
                );

                // Verify that all criteria are met
                $allCriteriaMet = true;
                if (! empty($eligibility['criteria'])) {
                    foreach ($eligibility['criteria'] as $crit) {
                        if (empty($crit['met'])) {
                            $allCriteriaMet = false;
                            break;
                        }
                    }
                } else {
                    $allCriteriaMet = false;
                }

                if (! $allCriteriaMet) {
                    break; // Targets not complete
                }

                // Look for existing pending request to transition, or create new
                $existingPending = PromotionRequest::where('agent_id', $agent->id)
                    ->where('proposed_level_id', $targetLevel->id)
                    ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
                    ->first();

                $autoReason = $reason ?? "Automatic promotion: Completed 100% of promotional targets for {$targetLevel->name}.";

                $latestPromotion = $this->executePromotionRecord(
                    agent: $agent,
                    newLevel: $targetLevel,
                    rule: $rule,
                    eligibility: $eligibility,
                    reviewer: $triggerUser,
                    reason: $autoReason,
                    period: now()->format('Y-m'),
                    existingRequest: $existingPending
                );

                $promotedAny = true;
                $agent->refresh();
            }

            // When an agent advances, check upline parent agents whose qualifying facilitator counts may now be satisfied
            if ($promotedAny) {
                $parent = $agent->parentAgent;
                $visited = [];
                while ($parent && ! in_array($parent->id, $visited, true)) {
                    $visited[] = $parent->id;
                    $this->checkAndAutoPromote($parent, $triggerUser, 'Automatic promotion: Downline agent advancement satisfied team targets.');
                    $parent = $parent->parentAgent;
                }
            }

            return $latestPromotion;
        } finally {
            static::$autoPromotingAgentIds = array_values(array_filter(
                static::$autoPromotingAgentIds,
                fn (int $id) => $id !== $agent->id
            ));
        }
    }

    /**
     * Atomically execute agent rank elevation and create permanent career history.
     */
    public function executePromotionRecord(
        Agent $agent,
        CareerLevel $newLevel,
        ?PromotionRule $rule,
        array $eligibility,
        ?User $reviewer = null,
        ?string $reason = null,
        ?string $period = null,
        ?PromotionRequest $existingRequest = null
    ): PromotionRequest {
        return DB::transaction(function () use ($agent, $newLevel, $rule, $eligibility, $reviewer, $reason, $period, $existingRequest) {
            /** @var Agent $lockedAgent */
            $lockedAgent = Agent::where('id', $agent->id)->lockForUpdate()->firstOrFail();
            $oldLevelId = $lockedAgent->rank_id;
            $period = $period ?: now()->format('Y-m');

            $authorizerId = $reviewer?->id
                ?? auth()->id()
                ?? User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->value('id')
                ?? $lockedAgent->user_id
                ?? 1;

            $promotionReason = $reason ?? "Promoted to {$newLevel->name} upon achieving promotional targets.";
            $ruleSnapshot = $eligibility['rule_snapshot'] ?? ($rule instanceof PromotionRule ? $rule->toCriteriaSnapshot() : null);

            if ($existingRequest) {
                /** @var PromotionRequest $promotionRequest */
                $promotionRequest = PromotionRequest::where('id', $existingRequest->id)->lockForUpdate()->firstOrFail();
                $promotionRequest->update([
                    'status' => PromotionRequest::STATUS_PROMOTED,
                    'reviewed_by' => $authorizerId,
                    'reviewed_at' => now(),
                    'review_reason' => $promotionReason,
                    'promoted_at' => now(),
                    'eligibility_result' => $eligibility,
                ]);
            } else {
                $requestNumber = PromotionRequest::generateRequestNumber();
                $promotionRequest = PromotionRequest::create([
                    'request_number' => $requestNumber,
                    'agent_id' => $lockedAgent->id,
                    'branch_id' => $lockedAgent->branch_id,
                    'current_level_id' => $oldLevelId,
                    'proposed_level_id' => $newLevel->id,
                    'promotion_rule_id' => $rule?->id,
                    'rule_snapshot' => $ruleSnapshot,
                    'performance_period' => $period,
                    'eligibility_result' => $eligibility,
                    'status' => PromotionRequest::STATUS_PROMOTED,
                    'remarks' => $promotionReason,
                    'requested_by' => $authorizerId,
                    'request_date' => now()->toDateString(),
                    'reviewed_by' => $authorizerId,
                    'reviewed_at' => now(),
                    'review_reason' => $promotionReason,
                    'promoted_at' => now(),
                ]);
            }

            // 1. Update Agent's Rank
            $lockedAgent->update([
                'rank_id' => $newLevel->id,
            ]);

            // 2. Create permanent Career History record
            CareerHistory::create([
                'agent_id' => $lockedAgent->id,
                'promotion_request_id' => $promotionRequest->id,
                'previous_level_id' => $oldLevelId,
                'new_level_id' => $newLevel->id,
                'performance_period' => $period,
                'promotion_date' => now()->toDateString(),
                'approved_by' => $authorizerId,
                'action' => CareerHistory::ACTION_PROMOTION,
                'remarks' => $promotionReason,
            ]);

            // 3. Audit Log
            AuditLog::record(
                event: 'agent_promoted',
                description: "Promoted Agent [{$lockedAgent->name}] to {$newLevel->name} ({$newLevel->code}). Reason: {$promotionReason}",
                auditable: $promotionRequest,
                oldValues: ['rank_id' => $oldLevelId],
                newValues: [
                    'rank_id' => $newLevel->id,
                    'status' => PromotionRequest::STATUS_PROMOTED,
                    'promoted_at' => now()->toDateTimeString(),
                ]
            );

            return $promotionRequest;
        });
    }

    /**
     * Approve and execute promotion: transitions status to PROMOTED, updates agent's rank, and creates CareerHistory.
     */
    public function approveAndPromote(PromotionRequest $request, User $reviewer, ?string $reason = null): PromotionRequest
    {
        // Rule 1: Agents cannot approve their own promotion!
        if ($request->agent->user_id && (int) $request->agent->user_id === (int) $reviewer->id) {
            throw ValidationException::withMessages([
                'authorization' => 'Unauthorized action: Agents are strictly prohibited from approving their own promotion.',
            ]);
        }

        // Rule 2: Verify rule-specific authorized approval role
        $rule = $request->rule;
        if ($rule && $rule->authorized_approval_role === PromotionRule::ROLE_SUPER_ADMIN && ! $reviewer->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'authorization' => 'Unauthorized action: This promotion rule strictly requires Super Admin approval.',
            ]);
        }

        if (! $request->canBePromoted()) {
            throw ValidationException::withMessages([
                'status' => "Invalid promotion state transition. Current status: '{$request->status}'.",
            ]);
        }

        // Rule 3: Sequential promotion check (cannot skip ranks)
        $expectedNext = $request->agent->careerLevel ? $request->agent->careerLevel->nextLevel() : CareerLevel::ordered()->first();
        if ($expectedNext && (int) $request->proposed_level_id !== (int) $expectedNext->id) {
            throw ValidationException::withMessages([
                'proposed_level' => "Promotions must be sequential. You cannot skip career ranks. Expected next rank: {$expectedNext->name}.",
            ]);
        }

        // Rule 4: Recheck eligibility at time of approval
        $recheck = $this->checkEligibility($request->agent, $request->proposedLevel, $request->performance_period, $request->id);
        if (! $recheck['is_eligible']) {
            $reasonList = implode(', ', $recheck['reasons'] ?? ['Requirements no longer satisfied']);
            throw ValidationException::withMessages([
                'eligibility' => "Agent no longer satisfies the promotion eligibility requirements: {$reasonList}",
            ]);
        }

        $promotedRequest = $this->executePromotionRecord(
            agent: $request->agent,
            newLevel: $request->proposedLevel,
            rule: $request->rule,
            eligibility: $recheck,
            reviewer: $reviewer,
            reason: $reason ?? "Promoted to {$request->proposedLevel->name} via request [{$request->request_number}].",
            period: $request->performance_period,
            existingRequest: $request
        );

        // When an agent advances, check upline parent agents
        $parent = $request->agent->parentAgent;
        $visited = [];
        while ($parent && ! in_array($parent->id, $visited, true)) {
            $visited[] = $parent->id;
            $this->checkAndAutoPromote($parent, $reviewer, 'Automatic promotion: Downline agent advancement satisfied team targets.');
            $parent = $parent->parentAgent;
        }

        return $promotedRequest;
    }

    /**
     * Reject a promotion request with mandatory reason.
     */
    public function rejectPromotion(PromotionRequest $request, string $reason, User $reviewer): PromotionRequest
    {
        if ($request->agent->user_id && (int) $request->agent->user_id === (int) $reviewer->id) {
            throw ValidationException::withMessages([
                'authorization' => 'Unauthorized action: Agents cannot review their own promotion requests.',
            ]);
        }

        if (! $request->canBeRejected()) {
            throw ValidationException::withMessages([
                'status' => "Only requests in PENDING_REVIEW can be rejected. Current status: '{$request->status}'.",
            ]);
        }

        return DB::transaction(function () use ($request, $reviewer, $reason) {
            /** @var PromotionRequest $locked */
            $locked = PromotionRequest::where('id', $request->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => PromotionRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            AuditLog::record(
                event: 'promotion_rejected',
                description: "Rejected promotion request [{$locked->request_number}] for Agent [{$locked->agent->name}] by {$reviewer->name}. Reason: {$reason}",
                auditable: $locked,
                oldValues: ['status' => PromotionRequest::STATUS_PENDING_REVIEW],
                newValues: [
                    'status' => PromotionRequest::STATUS_REJECTED,
                    'rejection_reason' => $reason,
                ]
            );

            return $locked;
        });
    }
}
