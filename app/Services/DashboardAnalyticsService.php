<?php

namespace App\Services;

use App\Models\AgentTargetAssignment;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\FinancialYear;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardAnalyticsService
{
    /**
     * Active/approved/matured deposit statuses treated as mobilized portfolio.
     *
     * @var array<int, string>
     */
    private const PORTFOLIO_STATUSES = [
        Deposit::STATUS_ACTIVE,
        Deposit::STATUS_APPROVED,
        Deposit::STATUS_MATURED,
    ];

    /**
     * Scope an Eloquent query optionally by branch and/or agent.
     */
    protected function scopeQuery(Builder $query, ?int $branchId = null, ?int $agentId = null): Builder
    {
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        if ($agentId !== null) {
            $query->where('agent_id', $agentId);
        }

        return $query;
    }

    /**
     * Get the currently active financial year record, if configured.
     */
    public function getCurrentFinancialYear(): ?FinancialYear
    {
        return FinancialYear::current();
    }

    /**
     * Compute monthly deposit mobilization, commission accrual, and maturity volume trends.
     *
     * @return array<int, array{key: string, label: string, short_label: string, deposits: float, accounts: int, commissions: float, maturities: float, outflows: float}>
     */
    public function getMonthlyCashflowTrends(?int $branchId = null, int $months = 12, ?int $agentId = null): array
    {
        $months = max(1, min(24, $months));
        $start = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $deposits = $this->scopeQuery(
            Deposit::query()
                ->select(['id', 'amount', 'created_at'])
                ->whereIn('status', self::PORTFOLIO_STATUSES)
                ->whereBetween('created_at', [$start, $end]),
            $branchId,
            $agentId
        )->get();

        $commissions = $this->scopeQuery(
            Commission::query()
                ->select(['id', 'commission_amount', 'created_at'])
                ->where('status', '!=', Commission::STATUS_REJECTED)
                ->whereBetween('created_at', [$start, $end]),
            $branchId,
            $agentId
        )->get();

        $maturities = $this->scopeQuery(
            Deposit::query()
                ->select(['id', 'amount', 'maturity_amount', 'maturity_date'])
                ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_MATURED])
                ->whereBetween('maturity_date', [$start->toDateString(), $end->toDateString()]),
            $branchId,
            $agentId
        )->get();

        $depositBuckets = $deposits->groupBy(fn (Deposit $d): string => $d->created_at->format('Y-m'));
        $commissionBuckets = $commissions->groupBy(fn (Commission $c): string => $c->created_at->format('Y-m'));
        $maturityBuckets = $maturities->groupBy(fn (Deposit $d): string => Carbon::parse($d->maturity_date)->format('Y-m'));

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $cursor = Carbon::now()->subMonths($i)->startOfMonth();
            $key = $cursor->format('Y-m');

            $monthDeposits = $depositBuckets->get($key, collect());
            $monthCommissions = $commissionBuckets->get($key, collect());
            $monthMaturities = $maturityBuckets->get($key, collect());

            $commSum = round((float) $monthCommissions->sum('commission_amount'), 2);
            $matSum = round((float) $monthMaturities->sum(fn (Deposit $d): float => (float) ($d->maturity_amount ?: $d->amount)), 2);

            $series[] = [
                'key' => $key,
                'label' => $cursor->format('M Y'),
                'short_label' => $cursor->format('M'),
                'deposits' => round((float) $monthDeposits->sum('amount'), 2),
                'accounts' => $monthDeposits->count(),
                'commissions' => $commSum,
                'maturities' => $matSum,
                'outflows' => round($commSum + $matSum, 2),
            ];
        }

        return $series;
    }

    /**
     * Portfolio distribution grouped by deposit scheme type (FD, RD, DD, DOUBLE, etc.).
     *
     * @return Collection<int, array{type: string, label: string, count: int, amount: float, maturity_amount: float, share_pct: float}>
     */
    public function getPortfolioByScheme(?int $branchId = null, ?int $agentId = null): Collection
    {
        $rows = $this->scopeQuery(
            Deposit::query()
                ->whereIn('status', self::PORTFOLIO_STATUSES)
                ->selectRaw("COALESCE(deposit_type, 'FD') as type_code, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_amount, COALESCE(SUM(COALESCE(maturity_amount, amount)), 0) as total_maturity")
                ->groupBy('type_code'),
            $branchId,
            $agentId
        )->get();

        $grandTotal = (float) $rows->sum('total_amount');

        return $rows->map(function ($item) use ($grandTotal): array {
            $typeCode = strtoupper((string) $item->type_code);
            $label = match ($typeCode) {
                'FD' => 'Fixed Deposit (FD)',
                'RD' => 'Recurring Deposit (RD)',
                'DD' => 'Daily Deposit (DD)',
                'DOUBLE' => 'Double Deposit',
                'MIS' => 'Monthly Income Scheme',
                default => $typeCode ?: 'Other Schemes',
            };

            $amount = (float) $item->total_amount;

            return [
                'type' => $typeCode,
                'label' => $label,
                'count' => (int) $item->count,
                'amount' => $amount,
                'maturity_amount' => (float) $item->total_maturity,
                'share_pct' => $grandTotal > 0 ? round(($amount / $grandTotal) * 100, 1) : 0.0,
            ];
        })->sortByDesc('amount')->values();
    }

    /**
     * Commission ledger pipeline broken down by status.
     *
     * @return array{
     *   pending_count: int, pending_amount: float,
     *   approved_count: int, approved_amount: float,
     *   payable_count: int, payable_amount: float,
     *   paid_count: int, paid_amount: float,
     *   rejected_count: int, rejected_amount: float,
     *   total_liability: float, total_disbursed: float, total_pool: float
     * }
     */
    public function getCommissionPipeline(?int $branchId = null, ?int $agentId = null): array
    {
        $rows = $this->scopeQuery(
            Commission::query()
                ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(commission_amount), 0) as total')
                ->groupBy('status'),
            $branchId,
            $agentId
        )->get()->keyBy('status');

        $pendingCount = (int) ($rows[Commission::STATUS_PENDING]->cnt ?? 0);
        $pendingAmount = (float) ($rows[Commission::STATUS_PENDING]->total ?? 0);
        $approvedCount = (int) ($rows[Commission::STATUS_APPROVED]->cnt ?? 0);
        $approvedAmount = (float) ($rows[Commission::STATUS_APPROVED]->total ?? 0);
        $payableCount = (int) ($rows[Commission::STATUS_PAYABLE]->cnt ?? 0);
        $payableAmount = (float) ($rows[Commission::STATUS_PAYABLE]->total ?? 0);
        $paidCount = (int) ($rows[Commission::STATUS_PAID]->cnt ?? 0);
        $paidAmount = (float) ($rows[Commission::STATUS_PAID]->total ?? 0);
        $rejectedCount = (int) ($rows[Commission::STATUS_REJECTED]->cnt ?? 0);
        $rejectedAmount = (float) ($rows[Commission::STATUS_REJECTED]->total ?? 0);

        $totalLiability = round($pendingAmount + $approvedAmount + $payableAmount, 2);
        $totalPool = round($totalLiability + $paidAmount, 2);

        return [
            'pending_count' => $pendingCount,
            'pending_amount' => round($pendingAmount, 2),
            'approved_count' => $approvedCount,
            'approved_amount' => round($approvedAmount, 2),
            'payable_count' => $payableCount,
            'payable_amount' => round($payableAmount, 2),
            'paid_count' => $paidCount,
            'paid_amount' => round($paidAmount, 2),
            'rejected_count' => $rejectedCount,
            'rejected_amount' => round($rejectedAmount, 2),
            'total_liability' => $totalLiability,
            'total_disbursed' => round($paidAmount, 2),
            'total_pool' => $totalPool,
        ];
    }

    /**
     * Upcoming maturity liquidity horizon buckets (overdue, 0-30d, 31-60d, 61-90d).
     *
     * @return array<string, array{count: int, principal: float, payout: float}>
     */
    public function getMaturityHorizon(?int $branchId = null): array
    {
        $today = Carbon::today();
        $d30 = $today->copy()->addDays(30)->toDateString();
        $d60 = $today->copy()->addDays(60)->toDateString();
        $d90 = $today->copy()->addDays(90)->toDateString();
        $todayStr = $today->toDateString();

        $activeDeposits = $this->scopeQuery(
            Deposit::query()
                ->select(['id', 'amount', 'maturity_amount', 'maturity_date'])
                ->where('status', Deposit::STATUS_ACTIVE)
                ->whereNotNull('maturity_date')
                ->where('maturity_date', '<=', $d90),
            $branchId
        )->get();

        $buckets = [
            'overdue' => ['count' => 0, 'principal' => 0.0, 'payout' => 0.0],
            'days_30' => ['count' => 0, 'principal' => 0.0, 'payout' => 0.0],
            'days_60' => ['count' => 0, 'principal' => 0.0, 'payout' => 0.0],
            'days_90' => ['count' => 0, 'principal' => 0.0, 'payout' => 0.0],
        ];

        foreach ($activeDeposits as $dep) {
            $matDate = Carbon::parse($dep->maturity_date)->toDateString();
            $principal = (float) $dep->amount;
            $payout = (float) ($dep->maturity_amount ?: $dep->amount);

            $bucket = match (true) {
                $matDate < $todayStr => 'overdue',
                $matDate <= $d30 => 'days_30',
                $matDate <= $d60 => 'days_60',
                default => 'days_90',
            };

            $buckets[$bucket]['count']++;
            $buckets[$bucket]['principal'] += $principal;
            $buckets[$bucket]['payout'] += $payout;
        }

        foreach ($buckets as $key => $values) {
            $buckets[$key]['principal'] = round($values['principal'], 2);
            $buckets[$key]['payout'] = round($values['payout'], 2);
        }

        return $buckets;
    }

    /**
     * Core banking treasury summary indicators (AUM, maturity liability, weighted yield, MoM growth).
     *
     * @return array{
     *   active_aum: float,
     *   maturity_liability: float,
     *   interest_spread: float,
     *   weighted_avg_rate: float,
     *   pending_approval_count: int,
     *   pending_approval_amount: float,
     *   this_month_volume: float,
     *   last_month_volume: float,
     *   mom_growth_pct: float|null,
     *   double_count: int,
     *   senior_citizen_count: int
     * }
     */
    public function getTreasurySummary(?int $branchId = null): array
    {
        $activeStats = $this->scopeQuery(
            Deposit::query()
                ->where('status', Deposit::STATUS_ACTIVE)
                ->selectRaw('
                    COALESCE(SUM(amount), 0) as active_aum,
                    COALESCE(SUM(COALESCE(maturity_amount, amount)), 0) as maturity_liability,
                    COALESCE(SUM(amount * COALESCE(interest_rate, 0)), 0) as weighted_rate_numerator,
                    SUM(CASE WHEN is_double = 1 THEN 1 ELSE 0 END) as double_count,
                    SUM(CASE WHEN is_senior_citizen = 1 THEN 1 ELSE 0 END) as senior_citizen_count
                '),
            $branchId
        )->first();

        $activeAum = (float) ($activeStats->active_aum ?? 0);
        $maturityLiability = (float) ($activeStats->maturity_liability ?? 0);
        $weightedRate = $activeAum > 0
            ? round(((float) ($activeStats->weighted_rate_numerator ?? 0)) / $activeAum, 2)
            : 0.0;

        $pendingStats = $this->scopeQuery(
            Deposit::query()
                ->where('status', Deposit::STATUS_PENDING_APPROVAL)
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total'),
            $branchId
        )->first();

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth();

        $thisMonthVol = (float) $this->scopeQuery(
            Deposit::query()
                ->whereIn('status', self::PORTFOLIO_STATUSES)
                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd]),
            $branchId
        )->sum('amount');

        $lastMonthVol = (float) $this->scopeQuery(
            Deposit::query()
                ->whereIn('status', self::PORTFOLIO_STATUSES)
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]),
            $branchId
        )->sum('amount');

        $momGrowth = null;
        if ($lastMonthVol > 0) {
            $momGrowth = round((($thisMonthVol - $lastMonthVol) / $lastMonthVol) * 100, 1);
        } elseif ($thisMonthVol > 0) {
            $momGrowth = 100.0;
        }

        return [
            'active_aum' => round($activeAum, 2),
            'maturity_liability' => round($maturityLiability, 2),
            'interest_spread' => round(max(0.0, $maturityLiability - $activeAum), 2),
            'weighted_avg_rate' => $weightedRate,
            'pending_approval_count' => (int) ($pendingStats->cnt ?? 0),
            'pending_approval_amount' => round((float) ($pendingStats->total ?? 0), 2),
            'this_month_volume' => round($thisMonthVol, 2),
            'last_month_volume' => round($lastMonthVol, 2),
            'mom_growth_pct' => $momGrowth,
            'double_count' => (int) ($activeStats->double_count ?? 0),
            'senior_citizen_count' => (int) ($activeStats->senior_citizen_count ?? 0),
        ];
    }

    /**
     * Lead conversion funnel breakdown.
     *
     * @return array{
     *   total: int, new: int, contacted: int, interested: int,
     *   converted: int, not_interested: int, conversion_rate: float, pipeline_value: float
     * }
     */
    public function getLeadFunnel(?int $branchId = null, ?int $agentId = null): array
    {
        $rows = $this->scopeQuery(
            Lead::query()
                ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(expected_amount), 0) as expected_total')
                ->groupBy('status'),
            $branchId,
            $agentId
        )->get()->keyBy('status');

        $new = (int) ($rows[Lead::STATUS_NEW]->cnt ?? 0);
        $contacted = (int) ($rows[Lead::STATUS_CONTACTED]->cnt ?? 0);
        $interested = (int) ($rows[Lead::STATUS_INTERESTED]->cnt ?? 0);
        $converted = (int) ($rows[Lead::STATUS_CONVERTED]->cnt ?? 0);
        $notInterested = (int) ($rows[Lead::STATUS_NOT_INTERESTED]->cnt ?? 0);
        $total = $new + $contacted + $interested + $converted + $notInterested;

        $pipelineValue = (float) ($rows[Lead::STATUS_NEW]->expected_total ?? 0)
            + (float) ($rows[Lead::STATUS_CONTACTED]->expected_total ?? 0)
            + (float) ($rows[Lead::STATUS_INTERESTED]->expected_total ?? 0);

        return [
            'total' => $total,
            'new' => $new,
            'contacted' => $contacted,
            'interested' => $interested,
            'converted' => $converted,
            'not_interested' => $notInterested,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
            'pipeline_value' => round($pipelineValue, 2),
        ];
    }

    /**
     * Real mobilization goal progress from Financial Year target assignments
     * (or portfolio activation ratio when no FY targets are configured).
     *
     * @return array{achieved: float, target: float, pct: float, mode: string, title: string, caption: string}
     */
    public function getMobilizationGoal(?int $branchId = null): array
    {
        $fy = $this->getCurrentFinancialYear();

        $targetSum = (float) AgentTargetAssignment::query()
            ->where('status', AgentTargetAssignment::STATUS_ACTIVE)
            ->when($fy, fn (Builder $q) => $q->where('financial_year_id', $fy->id))
            ->when($branchId !== null, fn (Builder $q) => $q->whereHas('agent', fn (Builder $aq) => $aq->where('branch_id', $branchId)))
            ->sum('total_deposit_target');

        $activeVolume = (float) $this->scopeQuery(
            Deposit::query()->whereIn('status', self::PORTFOLIO_STATUSES),
            $branchId
        )->sum('amount');

        if ($targetSum > 0) {
            $pct = min(100.0, round(($activeVolume / $targetSum) * 100, 1));

            return [
                'achieved' => round($activeVolume, 2),
                'target' => round($targetSum, 2),
                'pct' => $pct,
                'mode' => 'fy_target',
                'title' => 'Mobilization Goal',
                'caption' => $fy ? "Attainment against {$fy->name} assigned advisor targets." : 'Attainment against assigned advisor deposit targets.',
            ];
        }

        $pendingVolume = (float) $this->scopeQuery(
            Deposit::query()->where('status', Deposit::STATUS_PENDING_APPROVAL),
            $branchId
        )->sum('amount');

        $totalSubmitted = $activeVolume + $pendingVolume;
        $pct = $totalSubmitted > 0 ? round(($activeVolume / $totalSubmitted) * 100, 1) : 0.0;

        return [
            'achieved' => round($activeVolume, 2),
            'target' => round($totalSubmitted, 2),
            'pct' => $pct,
            'mode' => 'approval_ratio',
            'title' => 'Mobilization Goal',
            'caption' => $totalSubmitted > 0
                ? 'Active mobilized deposits vs. total submitted applications.'
                : 'Book deposits or configure Financial Year targets to track goal velocity.',
        ];
    }

    /**
     * Seven-day operational processing volume from AuditLog.
     *
     * @return array<int, array{label: string, date: string, count: int}>
     */
    public function getWeeklyAuditActivity(?int $branchId = null, ?int $userId = null): array
    {
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();

        $logs = AuditLog::query()
            ->select(['id', 'created_at'])
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($userId !== null, fn (Builder $q) => $q->where('user_id', $userId))
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(fn (AuditLog $log): string => $log->created_at->toDateString());

        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dateStr = $day->toDateString();
            $series[] = [
                'label' => $day->format('D'),
                'date' => $day->format('d M'),
                'count' => $logs->get($dateStr, collect())->count(),
            ];
        }

        return $series;
    }
}
