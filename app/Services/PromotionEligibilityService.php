<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\FinancialYear;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PromotionEligibilityService
{
    /**
     * Find the best matching active and effective promotion rule for an agent.
     */
    public function findApplicableRule(Agent $agent, ?CareerLevel $targetLevel = null, ?string $date = null): ?PromotionRule
    {
        $currentRankId = $agent->rank_id;
        if (! $currentRankId) {
            return null;
        }

        $query = PromotionRule::active()
            ->effective($date)
            ->where('from_rank_id', $currentRankId);

        // If target rank specified, match it using FY priority
        if ($targetLevel) {
            $rule = $this->resolvePrioritizedRule($query, $targetLevel->id);
            if ($rule) {
                return $rule;
            }
        }

        // Otherwise find rule for next level in career sort order
        $nextLevel = $agent->careerLevel?->nextLevel();
        if ($nextLevel) {
            $rule = $this->resolvePrioritizedRule($query, $nextLevel->id);
            if ($rule) {
                return $rule;
            }
        }

        // Fallback: any active effective rule for this rank
        return $this->resolvePrioritizedRule($query);
    }

    /**
     * Resolve the highest-priority active rule from the query, preferring current FY,
     * then global rules, then non-closed FY rules before any remaining fallback.
     */
    protected function resolvePrioritizedRule(Builder $baseQuery, ?int $toRankId = null): ?PromotionRule
    {
        $scoped = $toRankId !== null
            ? (clone $baseQuery)->where('to_rank_id', $toRankId)
            : (clone $baseQuery);

        $currentFyRule = (clone $scoped)
            ->whereHas('financialYear', fn (Builder $q) => $q
                ->where('is_current', true)
                ->where(fn (Builder $sq) => $sq->whereNull('status')->orWhere('status', '!=', FinancialYear::STATUS_CLOSED)))
            ->first();

        if ($currentFyRule) {
            return $currentFyRule;
        }

        $globalRule = (clone $scoped)
            ->whereNull('financial_year_id')
            ->first();

        if ($globalRule) {
            return $globalRule;
        }

        $openFyRule = (clone $scoped)
            ->whereDoesntHave('financialYear', fn (Builder $q) => $q->where('status', FinancialYear::STATUS_CLOSED))
            ->first();

        if ($openFyRule) {
            return $openFyRule;
        }

        return $scoped->first();
    }

    /**
     * Retrieve all downline agent IDs under an agent (multi-tier recursion with loop protection).
     *
     * @return array<int>
     */
    public function getDownlineAgentIds(Agent $agent, int $maxDepth = 10): array
    {
        $downlineIds = [];
        $currentLevelIds = [$agent->id];
        $visitedIds = [$agent->id => true];
        $depth = 0;

        while (! empty($currentLevelIds) && $depth < $maxDepth) {
            $nextLevelIds = Agent::whereIn('parent_agent_id', $currentLevelIds)
                ->pluck('id')
                ->all();

            $newIds = [];
            foreach ($nextLevelIds as $id) {
                if (! isset($visitedIds[$id])) {
                    $visitedIds[$id] = true;
                    $downlineIds[] = (int) $id;
                    $newIds[] = (int) $id;
                }
            }

            $currentLevelIds = $newIds;
            $depth++;
        }

        return $downlineIds;
    }

    /**
     * Build base query for deposits matching the rule's qualifying statuses and period.
     */
    public function applyRuleDepositFilters(Builder $query, PromotionRule $rule): Builder
    {
        // 1. Qualifying deposit statuses (e.g. ['ACTIVE'], ['ACTIVE', 'APPROVED'])
        $qualifyingStatuses = $rule->getQualifyingStatuses();
        $query->whereIn('status', $qualifyingStatuses);

        // 2. Calculation period filter
        $now = Carbon::now();

        match ($rule->calculation_period) {
            PromotionRule::PERIOD_FINANCIAL_YEAR => $this->applyFinancialYearFilter($query, $rule),
            PromotionRule::PERIOD_CURRENT_MONTH => $query->whereBetween('start_date', [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ]),
            PromotionRule::PERIOD_LAST_30_DAYS => $query->where('start_date', '>=', $now->copy()->subDays(30)->toDateString()),
            PromotionRule::PERIOD_LAST_90_DAYS => $query->where('start_date', '>=', $now->copy()->subDays(90)->toDateString()),
            PromotionRule::PERIOD_LAST_365_DAYS => $query->where('start_date', '>=', $now->copy()->subDays(365)->toDateString()),
            PromotionRule::PERIOD_CALENDAR_YEAR => $query->whereBetween('start_date', [
                $now->copy()->startOfYear()->toDateString(),
                $now->copy()->endOfYear()->toDateString(),
            ]),
            default => null, // All time
        };

        // If rule has an explicit financial_year_id bound, ensure deposits are restricted to that FY range
        if ($rule->financial_year_id && $rule->calculation_period !== PromotionRule::PERIOD_FINANCIAL_YEAR) {
            $this->applyFinancialYearFilter($query, $rule);
        }

        return $query;
    }

    /**
     * Scope query to the target financial year date range.
     */
    public function applyFinancialYearFilter(Builder $query, PromotionRule $rule): Builder
    {
        $fy = null;
        if ($rule->financial_year_id) {
            $fy = $rule->financialYear ?? FinancialYear::find($rule->financial_year_id);
        }
        if (! $fy) {
            $fy = FinancialYear::current() ?? FinancialYear::orderByDesc('start_date')->first();
        }

        if ($fy && $fy->start_date && $fy->end_date) {
            $start = $fy->start_date instanceof CarbonInterface ? $fy->start_date->toDateString() : (string) $fy->start_date;
            $end = $fy->end_date instanceof CarbonInterface ? $fy->end_date->toDateString() : (string) $fy->end_date;
            $query->whereBetween(DB::raw('COALESCE(start_date, deposit_date)'), [$start, $end]);
        }

        return $query;
    }

    /**
     * Calculate individual deposits originated directly by the agent.
     */
    public function calculateIndividualDeposits(Agent $agent, ?PromotionRule $rule = null): array
    {
        $query = Deposit::with('scheme')->where('agent_id', $agent->id);
        if ($rule) {
            $this->applyRuleDepositFilters($query, $rule);
        } else {
            $query->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED]);
        }

        $deposits = $query->get();
        $count = $deposits->count();

        $grossAmount = 0.0;
        $creditedAmount = 0.0;

        $rdCount = 0;
        $rdGross = 0.0;
        $rdCredited = 0.0;

        $fdCount = 0;
        $fdGross = 0.0;
        $fdCredited = 0.0;

        $ddCount = 0;
        $ddGross = 0.0;
        $ddCredited = 0.0;

        foreach ($deposits as $deposit) {
            $raw = (float) $deposit->amount;
            $credited = $rule ? $deposit->getPromotionTargetCreditedAmount($rule) : $raw;

            $grossAmount += $raw;
            $creditedAmount += $credited;

            $schemeType = strtoupper((string) ($deposit->deposit_type ?: $deposit->scheme?->scheme_type ?: 'FD'));
            if (in_array($schemeType, ['RD', 'RECURRING', 'RECURRING DEPOSIT'], true)) {
                $rdCount++;
                $rdGross += $raw;
                $rdCredited += $credited;
            } elseif (in_array($schemeType, ['DD', 'DAILY', 'DAILY DEPOSIT'], true)) {
                $ddCount++;
                $ddGross += $raw;
                $ddCredited += $credited;
            } else {
                $fdCount++;
                $fdGross += $raw;
                $fdCredited += $credited;
            }
        }

        return [
            'count' => $count,
            'amount' => round($creditedAmount, 2),
            'gross_amount' => round($grossAmount, 2),
            'rd_count' => $rdCount,
            'rd_amount' => round($rdCredited, 2),
            'rd_gross_amount' => round($rdGross, 2),
            'fd_count' => $fdCount,
            'fd_amount' => round($fdCredited, 2),
            'fd_gross_amount' => round($fdGross, 2),
            'dd_count' => $ddCount,
            'dd_amount' => round($ddCredited, 2),
            'dd_gross_amount' => round($ddGross, 2),
        ];
    }

    /**
     * Calculate team deposits across downline hierarchy.
     */
    public function calculateTeamDeposits(Agent $agent, ?PromotionRule $rule = null): array
    {
        $individual = $this->calculateIndividualDeposits($agent, $rule);

        // Direct downlines
        $directDownlineIds = Agent::where('parent_agent_id', $agent->id)->pluck('id')->all();

        // All downline tree (descendants)
        $allDownlineIds = $this->getDownlineAgentIds($agent);

        $downlineDeposits = collect();
        if (! empty($allDownlineIds)) {
            $downlineQuery = Deposit::with('scheme')->whereIn('agent_id', $allDownlineIds);
            if ($rule) {
                $this->applyRuleDepositFilters($downlineQuery, $rule);
            } else {
                $downlineQuery->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED]);
            }
            $downlineDeposits = $downlineQuery->get();
        }

        $downlineCount = $downlineDeposits->count();
        $downlineGross = 0.0;
        $downlineCredited = 0.0;

        $downlineRdCount = 0;
        $downlineRdGross = 0.0;
        $downlineRdCredited = 0.0;

        $downlineFdCount = 0;
        $downlineFdGross = 0.0;
        $downlineFdCredited = 0.0;

        $downlineDdCount = 0;
        $downlineDdGross = 0.0;
        $downlineDdCredited = 0.0;

        foreach ($downlineDeposits as $deposit) {
            $raw = (float) $deposit->amount;
            $credited = $rule ? $deposit->getPromotionTargetCreditedAmount($rule) : $raw;

            $downlineGross += $raw;
            $downlineCredited += $credited;

            $schemeType = strtoupper((string) ($deposit->deposit_type ?: $deposit->scheme?->scheme_type ?: 'FD'));
            if (in_array($schemeType, ['RD', 'RECURRING', 'RECURRING DEPOSIT'], true)) {
                $downlineRdCount++;
                $downlineRdGross += $raw;
                $downlineRdCredited += $credited;
            } elseif (in_array($schemeType, ['DD', 'DAILY', 'DAILY DEPOSIT'], true)) {
                $downlineDdCount++;
                $downlineDdGross += $raw;
                $downlineDdCredited += $credited;
            } else {
                $downlineFdCount++;
                $downlineFdGross += $raw;
                $downlineFdCredited += $credited;
            }
        }

        // Total team deposits = Personal volume + Downlines volume
        $totalTeamAmount = round($individual['amount'] + $downlineCredited, 2);
        $totalTeamGrossAmount = round($individual['gross_amount'] + $downlineGross, 2);
        $totalTeamCount = $individual['count'] + $downlineCount;

        $totalTeamRdAmount = round($individual['rd_amount'] + $downlineRdCredited, 2);
        $totalTeamRdGrossAmount = round($individual['rd_gross_amount'] + $downlineRdGross, 2);
        $totalTeamRdCount = $individual['rd_count'] + $downlineRdCount;

        $totalTeamFdAmount = round($individual['fd_amount'] + $downlineFdCredited, 2);
        $totalTeamFdGrossAmount = round($individual['fd_gross_amount'] + $downlineFdGross, 2);
        $totalTeamFdCount = $individual['fd_count'] + $downlineFdCount;

        $totalTeamDdAmount = round($individual['dd_amount'] + $downlineDdCredited, 2);
        $totalTeamDdGrossAmount = round($individual['dd_gross_amount'] + $downlineDdGross, 2);
        $totalTeamDdCount = $individual['dd_count'] + $downlineDdCount;

        return [
            'personal_amount' => $individual['amount'],
            'personal_gross_amount' => $individual['gross_amount'],
            'personal_count' => $individual['count'],
            'personal_rd_amount' => $individual['rd_amount'],
            'personal_rd_gross_amount' => $individual['rd_gross_amount'],
            'personal_rd_count' => $individual['rd_count'],
            'personal_fd_amount' => $individual['fd_amount'],
            'personal_fd_gross_amount' => $individual['fd_gross_amount'],
            'personal_fd_count' => $individual['fd_count'],
            'personal_dd_amount' => $individual['dd_amount'],
            'personal_dd_gross_amount' => $individual['dd_gross_amount'],
            'personal_dd_count' => $individual['dd_count'],
            'downline_amount' => round($downlineCredited, 2),
            'downline_gross_amount' => round($downlineGross, 2),
            'downline_count' => $downlineCount,
            'downline_rd_amount' => round($downlineRdCredited, 2),
            'downline_rd_gross_amount' => round($downlineRdGross, 2),
            'downline_rd_count' => $downlineRdCount,
            'downline_fd_amount' => round($downlineFdCredited, 2),
            'downline_fd_gross_amount' => round($downlineFdGross, 2),
            'downline_fd_count' => $downlineFdCount,
            'downline_dd_amount' => round($downlineDdCredited, 2),
            'downline_dd_gross_amount' => round($downlineDdGross, 2),
            'downline_dd_count' => $downlineDdCount,
            'total_team_amount' => $totalTeamAmount,
            'total_team_gross_amount' => $totalTeamGrossAmount,
            'total_team_count' => $totalTeamCount,
            'total_team_rd_amount' => $totalTeamRdAmount,
            'total_team_rd_gross_amount' => $totalTeamRdGrossAmount,
            'total_team_rd_count' => $totalTeamRdCount,
            'total_team_fd_amount' => $totalTeamFdAmount,
            'total_team_fd_gross_amount' => $totalTeamFdGrossAmount,
            'total_team_fd_count' => $totalTeamFdCount,
            'total_team_dd_amount' => $totalTeamDdAmount,
            'total_team_dd_gross_amount' => $totalTeamDdGrossAmount,
            'total_team_dd_count' => $totalTeamDdCount,
            'direct_downlines_count' => count($directDownlineIds),
            'total_downlines_count' => count($allDownlineIds),
        ];
    }

    /**
     * Evaluate 10% RD + 90% FD deposit composition against a financial target.
     *
     * Rules:
     * - Target is divided into required 10% RD and 90% FD components (dynamically configurable).
     * - FD & DD contribute at most 90% of the target towards eligible achievement.
     * - RD contributes towards the 10% RD requirement and total eligible volume.
     * - Target is satisfied if eligible total >= target AND RD requirement is met.
     */
    public function evaluateDepositComposition(
        float $targetAmount,
        float $rdAmount,
        float $fdAmount,
        float $rdPercent = 10.0,
        float $fdPercent = 90.0,
        float $ddAmount = 0.0
    ): array {
        $target = round(max(0.0, $targetAmount), 2);
        $rdActual = round(max(0.0, $rdAmount), 2);
        $fdActual = round(max(0.0, $fdAmount), 2);
        $ddActual = round(max(0.0, $ddAmount), 2);
        $nonRdActual = round($fdActual + $ddActual, 2);

        $rdTarget = round($target * ($rdPercent / 100.0), 2);
        $fdTarget = round($target * ($fdPercent / 100.0), 2);

        // Non-RD (FD + DD) can contribute at most the 90% target towards eligible achievement
        $eligibleFd = min($nonRdActual, $fdTarget);
        $eligibleRd = $rdActual;

        // Total eligible deposits capped at target amount
        $eligibleTotal = min($target, round($eligibleFd + $eligibleRd, 2));
        $grossTotal = round($rdActual + $fdActual + $ddActual, 2);

        $remainingAmount = max(0.00, round($target - $eligibleTotal, 2));
        $progressPercent = $target > 0.0 ? min(100.0, round(($eligibleTotal / $target) * 100.0, 1)) : 100.0;

        $isRdMet = $target <= 0.0 || $rdActual >= $rdTarget;
        $isFdMet = $target <= 0.0 || $nonRdActual >= $fdTarget;
        $isDepositTargetMet = ($eligibleTotal >= $target) && $isRdMet;

        $rdShortfall = max(0.00, round($rdTarget - $rdActual, 2));
        $fdShortfall = max(0.00, round($fdTarget - $nonRdActual, 2));

        $rdProgress = $rdTarget > 0.0 ? min(100.0, round(($rdActual / $rdTarget) * 100.0, 1)) : 100.0;
        $fdProgress = $fdTarget > 0.0 ? min(100.0, round(($nonRdActual / $fdTarget) * 100.0, 1)) : 100.0;

        return [
            'target_amount' => $target,
            'rd_percent' => $rdPercent,
            'fd_percent' => $fdPercent,
            'rd_target' => $rdTarget,
            'fd_target' => $fdTarget,
            'rd_actual' => $rdActual,
            'fd_actual' => $fdActual,
            'dd_actual' => $ddActual,
            'non_rd_actual' => $nonRdActual,
            'gross_total' => $grossTotal,
            'eligible_rd' => $eligibleRd,
            'eligible_fd' => $eligibleFd,
            'eligible_total' => $eligibleTotal,
            'remaining_amount' => $remainingAmount,
            'progress_percent' => $progressPercent,
            'is_rd_met' => $isRdMet,
            'is_fd_met' => $isFdMet,
            'is_target_met' => $isDepositTargetMet,
            'rd_shortfall' => $rdShortfall,
            'fd_shortfall' => $fdShortfall,
            'rd_progress' => $rdProgress,
            'fd_progress' => $fdProgress,
        ];
    }

    /**
     * Count qualifying direct agents under the agent, enforcing tier rules and distinct agent deduplication.
     */
    public function countQualifyingDirectAgents(Agent $agent, PromotionRule $rule): array
    {
        $tiers = $rule->getQualifyingAgentTiers();

        // Fetch all active direct downlines
        $directDownlines = Agent::where('parent_agent_id', $agent->id)
            ->where(function ($q) {
                $q->where('status', 'active')
                    ->orWhere('status', 'ACTIVE');
            })
            ->with(['careerLevel', 'rank', 'branch'])
            ->get();

        // If tiers array is empty, fall back to legacy target rank
        if (empty($tiers)) {
            $targetRank = $rule->getTargetQualifyingRank();
            $requiredCount = (int) $rule->min_qualifying_direct_facilitators;
            $tierSpec = [
                'count' => $requiredCount,
                'rank_id' => $targetRank?->id,
            ];
            $tiers = [$tierSpec];
        }

        // Preload ranks
        $allRanks = CareerLevel::all()->keyBy('id');
        $rankedTiers = [];
        foreach ($tiers as $tier) {
            $rId = $tier['rank_id'] ?? null;
            $rankModel = $rId ? ($allRanks->get($rId) ?? CareerLevel::find($rId)) : $rule->getTargetQualifyingRank();
            $rankedTiers[] = [
                'count' => (int) ($tier['count'] ?? 1),
                'rank_id' => $rankModel?->id,
                'rank' => $rankModel,
                'level' => (int) ($rankModel?->level ?? $rankModel?->sort_order ?? 0),
            ];
        }

        // Sort tiers descending by rank level so higher tiers get first pick of qualifying agents
        usort($rankedTiers, fn ($a, $b) => $b['level'] <=> $a['level']);

        $allocatedAgentIds = [];
        $tierResults = [];
        $allTiersMet = true;

        foreach ($rankedTiers as $tier) {
            $required = $tier['count'];
            $rankModel = $tier['rank'];
            $minLevel = $tier['level'];

            // Match available active downlines not already allocated to a higher tier slot
            $matched = $directDownlines->filter(function ($downline) use ($allocatedAgentIds, $minLevel) {
                if (in_array($downline->id, $allocatedAgentIds, true)) {
                    return false;
                }
                $agentLevel = (int) ($downline->careerLevel?->level ?? $downline->careerLevel?->sort_order ?? $downline->rank?->level ?? 1);

                return $agentLevel >= $minLevel;
            });

            $allocatedForThisTier = $matched->take($required);
            foreach ($allocatedForThisTier as $ag) {
                $allocatedAgentIds[] = $ag->id;
            }

            $actualCount = $matched->count();
            $isMet = $required <= 0 || $actualCount >= $required;
            if (! $isMet) {
                $allTiersMet = false;
            }

            $tierResults[] = [
                'required' => $required,
                'actual' => $actualCount,
                'allocated_count' => $allocatedForThisTier->count(),
                'met' => $isMet,
                'shortfall' => max(0, $required - $actualCount),
                'target_rank' => $rankModel,
                'target_rank_name' => $rankModel?->name ?? 'Agent',
                'target_rank_code' => $rankModel?->code ?? 'AGENT',
                'allocated_agents' => $allocatedForThisTier->values(),
            ];
        }

        $totalRequired = array_sum(array_column($tierResults, 'required'));
        $totalAllocated = count($allocatedAgentIds);

        return [
            'count' => $totalAllocated,
            'required' => $totalRequired,
            'met' => $allTiersMet,
            'tiers' => $tierResults,
            'target_rank' => $rule->getTargetQualifyingRank(),
            'agents' => $directDownlines->whereIn('id', $allocatedAgentIds)->values(),
        ];
    }

    /**
     * Backward-compatible alias for countQualifyingDirectFacilitators.
     */
    public function countQualifyingDirectFacilitators(Agent $agent, PromotionRule $rule): array
    {
        return $this->countQualifyingDirectAgents($agent, $rule);
    }

    /**
     * Evaluate comprehensive dynamic eligibility for an agent against a rule.
     */
    public function evaluateEligibility(
        Agent $agent,
        ?PromotionRule $rule = null,
        ?CareerLevel $targetLevel = null,
        ?int $excludeRequestId = null
    ): array {
        $currentLevel = $agent->careerLevel;

        // Resolve rule if not provided
        if (! $rule) {
            $rule = $this->findApplicableRule($agent, $targetLevel);
        }

        // If a rule exists, the proposed level is the rule's target rank
        if ($rule) {
            $targetLevel = $rule->toRank;
        } elseif (! $targetLevel) {
            $targetLevel = $currentLevel?->nextLevel();
        }

        // Check for existing pending request (ignoring current request if re-checking)
        $pendingQuery = PromotionRequest::where('agent_id', $agent->id)
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW);
        if ($excludeRequestId) {
            $pendingQuery->where('id', '!=', $excludeRequestId);
        }
        $hasPendingRequest = $pendingQuery->exists();

        // When evaluating for auto-promotion or when the applicable rule does not require approval,
        // a pending review request must not block eligibility calculation.
        if ($excludeRequestId === -1 || ($rule && ! $rule->requires_approval)) {
            $hasPendingRequest = false;
        }

        // Edge case: Inactive agent
        $agentStatus = strtolower((string) $agent->status);
        $requiredAgentStatus = strtolower($rule ? (string) $rule->required_agent_status : 'active');
        $isAgentActive = $requiredAgentStatus === 'any' || $agentStatus === $requiredAgentStatus;

        // If no dynamic rule exists, provide fallback result with rank target metrics
        if (! $rule) {
            $targetAmount = $targetLevel ? (float) ($targetLevel->minimum_deposit_amount ?? $targetLevel->min_deposit_amount ?? 0.00) : 0.00;
            $teamDeposits = $this->calculateTeamDeposits($agent);
            $comp = $this->evaluateDepositComposition(
                targetAmount: $targetAmount,
                rdAmount: (float) ($teamDeposits['total_team_rd_amount'] ?? 0.0),
                fdAmount: (float) ($teamDeposits['total_team_fd_amount'] ?? 0.0),
                ddAmount: (float) ($teamDeposits['total_team_dd_amount'] ?? 0.0)
            );
            $unmetReasons = [];
            if ($targetLevel) {
                $unmetReasons[] = 'No active dynamic promotion rule configured for this career transition.';
            } else {
                $unmetReasons[] = 'Agent is at the highest career rank ('.($currentLevel?->name ?? 'DD').').';
            }

            return [
                'is_eligible' => false,
                'has_rule' => false,
                'rule' => null,
                'rule_snapshot' => null,
                'has_pending_request' => $hasPendingRequest,
                'current_level' => $currentLevel,
                'target_level' => $targetLevel,
                'criteria' => [],
                'criteria_checklist' => [],
                'reasons' => $unmetReasons,
                'unmet_conditions' => $unmetReasons,
                'target_amount' => $targetAmount,
                'eligible_deposits' => $comp['eligible_total'],
                'achievement_amount' => $comp['eligible_total'],
                'remaining_amount' => $comp['remaining_amount'],
                'total_deposits' => (float) ($teamDeposits['total_team_gross_amount'] ?? $comp['gross_total']),
                'gross_deposits' => (float) ($teamDeposits['total_team_gross_amount'] ?? $comp['gross_total']),
                'credited_deposits' => (float) ($teamDeposits['total_team_amount'] ?? $comp['gross_total']),
                'progress_percent' => $comp['progress_percent'],
                'rd_target' => $comp['rd_target'],
                'rd_actual' => $comp['rd_actual'],
                'rd_eligible' => $comp['eligible_rd'],
                'rd_remaining' => $comp['rd_shortfall'],
                'rd_met' => $comp['is_rd_met'],
                'fd_target' => $comp['fd_target'],
                'fd_actual' => $comp['fd_actual'],
                'fd_eligible' => $comp['eligible_fd'],
                'fd_remaining' => $comp['fd_shortfall'],
                'fd_met' => $comp['is_fd_met'],
                'metrics' => [
                    'individual_deposits' => $this->calculateIndividualDeposits($agent),
                    'team_deposits' => $teamDeposits,
                    'facilitators' => ['count' => 0, 'required' => 0, 'met' => true, 'tiers' => []],
                ],
                'evaluated_at' => now()->toDateTimeString(),
            ];
        }

        // Calculate dynamic real database figures
        $individualDeposits = $this->calculateIndividualDeposits($agent, $rule);
        $teamDeposits = $this->calculateTeamDeposits($agent, $rule);
        $facilitators = $this->countQualifyingDirectAgents($agent, $rule);

        // Build criteria breakdown
        $criteria = [];
        $unmetReasons = [];

        // 1. Agent Status
        $statusMet = $isAgentActive;
        $criteria['agent_status'] = [
            'key' => 'agent_status',
            'label' => 'Agent Account Status',
            'required' => $requiredAgentStatus === 'any' ? 'Any Status' : ucfirst($requiredAgentStatus),
            'actual' => ucfirst($agentStatus),
            'unit' => '',
            'met' => $statusMet,
            'achieved' => $statusMet,
            'progress' => $statusMet ? 100 : 0,
        ];
        if (! $statusMet) {
            $unmetReasons[] = 'Agent account status must be '.$requiredAgentStatus.' (Current: '.ucfirst($agentStatus).').';
        }

        // 2. Minimum Individual Deposit
        $minIndDeposit = (float) $rule->min_individual_deposit;
        if ($minIndDeposit > 0) {
            $actualInd = $individualDeposits['amount'];
            $indMet = $actualInd >= $minIndDeposit;
            $criteria['individual_deposit'] = [
                'key' => 'individual_deposit',
                'label' => 'Minimum Personal Deposit Business',
                'required' => $minIndDeposit,
                'actual' => $actualInd,
                'unit' => '₹',
                'met' => $indMet,
                'achieved' => $indMet,
                'progress' => min(100, round(($actualInd / $minIndDeposit) * 100, 1)),
            ];
            if (! $indMet) {
                $unmetReasons[] = 'Personal deposit business: Required ₹'.number_format($minIndDeposit, 2).', Actual ₹'.number_format($actualInd, 2).'.';
            }
        }

        // 3. Minimum Team Deposit & 10% RD + 90% FD Composition
        $minTeamDeposit = (float) $rule->min_team_deposit;
        $rdPercent = $rule->getRdCompositionPercentage();
        $fdPercent = $rule->getFdCompositionPercentage();

        $comp = $this->evaluateDepositComposition(
            targetAmount: $minTeamDeposit,
            rdAmount: (float) ($teamDeposits['total_team_rd_amount'] ?? 0.0),
            fdAmount: (float) ($teamDeposits['total_team_fd_amount'] ?? 0.0),
            rdPercent: $rdPercent,
            fdPercent: $fdPercent,
            ddAmount: (float) ($teamDeposits['total_team_dd_amount'] ?? 0.0)
        );

        if ($minTeamDeposit > 0) {
            $teamMet = $comp['is_target_met'];
            $criteria['team_deposit'] = [
                'key' => 'team_deposit',
                'label' => 'Minimum Team Deposit Business',
                'required' => $minTeamDeposit,
                'actual' => $comp['eligible_total'],
                'gross_actual' => $comp['gross_total'],
                'eligible' => $comp['eligible_total'],
                'remaining' => $comp['remaining_amount'],
                'unit' => '₹',
                'met' => $teamMet,
                'achieved' => $teamMet,
                'progress' => $comp['progress_percent'],
                'rd_percent' => $rdPercent,
                'fd_percent' => $fdPercent,
                'rd_target' => $comp['rd_target'],
                'rd_actual' => $comp['rd_actual'],
                'rd_eligible' => $comp['eligible_rd'],
                'rd_remaining' => $comp['rd_shortfall'],
                'rd_met' => $comp['is_rd_met'],
                'fd_target' => $comp['fd_target'],
                'fd_actual' => $comp['fd_actual'],
                'fd_eligible' => $comp['eligible_fd'],
                'fd_remaining' => $comp['fd_shortfall'],
                'fd_met' => $comp['is_fd_met'],
                'dd_actual' => $comp['dd_actual'],
                'apply_tenure_targets' => (bool) $rule->apply_tenure_targets,
            ];

            if (! $comp['is_rd_met']) {
                $unmetReasons[] = number_format($rdPercent, 0).'% Recurring Deposit (RD) requirement: Required ₹'.number_format($comp['rd_target'], 2).', Actual ₹'.number_format($comp['rd_actual'], 2).' (Shortfall: ₹'.number_format($comp['rd_shortfall'], 2).').';
            }
            if ($comp['remaining_amount'] > 0.0) {
                $unmetReasons[] = 'Team deposit business volume: Required ₹'.number_format($minTeamDeposit, 2).', Eligible Achieved ₹'.number_format($comp['eligible_total'], 2).' (Remaining: ₹'.number_format($comp['remaining_amount'], 2).').';
            }
        }

        // 4. Qualifying Direct Downline Agents
        $minFacilitators = (int) $rule->min_qualifying_direct_facilitators;
        $hasTiers = ! empty($rule->getQualifyingAgentTiers());
        if ($minFacilitators > 0 || $hasTiers) {
            $facMet = ! empty($facilitators['met']);
            $qualRankName = $rule->getTargetQualifyingRank()?->name ?? 'Facilitator';
            $criteria['qualifying_facilitators'] = [
                'key' => 'qualifying_facilitators',
                'label' => "Qualifying Direct {$qualRankName}s",
                'required' => $facilitators['required'],
                'actual' => $facilitators['count'],
                'unit' => 'agents',
                'met' => $facMet,
                'achieved' => $facMet,
                'progress' => $facilitators['required'] > 0 ? min(100, round(($facilitators['count'] / $facilitators['required']) * 100, 1)) : 100,
                'tiers' => $facilitators['tiers'] ?? [],
            ];
            if (! $facMet) {
                if (! empty($facilitators['tiers'])) {
                    foreach ($facilitators['tiers'] as $tier) {
                        if (! $tier['met']) {
                            $unmetReasons[] = "Qualifying direct {$tier['target_rank_name']}s: Required {$tier['required']}, Actual {$tier['actual']} (Shortfall: {$tier['shortfall']}).";
                        }
                    }
                } else {
                    $unmetReasons[] = "Qualifying direct {$qualRankName}s: Required {$facilitators['required']}, Actual {$facilitators['count']}.";
                }
            }
        }

        if ($hasPendingRequest) {
            $unmetReasons[] = 'Agent already has an active promotion request pending review.';
        }

        // Overall eligibility check
        $allCriteriaMet = true;
        foreach ($criteria as $crit) {
            if (! $crit['met']) {
                $allCriteriaMet = false;
                break;
            }
        }

        $isEligible = $allCriteriaMet && ! $hasPendingRequest;

        return [
            'is_eligible' => $isEligible,
            'has_rule' => true,
            'rule' => $rule,
            'rule_snapshot' => $rule->toCriteriaSnapshot(),
            'has_pending_request' => $hasPendingRequest,
            'current_level' => $currentLevel,
            'target_level' => $targetLevel,
            'criteria' => $criteria,
            'criteria_checklist' => $criteria,
            'reasons' => $unmetReasons,
            'unmet_conditions' => $unmetReasons,
            'target_amount' => $minTeamDeposit,
            'eligible_deposits' => $comp['eligible_total'],
            'achievement_amount' => $comp['eligible_total'],
            'remaining_amount' => $comp['remaining_amount'],
            'total_deposits' => (float) ($teamDeposits['total_team_gross_amount'] ?? $comp['gross_total']),
            'gross_deposits' => (float) ($teamDeposits['total_team_gross_amount'] ?? $comp['gross_total']),
            'credited_deposits' => (float) ($teamDeposits['total_team_amount'] ?? $comp['gross_total']),
            'progress_percent' => $comp['progress_percent'],
            'rd_target' => $comp['rd_target'],
            'rd_actual' => $comp['rd_actual'],
            'rd_eligible' => $comp['eligible_rd'],
            'rd_remaining' => $comp['rd_shortfall'],
            'rd_met' => $comp['is_rd_met'],
            'fd_target' => $comp['fd_target'],
            'fd_actual' => $comp['fd_actual'],
            'fd_eligible' => $comp['eligible_fd'],
            'fd_remaining' => $comp['fd_shortfall'],
            'fd_met' => $comp['is_fd_met'],
            'dd_actual' => $comp['dd_actual'],
            'rd_percent' => $rdPercent,
            'fd_percent' => $fdPercent,
            'apply_tenure_targets' => (bool) $rule->apply_tenure_targets,
            'financial_year' => $rule->financialYear ? [
                'id' => $rule->financialYear->id,
                'name' => $rule->financialYear->name,
                'code' => $rule->financialYear->code,
            ] : null,
            'calculation_period' => $rule->calculation_period,
            'calculation_period_label' => $rule->getCalculationPeriodLabel(),
            'qualifying_agents' => $facilitators,
            'metrics' => [
                'individual_deposits' => $individualDeposits,
                'team_deposits' => $teamDeposits,
                'facilitators' => $facilitators,
                'composition' => $comp,
            ],
            'evaluated_at' => now()->toDateTimeString(),
        ];
    }
}
