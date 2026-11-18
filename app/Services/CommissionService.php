<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\CareerHistory;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositSchemeRankCommission;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    /**
     * Get the numeric rank order level for comparison across all 12 standardized career ranks.
     * Uses actual rank ordering (level / sort_order); never relies on database primary keys.
     */
    public function getRankOrder(?AgentRank $rank): int
    {
        return (int) ($rank?->level ?? $rank?->sort_order ?? 0);
    }

    /**
     * Get the effective rank of an agent as of a specific transaction date based on promotion records.
     * Enforces the rule that promotions apply existing effective-date rules and never retroactively
     * alter commissions simply because of a later promotion.
     */
    public function getEffectiveRankForAgent(Agent $agent, CarbonInterface|string|null $date = null): ?AgentRank
    {
        if (! $date) {
            return $agent->rank;
        }

        $targetDate = Carbon::parse($date)->toDateString();

        // If there is any promotion recorded strictly AFTER the transaction date,
        // the earliest such record specifies what rank the agent held (previous_level_id) prior to that promotion.
        $promotionAfter = CareerHistory::where('agent_id', $agent->id)
            ->whereDate('promotion_date', '>', $targetDate)
            ->orderBy('promotion_date', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if ($promotionAfter && $promotionAfter->previous_level_id) {
            $prevRank = AgentRank::find($promotionAfter->previous_level_id);
            if ($prevRank) {
                return $prevRank;
            }
        }

        // Otherwise check the latest promotion on or before the transaction date
        $promotionBeforeOrOn = CareerHistory::where('agent_id', $agent->id)
            ->whereDate('promotion_date', '<=', $targetDate)
            ->orderBy('promotion_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($promotionBeforeOrOn && $promotionBeforeOrOn->new_level_id) {
            $newRank = AgentRank::find($promotionBeforeOrOn->new_level_id);
            if ($newRank) {
                return $newRank;
            }
        }

        return $agent->rank;
    }

    /**
     * Find the best matching active commission rule for a collection or deposit.
     */
    public function findApplicableRule(DepositCollection|Deposit $subject, ?int $payoutYear = null): ?CommissionRule
    {
        if ($subject instanceof Deposit) {
            return $this->findApplicableRuleForDeposit($subject, $payoutYear);
        }

        $deposit = $subject->deposit;
        $agent = $subject->agent;

        if (! $deposit || ! $agent) {
            return null;
        }

        $schemeId = $deposit->deposit_scheme_id;
        $rankId = $agent->rank_id;
        $today = Carbon::today()->toDateString();

        $rulesQuery = CommissionRule::active()
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            });

        // 0. If the deposit scheme has explicit rank commission rules configured, respect rank eligibility
        if ($schemeId) {
            $hasSchemeRankRules = DepositSchemeRankCommission::where('deposit_scheme_id', $schemeId)->exists();
            if ($hasSchemeRankRules) {
                if (! $rankId) {
                    return null;
                }
                $rankRule = DepositSchemeRankCommission::where('deposit_scheme_id', $schemeId)
                    ->where('rank_id', $rankId)
                    ->where('is_eligible', true)
                    ->first();
                if (! $rankRule) {
                    // Agent rank is explicitly ineligible under this scheme; prevent fallback to generic rules
                    return null;
                }

                $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                    ->where('rank_id', $rankId)
                    ->first();
                if ($rule) {
                    return $rule;
                }

                if ((float) $rankRule->commission_percentage > 0) {
                    return new CommissionRule([
                        'name' => ($deposit->scheme?->name ?? 'Deposit Scheme').' - '.($agent->rank?->name ?? 'Rank'),
                        'code' => CommissionRule::generateRuleCode(),
                        'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                        'calculation_basis' => CommissionRule::BASIS_COLLECTION_AMOUNT,
                        'rate' => (float) $rankRule->commission_percentage,
                        'deposit_scheme_id' => $schemeId,
                        'rank_id' => $rankId,
                        'effective_from' => $today,
                        'status' => CommissionRule::STATUS_ACTIVE,
                    ]);
                }
            }
        }

        // 1. Match both Scheme and Rank
        if ($schemeId && $rankId) {
            $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                ->where('rank_id', $rankId)
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 2. Match Scheme only
        if ($schemeId) {
            $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                ->whereNull('rank_id')
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 3. Match Rank only
        if ($rankId) {
            $rule = (clone $rulesQuery)->where('rank_id', $rankId)
                ->whereNull('deposit_scheme_id')
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 4. Scheme total_commission_percentage fallback
        if ($schemeId && $deposit->scheme && (float) $deposit->scheme->total_commission_percentage > 0) {
            $rate = (float) $deposit->scheme->total_commission_percentage;

            $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                ->whereNull('rank_id')
                ->first();

            if ($rule) {
                return $rule;
            }

            return new CommissionRule([
                'name' => $deposit->scheme->name.' Standard Commission',
                'code' => CommissionRule::generateRuleCode(),
                'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                'calculation_basis' => CommissionRule::BASIS_COLLECTION_AMOUNT,
                'rate' => $rate,
                'deposit_scheme_id' => $schemeId,
                'rank_id' => null,
                'effective_from' => $today,
                'status' => CommissionRule::STATUS_ACTIVE,
            ]);
        }

        // 5. Global fallback rule (both null)
        return (clone $rulesQuery)->whereNull('deposit_scheme_id')
            ->whereNull('rank_id')
            ->first();
    }

    /**
     * Find matching commission rule for a deposit for a specific payout tenure year.
     * Supports targeting a specific agent (e.g. direct agent or upline recruiter) and their effective rank.
     */
    public function findApplicableRuleForDeposit(
        Deposit $deposit,
        ?int $payoutYear = null,
        ?Agent $agent = null,
        ?AgentRank $effectiveRank = null,
        bool $allowGenericFallback = true
    ): ?CommissionRule {
        $targetAgent = $agent ?? $deposit->agent;
        if (! $targetAgent) {
            return null;
        }

        $schemeId = $deposit->deposit_scheme_id;
        $targetRank = $effectiveRank ?? $this->getEffectiveRankForAgent($targetAgent, $deposit->deposit_date ?? $deposit->start_date);
        $rankId = $targetRank?->id ?? $targetAgent->rank_id;
        $year = $payoutYear ?? 1;
        $today = Carbon::today()->toDateString();

        $rulesQuery = CommissionRule::active()
            ->where('effective_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $today);
            });

        // 0. If the deposit scheme has explicit rank commission rules configured
        if ($schemeId) {
            $hasSchemeRankRules = DepositSchemeRankCommission::where('deposit_scheme_id', $schemeId)->exists();
            if ($hasSchemeRankRules) {
                if (! $rankId) {
                    return null;
                }

                // Look for rank commission rule configured specifically for this exact payout year
                $rankRule = DepositSchemeRankCommission::where('deposit_scheme_id', $schemeId)
                    ->where('rank_id', $rankId)
                    ->where('payout_year', $year)
                    ->first();

                $hasOtherYears = DepositSchemeRankCommission::where('deposit_scheme_id', $schemeId)
                    ->where('payout_year', '>', 1)
                    ->exists();

                // Single-tier scheme fallback: if scheme only has Year 1 configured and no other years exist,
                // the full Year 1 configured rate applies directly as the single commission (never divided).
                if (! $rankRule && ! $hasOtherYears) {
                    $rankRule = DepositSchemeRankCommission::where('deposit_scheme_id', $schemeId)
                        ->where('rank_id', $rankId)
                        ->where('payout_year', 1)
                        ->first();
                }

                if (! $rankRule || ! $rankRule->is_eligible) {
                    // Agent rank is not configured or explicitly ineligible under this scheme for this year
                    return null;
                }

                $rate = (float) $rankRule->commission_percentage;
                if ($rate <= 0) {
                    return null;
                }

                $ruleName = ($deposit->scheme?->name ?? 'Deposit Scheme').' - '.($targetRank?->name ?? $targetAgent->rank?->name ?? 'Rank')." (Year {$year})";
                $ruleQuery = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                    ->where('rank_id', $rankId);

                $rule = (clone $ruleQuery)->where(function ($q) use ($ruleName, $year) {
                    $q->where('name', $ruleName)
                        ->orWhere('name', 'like', "%(Year {$year})%");
                    if ($year === 1) {
                        $q->orWhere('name', 'not like', '%(Year %');
                    }
                })->first();

                if ($rule) {
                    if ((float) $rule->rate !== $rate) {
                        $rule = clone $rule;
                        $rule->rate = $rate;
                    }

                    return $rule;
                }

                // For Year 1, also check legacy base rule name
                if ($year === 1) {
                    $baseRuleName = ($deposit->scheme?->name ?? 'Deposit Scheme').' - '.($targetRank?->name ?? $targetAgent->rank?->name ?? 'Rank');
                    $baseRule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                        ->where('rank_id', $rankId)
                        ->where('name', $baseRuleName)
                        ->first();

                    if ($baseRule) {
                        if ((float) $baseRule->rate !== $rate) {
                            $baseRule = clone $baseRule;
                            $baseRule->rate = $rate;
                        }

                        return $baseRule;
                    }
                }

                return new CommissionRule([
                    'deposit_scheme_id' => $schemeId,
                    'rank_id' => $rankId,
                    'name' => $ruleName,
                    'code' => CommissionRule::generateRuleCode(),
                    'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                    'calculation_basis' => CommissionRule::BASIS_DEPOSIT_AMOUNT,
                    'rate' => $rate,
                    'effective_from' => $today,
                    'status' => CommissionRule::STATUS_ACTIVE,
                ]);
            }
        }

        // 1. Match both Scheme and Rank
        if ($schemeId && $rankId) {
            $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                ->where('rank_id', $rankId)
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 2. Match Rank only
        if ($rankId) {
            $rule = (clone $rulesQuery)->where('rank_id', $rankId)
                ->whereNull('deposit_scheme_id')
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // If generic fallback is disallowed (e.g. override recruiters require rank eligibility), stop here.
        if (! $allowGenericFallback) {
            return null;
        }

        // 3. Match Scheme only (generic rule without rank)
        if ($schemeId) {
            $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                ->whereNull('rank_id')
                ->first();
            if ($rule) {
                return $rule;
            }
        }

        // 4. Scheme total_commission_percentage fallback
        if ($schemeId && $deposit->scheme && (float) $deposit->scheme->total_commission_percentage > 0) {
            $rate = (float) $deposit->scheme->total_commission_percentage;

            $rule = (clone $rulesQuery)->where('deposit_scheme_id', $schemeId)
                ->whereNull('rank_id')
                ->first();

            if ($rule) {
                return $rule;
            }

            return new CommissionRule([
                'name' => $deposit->scheme->name.' Standard Commission',
                'code' => CommissionRule::generateRuleCode(),
                'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                'calculation_basis' => CommissionRule::BASIS_DEPOSIT_AMOUNT,
                'rate' => $rate,
                'deposit_scheme_id' => $schemeId,
                'rank_id' => null,
                'effective_from' => $today,
                'status' => CommissionRule::STATUS_ACTIVE,
            ]);
        }

        // 5. Global fallback rule (both null)
        return (clone $rulesQuery)->whereNull('deposit_scheme_id')
            ->whereNull('rank_id')
            ->first();
    }

    /**
     * Identify the correct lead-generating agent for a deposit.
     * Priority:
     * 1. Direct lead agent associated with deposit ($deposit->lead->agent)
     * 2. Converted lead agent associated with customer
     * 3. Customer's directly assigned agent ($deposit->customer->agent)
     * 4. Deposit's assigned agent ($deposit->agent)
     */
    public function resolveLeadGeneratingAgent(Deposit $deposit): ?Agent
    {
        // 1. Explicit lead associated with the deposit
        if ($deposit->lead && $deposit->lead->agent && $deposit->lead->agent->isActive()) {
            return $deposit->lead->agent;
        }

        // 2. Converted lead associated with the customer
        if ($deposit->customer_id) {
            $convertedLead = Lead::where('converted_customer_id', $deposit->customer_id)
                ->whereNotNull('agent_id')
                ->latest()
                ->first();
            if ($convertedLead && $convertedLead->agent && $convertedLead->agent->isActive()) {
                return $convertedLead->agent;
            }
        }

        // 3. Customer's directly assigned agent
        if ($deposit->customer && $deposit->customer->agent && $deposit->customer->agent->isActive()) {
            return $deposit->customer->agent;
        }

        // 4. Deposit's directly assigned agent
        if ($deposit->agent && $deposit->agent->isActive()) {
            return $deposit->agent;
        }

        return null;
    }

    /**
     * Automatically calculate and generate single pending commission for a deposit for its exact tenure
     * (e.g. 1 Year -> 1-year commission only, 2 Years -> 2-year commission only, 3 Years -> 3-year commission only),
     * including direct commission for the eligible lead-generating agent and hierarchical override distribution
     * for higher-rank recruiters for that exact tenure.
     * Never accumulates commissions from shorter tenures.
     *
     * @return Collection<int, Commission>
     */
    public function calculateAndCreateForDeposit(Deposit $deposit, ?User $creator = null): Collection
    {
        // Direct commission: Award the eligible lead-generating agent according to priority attribution rules
        $directAgent = $this->resolveLeadGeneratingAgent($deposit);

        if (! $directAgent || ! $directAgent->isActive()) {
            return collect();
        }

        if ((float) $deposit->amount <= 0) {
            return collect();
        }

        $exactTenureYear = max(1, $deposit->getTenureInYears());
        $commissions = collect();

        // 1. Direct commission for the lead-generating agent for the EXACT tenure year ONLY
        $commission = $this->calculateAndCreateForDepositYear(
            deposit: $deposit,
            year: $exactTenureYear,
            creator: $creator,
            totalTenureYears: $exactTenureYear,
            targetAgent: $directAgent
        );
        if ($commission) {
            $commissions->push($commission);
        }

        // 2. Hierarchical overrides for higher-rank recruiters in the recruitment chain for the EXACT tenure year ONLY
        $overrides = $this->calculateAndCreateOverridesForDepositYear(
            deposit: $deposit,
            year: $exactTenureYear,
            directAgent: $directAgent,
            creator: $creator,
            totalTenureYears: $exactTenureYear
        );
        foreach ($overrides as $overrideComm) {
            $commissions->push($overrideComm);
        }

        return $commissions;
    }

    /**
     * Calculate and generate pending commission for a single year of a deposit's tenure.
     */
    public function calculateAndCreateForDepositYear(
        Deposit $deposit,
        int $year,
        ?User $creator = null,
        ?int $totalTenureYears = null,
        ?Agent $targetAgent = null,
        bool $isOverride = false,
        ?Agent $sourceAgent = null
    ): ?Commission {
        $agent = $targetAgent ?? $this->resolveLeadGeneratingAgent($deposit);

        if (! $agent || ! $agent->isActive()) {
            return null;
        }

        if ((float) $deposit->amount <= 0) {
            return null;
        }

        $totalYears = $totalTenureYears ?? max(1, $deposit->getTenureInYears());

        // 1. Check if commission already generated for this deposit, agent, and payout_year (ignoring rejected ones)
        $existing = Commission::where('deposit_id', $deposit->id)
            ->where('agent_id', $agent->id)
            ->where('status', '!=', Commission::STATUS_REJECTED)
            ->where(function ($q) use ($year) {
                $q->where('payout_year', $year);
                if ($year === 1) {
                    $q->orWhereNull('payout_year');
                }
            })
            ->first();

        if ($existing) {
            if ($existing->payout_year === null && $year === 1) {
                $existing->update(['payout_year' => 1]);
            }

            return $existing;
        }

        $effectiveRank = $this->getEffectiveRankForAgent($agent, $deposit->deposit_date ?? $deposit->start_date);
        $allowGenericFallback = ! $isOverride;
        $rule = $this->findApplicableRuleForDeposit($deposit, $year, $agent, $effectiveRank, $allowGenericFallback);

        if (! $rule) {
            return null;
        }

        return DB::transaction(function () use ($deposit, $agent, $effectiveRank, $rule, $year, $totalYears, $creator, $isOverride, $sourceAgent) {
            $existing = Commission::where('deposit_id', $deposit->id)
                ->where('agent_id', $agent->id)
                ->where('status', '!=', Commission::STATUS_REJECTED)
                ->where(function ($q) use ($year) {
                    $q->where('payout_year', $year);
                    if ($year === 1) {
                        $q->orWhereNull('payout_year');
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->payout_year === null && $year === 1) {
                    $existing->update(['payout_year' => 1]);
                }

                return $existing;
            }

            $baseAmount = (float) $deposit->amount;
            $rate = (float) $rule->rate;
            $calculatedAmount = round($baseAmount * ($rate / 100), 2);

            if ($calculatedAmount < 0) {
                return null;
            }

            $commissionNumber = Commission::generateCommissionNumber();

            $notes = $isOverride
                ? "Tenure Year {$year} ({$totalYears}-year deposit) override commission from downline Agent {$sourceAgent?->name} ({$sourceAgent?->member_code}) for Deposit {$deposit->deposit_number}"
                : "Tenure Year {$year} ({$totalYears}-year deposit) commission for Deposit {$deposit->deposit_number}";

            $payoutRemarks = $isOverride
                ? "Year {$year} override commission at {$rate}%"
                : "Year {$year} commission at {$rate}%";

            $commission = Commission::create([
                'commission_number' => $commissionNumber,
                'commission_id' => $commissionNumber,
                'agent_id' => $agent->id,
                'branch_id' => $agent->branch_id ?? $deposit->branch_id,
                'customer_id' => $deposit->customer_id,
                'deposit_id' => $deposit->id,
                'deposit_scheme_id' => $deposit->deposit_scheme_id,
                'rule_id' => $rule->id,
                'base_amount' => $baseAmount,
                'rate' => $rate,
                'commission_rate' => $rate,
                'calculated_amount' => $calculatedAmount,
                'commission_amount' => $calculatedAmount,
                'status' => Commission::STATUS_PENDING,
                'period' => now()->format('Y-m'),
                'created_by' => $creator?->id ?? auth()->id(),
                'member_code' => $agent->member_code ?? $agent->agent_id,
                'rank_id' => $effectiveRank?->id ?? $agent->rank_id,
                'payout_year' => $year,
                'notes' => $notes,
                'payout_remarks' => $payoutRemarks,
            ]);

            $eventDesc = $isOverride
                ? "Automatically calculated Year {$year} recruiter override commission [{$commission->commission_number}] of ₹".number_format($calculatedAmount, 2)." ({$rate}%) for Recruiter [{$agent->name}] from downline Agent [{$sourceAgent?->name}] on Deposit [{$deposit->deposit_number}]. Status: PENDING Admin Approval."
                : "Automatically calculated Year {$year} agent commission [{$commission->commission_number}] of ₹".number_format($calculatedAmount, 2)." ({$rate}%) for Agent [{$agent->name}] on Deposit [{$deposit->deposit_number}]. Status: PENDING Admin Approval.";

            AuditLog::record(
                event: $isOverride ? 'deposit_override_commission_calculated' : 'deposit_commission_calculated',
                description: $eventDesc,
                auditable: $commission,
                newValues: [
                    'commission_number' => $commission->commission_number,
                    'deposit_id' => $deposit->id,
                    'agent_id' => $agent->id,
                    'is_override' => $isOverride,
                    'source_agent_id' => $sourceAgent?->id,
                    'payout_year' => $year,
                    'base_amount' => $baseAmount,
                    'rate' => $rate,
                    'calculated_amount' => $calculatedAmount,
                    'status' => Commission::STATUS_PENDING,
                ],
                amount: $calculatedAmount
            );

            return $commission;
        });
    }

    /**
     * Calculate and generate override commissions for higher-rank recruiters in the recruitment hierarchy.
     * Evaluates ancestors up the recruitment tree according to established rules:
     * - Same-rank recruitment: No override solely for recruiting an agent at the same rank.
     * - Higher-rank recruiter: Override is permitted only when rank eligibility and existing commission rules authorize it.
     * - Rank comparisons: Uses actual rank ordering (level / sort_order), never database IDs.
     * - Recruitment hierarchy: Preserves recruiter relationships after promotion.
     * - Historical preservation: Uses effective rank as of deposit date; later promotions do not retroactively alter awards.
     * - Independent agents: Branch-independent agents (null branch) are preserved.
     *
     * @return Collection<int, Commission>
     */
    public function calculateAndCreateOverridesForDepositYear(
        Deposit $deposit,
        int $year,
        Agent $directAgent,
        ?User $creator = null,
        ?int $totalTenureYears = null
    ): Collection {
        $overrides = collect();

        $depositDate = $deposit->deposit_date ?? $deposit->start_date;
        $directRank = $this->getEffectiveRankForAgent($directAgent, $depositDate);
        $highestRankLevelSoFar = $this->getRankOrder($directRank);

        $current = $directAgent;
        $visited = [$directAgent->id];

        while ($current->parent_agent_id) {
            $recruiter = $current->parentAgent;

            if (! $recruiter || in_array($recruiter->id, $visited, true)) {
                break;
            }

            $visited[] = $recruiter->id;
            $current = $recruiter;

            // 1. Recruiter must be active
            if (! $recruiter->isActive()) {
                continue;
            }

            // 2. Get recruiter's effective rank as of deposit date
            $recruiterRank = $this->getEffectiveRankForAgent($recruiter, $depositDate);
            if (! $recruiterRank) {
                continue;
            }

            $recruiterLevel = $this->getRankOrder($recruiterRank);

            // 3. Higher-rank recruiter check:
            // "Same-rank recruitment: No override solely for recruiting an agent at the same rank."
            // "Higher-rank recruiter: Override is permitted only when rank eligibility and existing commission rules authorize it."
            // "Rank comparisons: Use actual rank ordering, never assume database IDs represent rank order."
            if ($recruiterLevel <= $highestRankLevelSoFar) {
                continue;
            }

            // 4. Check if existing commission rules authorize an override for this rank
            $rule = $this->findApplicableRuleForDeposit($deposit, $year, $recruiter, $recruiterRank, false);
            if (! $rule || ! $rule->isActive() || (float) $rule->rate <= 0) {
                // Not eligible or no active rule with rate > 0
                $highestRankLevelSoFar = $recruiterLevel;

                continue;
            }

            // 5. Generate override commission for this recruiter
            $commission = $this->calculateAndCreateForDepositYear(
                deposit: $deposit,
                year: $year,
                creator: $creator,
                totalTenureYears: $totalTenureYears,
                targetAgent: $recruiter,
                isOverride: true,
                sourceAgent: $directAgent
            );

            if ($commission) {
                $overrides->push($commission);
            }

            // Update highest rank reached in this hierarchy path
            $highestRankLevelSoFar = $recruiterLevel;
        }

        return $overrides;
    }

    /**
     * Validate commission eligibility for a collection.
     */
    public function isEligibleForCommission(DepositCollection $collection): array
    {
        // 1. Agent must exist and be active
        $agent = $collection->agent;
        if (! $agent || $agent->status !== 'active') {
            return ['eligible' => false, 'reason' => 'Agent is inactive or not assigned.', 'rule' => null];
        }

        // 2. Customer relationship must be active
        $customer = $collection->customer;
        if (! $customer || $customer->status !== 'ACTIVE') {
            return ['eligible' => false, 'reason' => 'Customer is not active.', 'rule' => null];
        }

        // 3. Deposit must be active
        $deposit = $collection->deposit;
        if (! $deposit || ! $deposit->isActive()) {
            return ['eligible' => false, 'reason' => 'Deposit is not active.', 'rule' => null];
        }

        // 4. Collection must not be cancelled
        if ($collection->isCancelled()) {
            return ['eligible' => false, 'reason' => 'Collection is cancelled.', 'rule' => null];
        }

        // 5. Collection must have paid amount > 0
        if ((float) $collection->paid_amount <= 0) {
            return ['eligible' => false, 'reason' => 'No payment recorded for this collection.', 'rule' => null];
        }

        // 6. Duplicate check: commission must not have already been generated
        $alreadyGenerated = Commission::where('deposit_collection_id', $collection->id)
            ->where('agent_id', $agent->id)
            ->exists();
        if ($alreadyGenerated) {
            return ['eligible' => false, 'reason' => 'Commission has already been generated for this collection.', 'rule' => null];
        }

        // 7. Applicable rule must exist and be active
        $rule = $this->findApplicableRule($collection);
        if (! $rule || ! $rule->isActive()) {
            return ['eligible' => false, 'reason' => 'No active applicable commission rule found.', 'rule' => null];
        }

        return ['eligible' => true, 'reason' => null, 'rule' => $rule];
    }

    /**
     * Generate commission for an eligible collection.
     *
     * @throws ValidationException
     */
    public function generateForCollection(
        DepositCollection $collection,
        ?CommissionRule $rule = null,
        ?User $creator = null
    ): Commission {
        $eligibility = $this->isEligibleForCommission($collection);

        if (! $eligibility['eligible']) {
            throw ValidationException::withMessages([
                'collection' => $eligibility['reason'] ?? 'Collection is not eligible for commission generation.',
            ]);
        }

        $activeRule = $rule ?? $eligibility['rule'];
        if (! $activeRule || ! $activeRule->isActive()) {
            throw ValidationException::withMessages([
                'rule' => 'Applicable commission rule is invalid or inactive.',
            ]);
        }

        return DB::transaction(function () use ($collection, $activeRule, $creator) {
            // Lock collection to prevent concurrent duplicate generation
            $lockedColl = DepositCollection::where('id', $collection->id)->lockForUpdate()->firstOrFail();

            // Re-check duplicate inside transaction lock
            $existing = Commission::where('deposit_collection_id', $lockedColl->id)
                ->where('agent_id', $lockedColl->agent_id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            // Determine calculation base
            $baseAmount = ($activeRule->calculation_basis === CommissionRule::BASIS_DEPOSIT_AMOUNT)
                ? (float) $lockedColl->deposit->amount
                : (float) $lockedColl->paid_amount;

            // Calculate commission server-side
            $calculatedAmount = $activeRule->calculateCommission($baseAmount);

            if ($calculatedAmount < 0) {
                throw ValidationException::withMessages([
                    'calculated_amount' => 'Calculated commission amount cannot be negative.',
                ]);
            }

            $commissionNumber = Commission::generateCommissionNumber();

            $commission = Commission::create([
                'commission_number' => $commissionNumber,
                'commission_id' => $commissionNumber,
                'agent_id' => $lockedColl->agent_id,
                'branch_id' => $lockedColl->branch_id,
                'customer_id' => $lockedColl->customer_id,
                'deposit_id' => $lockedColl->deposit_id,
                'deposit_scheme_id' => $lockedColl->deposit->deposit_scheme_id,
                'deposit_collection_id' => $lockedColl->id,
                'rule_id' => $activeRule->id,
                'base_amount' => $baseAmount,
                'commission_rate' => $activeRule->rate,
                'commission_amount' => $calculatedAmount,
                'status' => Commission::STATUS_PENDING,
                'period' => now()->format('Y-m'),
                'created_by' => $creator?->id ?? auth()->id(),
                'member_code' => $lockedColl->agent?->member_code ?? $lockedColl->agent?->agent_id,
                'rank_id' => $lockedColl->agent?->rank_id,
            ]);

            AuditLog::record(
                event: 'commission_generated',
                description: "Generated commission [{$commission->commission_number}] of ₹".number_format($calculatedAmount, 2)." for Agent [{$lockedColl->agent->name}] from Collection [{$lockedColl->collection_number}]. Rule: {$activeRule->name}.",
                auditable: $commission,
                newValues: [
                    'commission_number' => $commission->commission_number,
                    'agent_id' => $commission->agent_id,
                    'base_amount' => $baseAmount,
                    'rate' => $activeRule->rate,
                    'calculated_amount' => $calculatedAmount,
                    'rule_id' => $activeRule->id,
                    'status' => Commission::STATUS_PENDING,
                ],
                amount: $calculatedAmount
            );

            return $commission;
        });
    }

    /**
     * Cancel or reverse all active pending/approved/payable commissions for a deposit upon cancellation or rejection.
     * Preserves financial audit trails and historical records.
     *
     * @return Collection<int, Commission>
     */
    public function cancelOrReverseForDeposit(Deposit $deposit, string $reason, ?User $user = null): Collection
    {
        return DB::transaction(function () use ($deposit, $reason, $user) {
            $commissions = Commission::where('deposit_id', $deposit->id)
                ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED, Commission::STATUS_PAYABLE])
                ->lockForUpdate()
                ->get();

            $reversed = collect();
            $actorId = $user?->id ?? auth()->id();
            $actorName = $user?->name ?? (auth()->user()?->name ?? 'System');

            foreach ($commissions as $comm) {
                $oldStatus = $comm->status;
                $rejectionReason = "Deposit [{$deposit->deposit_number}] cancelled/rejected: {$reason}";

                $comm->update([
                    'status' => Commission::STATUS_REJECTED,
                    'rejected_by' => $actorId,
                    'rejected_at' => now(),
                    'rejection_reason' => $rejectionReason,
                ]);

                AuditLog::record(
                    event: 'commission_reversed',
                    description: "Commission [{$comm->commission_number}] reversed by {$actorName} due to cancellation/rejection of Deposit [{$deposit->deposit_number}]. Reason: {$reason}",
                    auditable: $comm,
                    oldValues: ['status' => $oldStatus],
                    newValues: [
                        'status' => Commission::STATUS_REJECTED,
                        'rejection_reason' => $rejectionReason,
                        'rejected_by' => $actorId,
                        'rejected_at' => now()->toDateTimeString(),
                    ],
                    amount: (float) $comm->commission_amount
                );

                $reversed->push($comm);
            }

            return $reversed;
        });
    }

    /**
     * Handle deposit modifications with controlled reversal of active commissions and recalculation.
     * Preserves financial audit trails and historical records.
     *
     * @return Collection<int, Commission>
     */
    public function recalculateForDeposit(Deposit $deposit, string $reason = 'Deposit terms modified', ?User $user = null): Collection
    {
        return DB::transaction(function () use ($deposit, $reason, $user) {
            $lockedDeposit = Deposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            // 1. Reverse existing active (pending/approved/payable) commissions
            $activeCommissions = Commission::where('deposit_id', $lockedDeposit->id)
                ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED, Commission::STATUS_PAYABLE])
                ->lockForUpdate()
                ->get();

            $actorId = $user?->id ?? auth()->id();
            $actorName = $user?->name ?? (auth()->user()?->name ?? 'System');

            foreach ($activeCommissions as $comm) {
                $oldStatus = $comm->status;
                $rejectionReason = "Deposit [{$lockedDeposit->deposit_number}] modified: {$reason}";

                $comm->update([
                    'status' => Commission::STATUS_REJECTED,
                    'rejected_by' => $actorId,
                    'rejected_at' => now(),
                    'rejection_reason' => $rejectionReason,
                ]);

                AuditLog::record(
                    event: 'commission_reversed_for_recalculation',
                    description: "Commission [{$comm->commission_number}] reversed by {$actorName} due to modification of Deposit [{$lockedDeposit->deposit_number}]. Reason: {$reason}",
                    auditable: $comm,
                    oldValues: ['status' => $oldStatus],
                    newValues: [
                        'status' => Commission::STATUS_REJECTED,
                        'rejection_reason' => $rejectionReason,
                        'rejected_by' => $actorId,
                        'rejected_at' => now()->toDateTimeString(),
                    ],
                    amount: (float) $comm->commission_amount
                );
            }

            // 2. Recalculate single exact-tenure commission for the updated deposit terms
            return $this->calculateAndCreateForDeposit($lockedDeposit, $user);
        });
    }

    /**
     * Approve commission: PENDING -> APPROVED
     *
     * @throws ValidationException
     */
    public function approve(Commission $commission, User $user): Commission
    {
        $this->authorizeAdminAction($commission, $user);

        if ($commission->deposit && in_array($commission->deposit->status, [Deposit::STATUS_REJECTED, Deposit::STATUS_DRAFT], true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition: Cannot approve commission for a {$commission->deposit->status} deposit.",
            ]);
        }

        if ($commission->collection && $commission->collection->isCancelled()) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status transition: Cannot approve commission for a cancelled collection.',
            ]);
        }

        if (! $commission->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition: Commission with status '{$commission->status}' cannot be approved.",
            ]);
        }

        return DB::transaction(function () use ($commission, $user) {
            $oldStatus = $commission->status;

            $commission->update([
                'status' => Commission::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            AuditLog::record(
                event: 'commission_approved',
                description: "Commission [{$commission->commission_number}] was approved by {$user->name}.",
                auditable: $commission,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => Commission::STATUS_APPROVED, 'approved_by' => $user->id, 'approved_at' => now()->toDateTimeString()],
                amount: (float) $commission->calculated_amount
            );

            return $commission;
        });
    }

    /**
     * Reject commission: PENDING -> REJECTED
     *
     * @throws ValidationException
     */
    public function reject(Commission $commission, string $reason, User $user): Commission
    {
        $this->authorizeAdminAction($commission, $user);

        if (! $commission->canBeRejected()) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition: Commission with status '{$commission->status}' cannot be rejected.",
            ]);
        }

        return DB::transaction(function () use ($commission, $reason, $user) {
            $oldStatus = $commission->status;

            $commission->update([
                'status' => Commission::STATUS_REJECTED,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            AuditLog::record(
                event: 'commission_rejected',
                description: "Commission [{$commission->commission_number}] was rejected by {$user->name}. Reason: {$reason}",
                auditable: $commission,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => Commission::STATUS_REJECTED,
                    'rejected_by' => $user->id,
                    'rejected_at' => now()->toDateTimeString(),
                    'rejection_reason' => $reason,
                ],
                amount: (float) $commission->calculated_amount
            );

            return $commission;
        });
    }

    /**
     * Mark commission payable: APPROVED -> PAYABLE
     *
     * @throws ValidationException
     */
    public function markPayable(Commission $commission, User $user): Commission
    {
        $this->authorizeAdminAction($commission, $user);

        if (! $commission->canBeMarkedPayable()) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition: Only APPROVED commissions can be marked as PAYABLE. Current status: '{$commission->status}'.",
            ]);
        }

        return DB::transaction(function () use ($commission, $user) {
            $oldStatus = $commission->status;

            $commission->update([
                'status' => Commission::STATUS_PAYABLE,
            ]);

            AuditLog::record(
                event: 'commission_marked_payable',
                description: "Commission [{$commission->commission_number}] marked as PAYABLE by {$user->name}.",
                auditable: $commission,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => Commission::STATUS_PAYABLE],
                amount: (float) $commission->calculated_amount
            );

            return $commission;
        });
    }

    /**
     * Process commission payment: PAYABLE -> PAID
     *
     * @throws ValidationException
     */
    public function pay(Commission $commission, array $paymentData, User $user): Commission
    {
        $this->authorizeAdminAction($commission, $user);

        if (! $commission->canBePaid()) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition: Only PAYABLE commissions can be paid. Current status: '{$commission->status}'.",
            ]);
        }

        return DB::transaction(function () use ($commission, $paymentData, $user) {
            /** @var Commission $locked */
            $locked = Commission::where('id', $commission->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canBePaid()) {
                throw ValidationException::withMessages([
                    'status' => 'Commission is no longer in PAYABLE status.',
                ]);
            }

            $paymentDate = ! empty($paymentData['payment_date'])
                ? Carbon::parse($paymentData['payment_date'])->toDateString()
                : Carbon::today()->toDateString();

            $oldStatus = $locked->status;

            $locked->update([
                'status' => Commission::STATUS_PAID,
                'paid_by' => $user->id,
                'paid_at' => now(),
                'payment_date' => $paymentDate,
                'payment_method' => $paymentData['payment_method'] ?? 'BANK_TRANSFER',
                'payment_reference' => $paymentData['reference_number'] ?? null,
                'notes' => $paymentData['notes'] ?? $locked->notes,
                'payout_remarks' => $paymentData['notes'] ?? $locked->payout_remarks,
            ]);

            AuditLog::record(
                event: 'commission_paid',
                description: "Paid commission [{$locked->commission_number}] of ₹".number_format($locked->calculated_amount, 2)." to Agent [{$locked->agent->name}] via {$locked->payment_method} by {$user->name}.",
                auditable: $locked,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => Commission::STATUS_PAID,
                    'paid_by' => $user->id,
                    'paid_at' => now()->toDateTimeString(),
                    'payment_date' => $paymentDate,
                    'payment_method' => $locked->payment_method,
                    'payment_reference' => $locked->payment_reference,
                ],
                amount: (float) $locked->calculated_amount
            );

            return $locked;
        });
    }

    /**
     * Authorize administrative action on a commission.
     */
    protected function authorizeAdminAction(Commission $commission, User $user): void
    {
        // 1. Self-approval or self-payment is strictly prohibited (maker-checker violation)
        if ($commission->agent && $commission->agent->user_id && (int) $commission->agent->user_id === (int) $user->id) {
            abort(403, 'Unauthorized action: Self-approval or self-payment of commissions is strictly prohibited.');
        }

        // 2. Agents must NEVER approve or pay commissions
        if ($user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Unauthorized action: Agents are not permitted to approve, reject, or pay commissions.');
        }

        // 3. Staff check: must be admin or have assigned operational permissions
        if ($user->isStaff() && ! $user->isAdmin()) {
            if (! $user->hasPermission('approve-commissions') && ! $user->hasPermission('create-settlements')) {
                abort(403, 'Unauthorized action: You lack assigned permission to approve or settle commissions.');
            }
        }

        // 4. Branch Isolation check
        if (! $user->isSuperAdmin()) {
            $userBranchId = $user->branch_id ?? $user->staffProfile?->branch_id;
            if ($userBranchId !== null && $commission->branch_id !== null && (int) $commission->branch_id !== (int) $userBranchId) {
                abort(403, 'Unauthorized action: You cannot approve or pay commissions for another branch.');
            }
        }
    }
}
