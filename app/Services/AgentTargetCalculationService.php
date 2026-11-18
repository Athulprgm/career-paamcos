<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AgentRankTarget;
use App\Models\AgentTargetAssignment;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\FinancialYear;
use App\Models\PromotionRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgentTargetCalculationService
{
    /**
     * Resolve the target FinancialYear instance.
     */
    public function resolveFinancialYear(FinancialYear|string|int|null $fy = null): FinancialYear
    {
        if ($fy instanceof FinancialYear) {
            return $fy;
        }

        if (is_numeric($fy)) {
            $found = FinancialYear::find($fy);
            if ($found) {
                return $found;
            }
        } elseif (is_string($fy) && trim($fy) !== '') {
            $code = trim($fy);
            $found = FinancialYear::where('code', $code)
                ->orWhere('name', $code)
                ->first();
            if ($found) {
                return $found;
            }
        }

        return FinancialYear::current() ?? FinancialYear::firstOrCreate(
            ['code' => 'FY2026-27'],
            [
                'name' => 'FY 2026-27',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => 'active',
                'description' => 'Current operating Financial Year for Pristine Agro Cooperative Society.',
            ]
        );
    }

    /**
     * Get configured target for an agent in a specific financial year.
     * Looks up individual custom assignment first; falls back to the rank-wide configuration for the agent's rank,
     * and finally falls back to the Rank model definition.
     *
     * @return array<string, mixed>
     */
    public function getAgentTarget(Agent $agent, FinancialYear $fy, ?string $asOfDate = null): array
    {
        $date = $asOfDate ?? ($fy->isCurrent() ? now()->toDateString() : $fy->end_date->toDateString());
        $rule = $this->resolveApplicablePromotionRule($agent);
        $applyTenureTargets = $this->isTenureTargetEnabled($agent, $rule);

        // 1. Priority: Individual authorized target assignment
        $assignment = AgentTargetAssignment::where('agent_id', $agent->id)
            ->where('financial_year_id', $fy->id)
            ->where('status', AgentTargetAssignment::STATUS_ACTIVE)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->latest('effective_from')
            ->first();

        if ($assignment) {
            $rank = $assignment->careerLevel ?? $assignment->rank ?? $agent->careerLevel;

            return $this->formatTargetPayload(
                source: 'custom_assignment',
                type: 'custom',
                financialYear: $fy,
                rank: $rank,
                totalDeposit: (float) $assignment->total_deposit_target,
                fdPercentage: (float) $assignment->fd_percentage,
                rdPercentage: (float) $assignment->rd_percentage,
                fdTargetAmount: (float) $assignment->fd_target_amount,
                rdTargetAmount: (float) $assignment->rd_target_amount,
                recruitmentTarget: (int) $assignment->recruitment_target,
                applyTenureTargets: $applyTenureTargets,
                isCustom: true,
                effectiveFrom: $assignment->effective_from?->toDateString(),
                effectiveTo: $assignment->effective_to?->toDateString(),
                description: $assignment->revision_reason,
                assignmentId: $assignment->id,
            );
        }

        // 2. Resolve agent effective rank
        $effectiveRank = $this->getEffectiveRankForAgent($agent, $date);
        $rankId = $effectiveRank?->id ?? $agent->rank_id;

        // 3. Priority: Rank-wide target configuration in this Financial Year
        $rankTarget = AgentRankTarget::where('financial_year_id', $fy->id)
            ->where('rank_id', $rankId)
            ->where('status', AgentRankTarget::STATUS_ACTIVE)
            ->first();

        if ($rankTarget) {
            return $this->formatTargetPayload(
                source: 'rank_configuration',
                type: 'rank_default',
                financialYear: $fy,
                rank: $effectiveRank,
                totalDeposit: (float) $rankTarget->total_deposit_target,
                fdPercentage: (float) $rankTarget->fd_percentage,
                rdPercentage: (float) $rankTarget->rd_percentage,
                fdTargetAmount: (float) $rankTarget->fd_target_amount,
                rdTargetAmount: (float) $rankTarget->rd_target_amount,
                recruitmentTarget: (int) $rankTarget->recruitment_target,
                applyTenureTargets: $applyTenureTargets,
                isCustom: false,
                effectiveFrom: $fy->start_date->toDateString(),
                effectiveTo: $fy->end_date->toDateString(),
                description: $rankTarget->description,
                targetConfigId: $rankTarget->id,
            );
        }

        // 4. Priority: Minimal, logical fallback directly from Career Rank definition
        $total = (float) ($effectiveRank?->minimum_deposit_amount ?? $effectiveRank?->min_deposit_amount ?? 300000.00);
        $recruits = (int) ($rule?->min_qualifying_direct_facilitators ?? 3);
        $fdPct = 90.00;
        $rdPct = 10.00;

        return $this->formatTargetPayload(
            source: 'rank_default',
            type: 'rank_default',
            financialYear: $fy,
            rank: $effectiveRank,
            totalDeposit: $total,
            fdPercentage: $fdPct,
            rdPercentage: $rdPct,
            fdTargetAmount: round($total * ($fdPct / 100.0), 2),
            rdTargetAmount: round($total * ($rdPct / 100.0), 2),
            recruitmentTarget: $recruits,
            applyTenureTargets: $applyTenureTargets,
            isCustom: false,
            effectiveFrom: $fy->start_date->toDateString(),
            effectiveTo: $fy->end_date->toDateString(),
            description: 'Standard Rank Deposit & Recruitment Target',
        );
    }

    /**
     * Standardized, easy-to-understand target payload format.
     *
     * @return array<string, mixed>
     */
    protected function formatTargetPayload(
        string $source,
        string $type,
        FinancialYear $financialYear,
        ?CareerLevel $rank,
        float $totalDeposit,
        float $fdPercentage,
        float $rdPercentage,
        float $fdTargetAmount,
        float $rdTargetAmount,
        int $recruitmentTarget,
        bool $applyTenureTargets,
        bool $isCustom = false,
        ?string $effectiveFrom = null,
        ?string $effectiveTo = null,
        ?string $description = null,
        ?int $assignmentId = null,
        ?int $targetConfigId = null
    ): array {
        $level = (int) ($rank?->level ?? $rank?->sort_order ?? 1);
        $rankName = $rank?->name ?? 'Standard Agent';

        return [
            'target_source' => $source,
            'target_type' => $type,
            'is_configured' => true,
            'assignment_id' => $assignmentId,
            'target_configuration_id' => $targetConfigId,
            'financial_year' => $financialYear,
            'rank' => $rank,
            'rank_name' => $rankName,
            'rank_level' => $level,
            'total_deposit_target' => $totalDeposit,
            'total_deposit' => $totalDeposit,
            'fd_percentage' => $fdPercentage,
            'rd_percentage' => $rdPercentage,
            'fd_target_amount' => $fdTargetAmount,
            'fd_amount' => $fdTargetAmount,
            'rd_target_amount' => $rdTargetAmount,
            'rd_amount' => $rdTargetAmount,
            'recruitment_target' => $recruitmentTarget,
            'recruitment' => $recruitmentTarget,
            'apply_tenure_targets' => $applyTenureTargets,
            'is_custom' => $isCustom,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'description' => $description,
        ];
    }

    /**
     * Resolve the effective rank of an agent as of a specific date from promotion history.
     */
    public function getEffectiveRankForAgent(Agent $agent, ?string $date = null): ?CareerLevel
    {
        if (! $date) {
            return $agent->careerLevel ?? CareerLevel::find($agent->rank_id);
        }

        $targetDate = Carbon::parse($date)->toDateString();

        // Earliest promotion strictly after transaction date specifies previous rank
        $promotionAfter = CareerHistory::where('agent_id', $agent->id)
            ->whereDate('promotion_date', '>', $targetDate)
            ->orderBy('promotion_date', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if ($promotionAfter && $promotionAfter->previous_level_id) {
            $prevRank = CareerLevel::find($promotionAfter->previous_level_id);
            if ($prevRank) {
                return $prevRank;
            }
        }

        // Latest promotion on or before transaction date
        $promotionBeforeOrOn = CareerHistory::where('agent_id', $agent->id)
            ->whereDate('promotion_date', '<=', $targetDate)
            ->orderBy('promotion_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($promotionBeforeOrOn && $promotionBeforeOrOn->new_level_id) {
            $newRank = CareerLevel::find($promotionBeforeOrOn->new_level_id);
            if ($newRank) {
                return $newRank;
            }
        }

        return $agent->careerLevel ?? CareerLevel::find($agent->rank_id);
    }

    /**
     * Resolve the applicable promotion rule for an agent.
     */
    public function resolveApplicablePromotionRule(Agent $agent): ?PromotionRule
    {
        $eligibilityService = app(PromotionEligibilityService::class);
        $rule = $eligibilityService->findApplicableRule($agent);

        if ($rule) {
            return $rule;
        }

        if ($agent->rank_id) {
            $rule = PromotionRule::active()->where('from_rank_id', $agent->rank_id)->first();
            if ($rule) {
                return $rule;
            }
        }

        return PromotionRule::active()->where('apply_tenure_targets', true)->first()
            ?? PromotionRule::active()->first();
    }

    /**
     * Determine whether tenure-based target multipliers/weightages are enabled for an agent.
     */
    public function isTenureTargetEnabled(Agent $agent, ?PromotionRule $rule = null): bool
    {
        if ($rule && $rule->apply_tenure_targets) {
            return true;
        }

        $globalSetting = DB::table('settings')->where('key', 'career.apply_tenure_targets')->value('value');
        if ($globalSetting !== null) {
            return filter_var($globalSetting, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) PromotionRule::active()->where('apply_tenure_targets', true)->exists();
    }

    /**
     * Calculate deposit achievement for an agent within a Financial Year.
     * Follows exact rules:
     * - FD Achieved = Eligible FD deposits (or tenure-weighted if multipliers enabled).
     * - RD Achieved = Eligible RD deposits (or tenure-weighted if multipliers enabled).
     * - Total Achieved = FD + RD.
     * - Remaining = Maximum of Target − Achieved and zero.
     * - Achievement % = Achieved / Target × 100.
     * Excludes cancelled, reversed, and rejected deposits.
     * Does NOT include DD/Kisan unless explicitly configured.
     */
    public function calculateAgentDepositAchievement(Agent $agent, FinancialYear $fy): array
    {
        $target = $this->getAgentTarget($agent, $fy);

        $startDate = $fy->start_date->toDateString();
        $endDate = $fy->end_date->toDateString();

        // Query eligible, verified deposits originated by this agent
        // Active, Approved, or Matured deposits are eligible; Drafts/Rejected/Cancelled are strictly excluded.
        $deposits = Deposit::with('scheme')
            ->where('agent_id', $agent->id)
            ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED])
            ->whereBetween(DB::raw('COALESCE(start_date, deposit_date)'), [$startDate, $endDate])
            ->get();

        $rule = $this->resolveApplicablePromotionRule($agent);
        $applyTenureTargets = $this->isTenureTargetEnabled($agent, $rule);

        $fdCount = 0;
        $fdAchieved = 0.0;
        $fdGross = 0.0;
        $rdCount = 0;
        $rdAchieved = 0.0;
        $rdGross = 0.0;
        $ddCount = 0;
        $ddVolume = 0.0;
        $ddGross = 0.0;

        $qualifyingDeposits = [];

        foreach ($deposits as $deposit) {
            $rawAmount = (float) $deposit->amount;
            $schemeType = strtoupper((string) ($deposit->deposit_type ?? $deposit->scheme?->scheme_type ?? 'FD'));

            $isRd = in_array($schemeType, ['RD', 'RECURRING', 'RECURRING DEPOSIT'], true);
            $isDd = in_array($schemeType, ['DD', 'DAILY', 'DAILY DEPOSIT', 'KISAN'], true);

            if ($applyTenureTargets) {
                $creditedAmount = $rule
                    ? $deposit->getPromotionTargetCreditedAmount($rule)
                    : $deposit->getPromotionTargetCreditedAmount();
                $creditPercentage = $rule
                    ? $deposit->getPromotionTargetPercentage($rule)
                    : $deposit->getPromotionTargetPercentage();
            } else {
                $creditedAmount = $rawAmount;
                $creditPercentage = 100.00;
            }

            // Dynamically assign calculated values onto the model for blade views
            $deposit->credited_amount = $creditedAmount;
            $deposit->target_percentage = $creditPercentage;
            $deposit->raw_amount = $rawAmount;

            if ($isRd) {
                $rdCount++;
                $rdGross += $rawAmount;
                $rdAchieved += $creditedAmount;
                $qualifyingDeposits[] = [
                    'id' => $deposit->id,
                    'deposit_number' => $deposit->deposit_number,
                    'scheme_name' => $deposit->scheme?->name ?? 'Recurring Deposit',
                    'type' => 'RD',
                    'amount' => $rawAmount,
                    'credited_amount' => $creditedAmount,
                    'target_percentage' => $creditPercentage,
                    'date' => $deposit->start_date?->toDateString() ?? $deposit->deposit_date?->toDateString(),
                    'status' => $deposit->status,
                ];
            } elseif ($isDd) {
                // Tracked separately, NOT included in standard FD/RD deposit target achievement
                $ddCount++;
                $ddGross += $rawAmount;
                $ddVolume += $creditedAmount;
            } else {
                $fdCount++;
                $fdGross += $rawAmount;
                $fdAchieved += $creditedAmount;
                $qualifyingDeposits[] = [
                    'id' => $deposit->id,
                    'deposit_number' => $deposit->deposit_number,
                    'scheme_name' => $deposit->scheme?->name ?? 'Fixed Deposit',
                    'type' => 'FD',
                    'amount' => $rawAmount,
                    'credited_amount' => $creditedAmount,
                    'target_percentage' => $creditPercentage,
                    'date' => $deposit->start_date?->toDateString() ?? $deposit->deposit_date?->toDateString(),
                    'status' => $deposit->status,
                ];
            }
        }

        $fdAchieved = round($fdAchieved, 2);
        $rdAchieved = round($rdAchieved, 2);
        $totalAchieved = round($fdAchieved + $rdAchieved, 2);

        $totalTarget = (float) $target['total_deposit_target'];
        $fdTarget = (float) $target['fd_target_amount'];
        $rdTarget = (float) $target['rd_target_amount'];

        $remainingTotal = max(0.00, round($totalTarget - $totalAchieved, 2));
        $achievementPercent = $totalTarget > 0.0 ? round(($totalAchieved / $totalTarget) * 100.0, 2) : 100.0;

        $fdRemaining = max(0.00, round($fdTarget - $fdAchieved, 2));
        $rdRemaining = max(0.00, round($rdTarget - $rdAchieved, 2));

        $fdProgressPercent = $fdTarget > 0.0 ? round(($fdAchieved / $fdTarget) * 100.0, 2) : 100.0;
        $rdProgressPercent = $rdTarget > 0.0 ? round(($rdAchieved / $rdTarget) * 100.0, 2) : 100.0;

        $isFdMet = $fdAchieved >= $fdTarget;
        $isRdMet = $rdAchieved >= $rdTarget;
        $isTargetMet = ($totalAchieved >= $totalTarget) && $isFdMet && $isRdMet;

        // Subordinate/Team deposits (tracked for analysis and branch overview without merging into personal target)
        $downlineIds = Agent::where('parent_agent_id', $agent->id)->pluck('id')->all();
        $subordinateDepositsVolume = 0.0;
        $subordinateGrossVolume = 0.0;
        if (! empty($downlineIds)) {
            $subDeposits = Deposit::with('scheme')->whereIn('agent_id', $downlineIds)
                ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED])
                ->whereBetween(DB::raw('COALESCE(start_date, deposit_date)'), [$startDate, $endDate])
                ->whereNotIn(DB::raw('UPPER(deposit_type)'), ['DD', 'DAILY', 'DAILY DEPOSIT', 'KISAN'])
                ->get();

            foreach ($subDeposits as $subDep) {
                $rawSub = (float) $subDep->amount;
                $subordinateGrossVolume += $rawSub;
                $creditedSub = $applyTenureTargets
                    ? ($rule ? $subDep->getPromotionTargetCreditedAmount($rule) : $subDep->getPromotionTargetCreditedAmount())
                    : $rawSub;
                $subordinateDepositsVolume += $creditedSub;
            }
        }

        return [
            'total_target' => $totalTarget,
            'total_achieved' => $totalAchieved,
            'total_gross_achieved' => round($fdGross + $rdGross, 2),
            'total_remaining' => $remainingTotal,
            'total_percentage' => $achievementPercent,
            'remaining_deposit' => $remainingTotal,
            'achievement_percentage' => $achievementPercent,
            'fd_percentage' => $fdProgressPercent,
            'rd_percentage' => $rdProgressPercent,
            'fd_target_ratio' => (float) $target['fd_percentage'],
            'rd_target_ratio' => (float) $target['rd_percentage'],
            'fd_target' => $fdTarget,
            'rd_target' => $rdTarget,
            'fd_achieved' => $fdAchieved,
            'fd_gross_achieved' => round($fdGross, 2),
            'rd_achieved' => $rdAchieved,
            'rd_gross_achieved' => round($rdGross, 2),
            'fd_count' => $fdCount,
            'rd_count' => $rdCount,
            'fd_remaining' => $fdRemaining,
            'rd_remaining' => $rdRemaining,
            'fd_progress_percentage' => $fdProgressPercent,
            'rd_progress_percentage' => $rdProgressPercent,
            'is_fd_met' => $isFdMet,
            'is_rd_met' => $isRdMet,
            'is_target_met' => $isTargetMet,
            'dd_volume' => round($ddVolume, 2),
            'dd_gross_volume' => round($ddGross, 2),
            'dd_count' => $ddCount,
            'subordinate_deposit_volume' => round($subordinateDepositsVolume, 2),
            'subordinate_gross_volume' => round($subordinateGrossVolume, 2),
            'team_volume' => round($subordinateDepositsVolume, 2),
            'team_total_volume' => round($totalAchieved + $subordinateDepositsVolume, 2),
            'apply_tenure_targets' => $applyTenureTargets,
            'qualifying_deposits' => $qualifyingDeposits,
            'eligible_deposits' => $deposits,
        ];
    }

    /**
     * Calculate recruitment achievement for an agent within a Financial Year.
     * Rules:
     * - Count direct recruits only (parent_agent_id = agent.id).
     * - Only approved and active agents qualify (status = 'active').
     * - Exclude rejected, deleted and inactive agents.
     * - Prevent duplicate counting.
     * - Respect financial-year dates (joining_date / created_at within 1 April – 31 March).
     */
    public function calculateAgentRecruitmentAchievement(Agent $agent, FinancialYear $fy): array
    {
        $target = $this->getAgentTarget($agent, $fy);
        $recruitmentTarget = (int) ($target['recruitment_target'] ?? 0);

        $startDate = $fy->start_date->toDateString();
        $endDate = $fy->end_date->toDateString();

        // Query eligible direct recruits
        $recruits = Agent::with(['rank', 'branch'])
            ->where('parent_agent_id', $agent->id)
            ->whereIn('status', [Agent::STATUS_ACTIVE, 'ACTIVE'])
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('COALESCE(joining_date, DATE(created_at))'), [$startDate, $endDate])
            ->orderBy('joining_date', 'desc')
            ->get();

        $achievedCount = $recruits->count();
        $remaining = max(0, $recruitmentTarget - $achievedCount);
        $percentage = $recruitmentTarget > 0 ? round(($achievedCount / $recruitmentTarget) * 100.0, 2) : 100.0;
        $isMet = $achievedCount >= $recruitmentTarget;

        $recruitsList = $recruits->map(function (Agent $recruited) {
            return [
                'id' => $recruited->id,
                'agent_id' => $recruited->agent_id,
                'member_code' => $recruited->member_code,
                'name' => $recruited->name,
                'email' => $recruited->email,
                'phone' => $recruited->phone,
                'rank_name' => $recruited->rank?->name ?? 'Standard Agent',
                'rank_level' => $recruited->rank?->level ?? $recruited->rank?->sort_order ?? 1,
                'branch_name' => $recruited->branch?->name ?? 'Free Field',
                'joining_date' => $recruited->joining_date?->toDateString() ?? $recruited->created_at?->toDateString(),
                'status' => $recruited->status,
                'profile_image_url' => $recruited->profile_image_url,
            ];
        })->all();

        return [
            'target' => $recruitmentTarget,
            'recruitment_target' => $recruitmentTarget,
            'achieved' => $achievedCount,
            'recruits_achieved' => $achievedCount,
            'remaining' => $remaining,
            'recruits_remaining' => $remaining,
            'percentage' => $percentage,
            'recruitment_percentage' => $percentage,
            'is_recruitment_met' => $isMet,
            'recruits_list' => $recruitsList,
            'recruits' => $recruits,
        ];
    }

    /**
     * Complete performance summary for an agent for a Financial Year.
     */
    public function calculateAgentTargetPerformance(Agent $agent, FinancialYear|string|int|null $fy = null): array
    {
        $financialYear = $this->resolveFinancialYear($fy);
        $target = $this->getAgentTarget($agent, $financialYear);
        $depositAchievement = $this->calculateAgentDepositAchievement($agent, $financialYear);
        $recruitmentAchievement = $this->calculateAgentRecruitmentAchievement($agent, $financialYear);

        $isFullyAchieved = $depositAchievement['is_target_met'] && $recruitmentAchievement['is_recruitment_met'];

        $overallStatus = 'not_started';
        if ($isFullyAchieved) {
            $overallStatus = 'completed';
        } elseif (($depositAchievement['total_percentage'] ?? 0) >= 70 || ($recruitmentAchievement['percentage'] ?? 0) >= 70) {
            $overallStatus = 'on_track';
        } elseif (($depositAchievement['total_achieved'] ?? 0) > 0 || ($recruitmentAchievement['achieved'] ?? 0) > 0) {
            $overallStatus = 'in_progress';
        }

        return [
            'agent' => $agent,
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'agent_code' => $agent->member_code ?? $agent->agent_id,
            'branch_id' => $agent->branch_id,
            'branch_name' => $agent->branch?->name,
            'rank_id' => $target['rank']?->id ?? $agent->rank_id,
            'rank_name' => $target['rank_name'],
            'rank_level' => $target['rank_level'],
            'financial_year' => $financialYear,
            'financial_year_id' => $financialYear->id,
            'financial_year_code' => $financialYear->code,
            'financial_year_name' => $financialYear->name,
            'target_type' => $target['target_type'] ?? ($target['is_custom'] ? 'custom' : 'rank_default'),
            'is_configured' => true,
            'target' => $target,
            'targets' => [
                'total_deposit' => $target['total_deposit_target'],
                'fd_percentage' => $target['fd_percentage'],
                'rd_percentage' => $target['rd_percentage'],
                'fd_amount' => $target['fd_target_amount'],
                'rd_amount' => $target['rd_target_amount'],
                'recruitment' => $target['recruitment_target'],
                'status' => 'active',
                'description' => $target['description'] ?? null,
            ],
            'deposit' => $depositAchievement,
            'deposits' => $depositAchievement,
            'recruitment' => $recruitmentAchievement,
            'is_fully_achieved' => $isFullyAchieved,
            'overall_status' => $overallStatus,
            'overall_badge_class' => $isFullyAchieved ? 'badge-active' : 'badge-pending',
        ];
    }

    /**
     * Calculate Branch Target Performance across all agents in the branch.
     */
    public function calculateBranchTargetPerformance(Branch|int $branch, FinancialYear|string|int|null $fy = null): array
    {
        $financialYear = $this->resolveFinancialYear($fy);
        $branchModel = $branch instanceof Branch ? $branch : Branch::findOrFail($branch);

        $agents = Agent::where('branch_id', $branchModel->id)
            ->whereIn('status', [Agent::STATUS_ACTIVE, 'ACTIVE'])
            ->with(['rank', 'careerLevel'])
            ->orderBy('name')
            ->get();

        $totalAgents = $agents->count();
        $totalDepositTarget = 0.0;
        $totalFdTarget = 0.0;
        $totalRdTarget = 0.0;
        $totalFdAchieved = 0.0;
        $totalRdAchieved = 0.0;
        $totalDepositAchieved = 0.0;

        $totalRecruitmentTarget = 0;
        $totalRecruitsAchieved = 0;

        $agentPerformances = [];

        foreach ($agents as $agent) {
            $perf = $this->calculateAgentTargetPerformance($agent, $financialYear);
            $agentPerformances[] = $perf;

            $totalDepositTarget += $perf['deposit']['total_target'];
            $totalFdTarget += $perf['deposit']['fd_target'];
            $totalRdTarget += $perf['deposit']['rd_target'];
            $totalFdAchieved += $perf['deposit']['fd_achieved'];
            $totalRdAchieved += $perf['deposit']['rd_achieved'];
            $totalDepositAchieved += $perf['deposit']['total_achieved'];

            $totalRecruitmentTarget += $perf['recruitment']['recruitment_target'];
            $totalRecruitsAchieved += $perf['recruitment']['recruits_achieved'];
        }

        $depositAchievementPercent = $totalDepositTarget > 0.0
            ? round(($totalDepositAchieved / $totalDepositTarget) * 100.0, 2)
            : 100.0;

        $recruitmentAchievementPercent = $totalRecruitmentTarget > 0
            ? round(($totalRecruitsAchieved / $totalRecruitmentTarget) * 100.0, 2)
            : 100.0;

        return [
            'branch' => $branchModel,
            'financial_year' => $financialYear,
            'total_agents' => $totalAgents,
            'total_deposit_target' => round($totalDepositTarget, 2),
            'total_fd_target' => round($totalFdTarget, 2),
            'total_rd_target' => round($totalRdTarget, 2),
            'total_fd_achieved' => round($totalFdAchieved, 2),
            'total_rd_achieved' => round($totalRdAchieved, 2),
            'total_deposit_achieved' => round($totalDepositAchieved, 2),
            'deposit_achievement_percent' => $depositAchievementPercent,
            'total_deposit_remaining' => max(0.00, round($totalDepositTarget - $totalDepositAchieved, 2)),
            'deposit_remaining' => max(0.00, round($totalDepositTarget - $totalDepositAchieved, 2)),
            'total_recruitment_target' => $totalRecruitmentTarget,
            'total_recruits_achieved' => $totalRecruitsAchieved,
            'recruitment_achievement_percent' => $recruitmentAchievementPercent,
            'total_recruits_remaining' => max(0, $totalRecruitmentTarget - $totalRecruitsAchieved),
            'recruitment_remaining' => max(0, $totalRecruitmentTarget - $totalRecruitsAchieved),
            'agent_performances' => $agentPerformances,
        ];
    }

    /**
     * Calculate Target Performance aggregated by Rank.
     */
    public function calculateRankTargetPerformance(CareerLevel|AgentRank|int $rank, FinancialYear|string|int|null $fy = null, ?int $branchId = null): array
    {
        $financialYear = $this->resolveFinancialYear($fy);
        $rankId = is_numeric($rank) ? $rank : $rank->id;
        $rankModel = CareerLevel::find($rankId) ?? AgentRank::find($rankId);

        $query = Agent::where('rank_id', $rankId)
            ->whereIn('status', [Agent::STATUS_ACTIVE, 'ACTIVE']);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        $agents = $query->with(['branch'])->orderBy('name')->get();

        $configuredTarget = AgentRankTarget::where('financial_year_id', $financialYear->id)
            ->where('rank_id', $rankId)
            ->where('status', AgentRankTarget::STATUS_ACTIVE)
            ->first();

        $totalAgents = $agents->count();
        $totalDepositTarget = 0.0;
        $totalFdAchieved = 0.0;
        $totalRdAchieved = 0.0;
        $totalDepositAchieved = 0.0;
        $totalRecruitmentTarget = 0;
        $totalRecruitsAchieved = 0;

        foreach ($agents as $agent) {
            $perf = $this->calculateAgentTargetPerformance($agent, $financialYear);
            $totalDepositTarget += $perf['deposit']['total_target'];
            $totalFdAchieved += $perf['deposit']['fd_achieved'];
            $totalRdAchieved += $perf['deposit']['rd_achieved'];
            $totalDepositAchieved += $perf['deposit']['total_achieved'];
            $totalRecruitmentTarget += $perf['recruitment']['recruitment_target'];
            $totalRecruitsAchieved += $perf['recruitment']['recruits_achieved'];
        }

        $depositAchievementPercent = $totalDepositTarget > 0.0
            ? round(($totalDepositAchieved / $totalDepositTarget) * 100.0, 2)
            : 100.0;

        $recruitmentAchievementPercent = $totalRecruitmentTarget > 0
            ? round(($totalRecruitsAchieved / $totalRecruitmentTarget) * 100.0, 2)
            : 100.0;

        return [
            'rank' => $rankModel,
            'rank_id' => $rankId,
            'rank_name' => $rankModel?->name ?? 'Rank '.$rankId,
            'rank_level' => $rankModel?->level ?? $rankModel?->sort_order ?? 1,
            'financial_year' => $financialYear,
            'configured_target' => $configuredTarget,
            'total_agents' => $totalAgents,
            'unit_deposit_target' => (float) ($configuredTarget?->total_deposit_target ?? 0.0),
            'unit_recruitment_target' => (int) ($configuredTarget?->recruitment_target ?? 0),
            'total_deposit_target' => round($totalDepositTarget, 2),
            'total_deposit_achieved' => round($totalDepositAchieved, 2),
            'total_fd_achieved' => round($totalFdAchieved, 2),
            'total_rd_achieved' => round($totalRdAchieved, 2),
            'deposit_achievement_percent' => $depositAchievementPercent,
            'total_recruitment_target' => $totalRecruitmentTarget,
            'total_recruits_achieved' => $totalRecruitsAchieved,
            'recruitment_achievement_percent' => $recruitmentAchievementPercent,
        ];
    }

    /**
     * System Overview calculation: aggregated KPIs, Rank breakdown, Branch breakdown, Top agents.
     */
    public function calculateSystemOverview(FinancialYear|string|int|null $fy = null, ?int $restrictedBranchId = null): array
    {
        $financialYear = $this->resolveFinancialYear($fy);

        $agentsQuery = Agent::whereIn('status', [Agent::STATUS_ACTIVE, 'ACTIVE'])
            ->with(['branch', 'careerLevel']);

        if ($restrictedBranchId !== null) {
            $agentsQuery->where('branch_id', $restrictedBranchId);
        }

        $allAgents = $agentsQuery->orderBy('name')->get();

        $totalAgents = $allAgents->count();
        $totalDepositTarget = 0.0;
        $totalFdTarget = 0.0;
        $totalRdTarget = 0.0;
        $totalFdAchieved = 0.0;
        $totalRdAchieved = 0.0;
        $totalDepositAchieved = 0.0;
        $totalRecruitmentTarget = 0;
        $totalRecruitsAchieved = 0;

        $agentPerformances = [];

        foreach ($allAgents as $agent) {
            $perf = $this->calculateAgentTargetPerformance($agent, $financialYear);
            $agentPerformances[] = $perf;

            $totalDepositTarget += $perf['deposit']['total_target'];
            $totalFdTarget += $perf['deposit']['fd_target'];
            $totalRdTarget += $perf['deposit']['rd_target'];
            $totalFdAchieved += $perf['deposit']['fd_achieved'];
            $totalRdAchieved += $perf['deposit']['rd_achieved'];
            $totalDepositAchieved += $perf['deposit']['total_achieved'];

            $totalRecruitmentTarget += $perf['recruitment']['recruitment_target'];
            $totalRecruitsAchieved += $perf['recruitment']['recruits_achieved'];
        }

        $depositAchievementPercent = $totalDepositTarget > 0.0
            ? round(($totalDepositAchieved / $totalDepositTarget) * 100.0, 2)
            : 100.0;

        $recruitmentAchievementPercent = $totalRecruitmentTarget > 0
            ? round(($totalRecruitsAchieved / $totalRecruitmentTarget) * 100.0, 2)
            : 100.0;

        $completedAgents = 0;
        $onTrackAgents = 0;
        $configuredAgents = 0;

        foreach ($agentPerformances as $ap) {
            if ($ap['is_configured']) {
                $configuredAgents++;
            }
            if ($ap['overall_status'] === 'completed' || $ap['is_fully_achieved']) {
                $completedAgents++;
            } elseif ($ap['overall_status'] === 'on_track') {
                $onTrackAgents++;
            }
        }

        $depositRemaining = max(0.00, round($totalDepositTarget - $totalDepositAchieved, 2));
        $recruitmentRemaining = max(0, $totalRecruitmentTarget - $totalRecruitsAchieved);

        $totals = [
            'deposit_target' => round($totalDepositTarget, 2),
            'deposit_achieved' => round($totalDepositAchieved, 2),
            'deposit_remaining' => $depositRemaining,
            'deposit_percentage' => $depositAchievementPercent,
            'fd_target' => round($totalFdTarget, 2),
            'fd_achieved' => round($totalFdAchieved, 2),
            'fd_remaining' => max(0.00, round($totalFdTarget - $totalFdAchieved, 2)),
            'fd_percentage' => $totalFdTarget > 0 ? round(($totalFdAchieved / $totalFdTarget) * 100.0, 2) : 100.0,
            'rd_target' => round($totalRdTarget, 2),
            'rd_achieved' => round($totalRdAchieved, 2),
            'rd_remaining' => max(0.00, round($totalRdTarget - $totalRdAchieved, 2)),
            'rd_percentage' => $totalRdTarget > 0 ? round(($totalRdAchieved / $totalRdTarget) * 100.0, 2) : 100.0,
            'recruitment_target' => $totalRecruitmentTarget,
            'recruitment_achieved' => $totalRecruitsAchieved,
            'recruitment_remaining' => $recruitmentRemaining,
            'recruitment_percentage' => $recruitmentAchievementPercent,
        ];

        // Rank-wise performance aggregation
        $ranks = CareerLevel::ordered()->get();
        $rankBreakdown = [];
        foreach ($ranks as $rank) {
            $perf = $this->calculateRankTargetPerformance($rank, $financialYear, $restrictedBranchId);
            $target = $perf['configured_target'];
            $rankBreakdown[] = [
                'rank' => $rank,
                'target' => $target,
                'has_target' => $target !== null,
                'performance' => [
                    'agents_count' => $perf['total_agents'],
                    'deposit' => [
                        'total_target' => $perf['total_deposit_target'],
                        'total_achieved' => $perf['total_deposit_achieved'],
                        'total_remaining' => max(0.00, round($perf['total_deposit_target'] - $perf['total_deposit_achieved'], 2)),
                        'percentage' => $perf['deposit_achievement_percent'],
                        'fd_achieved' => $perf['total_fd_achieved'],
                        'fd_percentage' => $target && $target->fd_target_amount > 0 ? round(($perf['total_fd_achieved'] / ($target->fd_target_amount * max(1, $perf['total_agents']))) * 100.0, 1) : 0,
                        'rd_achieved' => $perf['total_rd_achieved'],
                        'rd_percentage' => $target && $target->rd_target_amount > 0 ? round(($perf['total_rd_achieved'] / ($target->rd_target_amount * max(1, $perf['total_agents']))) * 100.0, 1) : 0,
                    ],
                    'recruitment' => [
                        'target' => $perf['total_recruitment_target'],
                        'achieved' => $perf['total_recruits_achieved'],
                        'remaining' => max(0, $perf['total_recruitment_target'] - $perf['total_recruits_achieved']),
                        'percentage' => $perf['recruitment_achievement_percent'],
                    ],
                ],
            ];
        }

        // Branch-wise performance aggregation
        $branchesQuery = Branch::where('is_active', true);
        if ($restrictedBranchId !== null) {
            $branchesQuery->where('id', $restrictedBranchId);
        }
        $branches = $branchesQuery->orderBy('name')->get();
        $branchBreakdown = [];
        foreach ($branches as $branch) {
            $perf = $this->calculateBranchTargetPerformance($branch, $financialYear);
            $branchBreakdown[] = [
                'branch' => $branch,
                'performance' => [
                    'agents_count' => $perf['total_agents'],
                    'deposit' => [
                        'total_target' => $perf['total_deposit_target'],
                        'total_achieved' => $perf['total_deposit_achieved'],
                        'total_remaining' => $perf['total_deposit_remaining'],
                        'percentage' => $perf['deposit_achievement_percent'],
                        'fd_achieved' => $perf['total_fd_achieved'],
                        'rd_achieved' => $perf['total_rd_achieved'],
                    ],
                    'recruitment' => [
                        'target' => $perf['total_recruitment_target'],
                        'achieved' => $perf['total_recruits_achieved'],
                        'remaining' => $perf['total_recruits_remaining'],
                        'percentage' => $perf['recruitment_achievement_percent'],
                    ],
                ],
            ];
        }

        // Top 5 performing agents by deposit volume achieved
        $topAgents = collect($agentPerformances)
            ->sortByDesc(fn ($p) => $p['deposit']['total_achieved'])
            ->take(5)
            ->values()
            ->all();

        return [
            'financial_year' => $financialYear,
            'total_agents' => $totalAgents,
            'configured_agents' => $configuredAgents,
            'completed_agents' => $completedAgents,
            'on_track_agents' => $onTrackAgents,
            'totals' => $totals,
            'total_deposit_target' => round($totalDepositTarget, 2),
            'total_fd_target' => round($totalFdTarget, 2),
            'total_rd_target' => round($totalRdTarget, 2),
            'total_fd_achieved' => round($totalFdAchieved, 2),
            'total_rd_achieved' => round($totalRdAchieved, 2),
            'total_deposit_achieved' => round($totalDepositAchieved, 2),
            'deposit_achievement_percent' => $depositAchievementPercent,
            'total_deposit_remaining' => $depositRemaining,
            'total_recruitment_target' => $totalRecruitmentTarget,
            'total_recruits_achieved' => $totalRecruitsAchieved,
            'recruitment_achievement_percent' => $recruitmentAchievementPercent,
            'total_recruits_remaining' => $recruitmentRemaining,
            'rank_breakdown' => $rankBreakdown,
            'ranks_breakdown' => $rankBreakdown,
            'branch_breakdown' => $branchBreakdown,
            'branches_breakdown' => $branchBreakdown,
            'top_agents' => $topAgents,
            'agent_performances' => $agentPerformances,
        ];
    }
}
