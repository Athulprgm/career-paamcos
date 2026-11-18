<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\CollectionPayment;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\Lead;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardAnalyticsService $analytics
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $branchId = (int) $user->branch_id;
        $today = Carbon::today()->toDateString();
        $thirtyDaysFromNow = Carbon::today()->addDays(30)->toDateString();

        // ── 1. Branch-Scoped Operational Statistics ───────────────────────────
        $stats = [
            'active_customers' => Customer::where('branch_id', $branchId)
                ->where('status', Customer::STATUS_ACTIVE)
                ->count(),

            'active_agents' => Agent::where('branch_id', $branchId)
                ->whereIn('status', ['active', 'ACTIVE'])
                ->count(),

            'total_deposits' => Deposit::where('branch_id', $branchId)->count(),
            'total_deposits_volume' => (float) Deposit::where('branch_id', $branchId)
                ->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_MATURED])
                ->sum('amount'),

            'todays_collections_count' => DepositCollection::where('branch_id', $branchId)
                ->whereDate('due_date', $today)
                ->whereNotIn('status', [DepositCollection::STATUS_CANCELLED])
                ->count(),
            'todays_collections_amount' => (float) DepositCollection::where('branch_id', $branchId)
                ->whereDate('due_date', $today)
                ->whereNotIn('status', [DepositCollection::STATUS_CANCELLED])
                ->sum('due_amount'),

            'outstanding_collections' => DepositCollection::where('branch_id', $branchId)
                ->whereNotIn('status', [DepositCollection::STATUS_PAID, DepositCollection::STATUS_CANCELLED])
                ->whereRaw('paid_amount < due_amount')
                ->count(),
            'outstanding_amount' => (float) DepositCollection::where('branch_id', $branchId)
                ->whereNotIn('status', [DepositCollection::STATUS_PAID, DepositCollection::STATUS_CANCELLED])
                ->sum(DB::raw('due_amount - paid_amount')),

            'pending_commission' => Commission::where('branch_id', $branchId)
                ->where('status', Commission::STATUS_PENDING)
                ->count(),
            'pending_commission_amount' => (float) Commission::where('branch_id', $branchId)
                ->where('status', Commission::STATUS_PENDING)
                ->sum('commission_amount'),

            'upcoming_maturities' => Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_ACTIVE)
                ->whereBetween('maturity_date', [$today, $thirtyDaysFromNow])
                ->count(),
            'upcoming_maturities_amount' => (float) Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_ACTIVE)
                ->whereBetween('maturity_date', [$today, $thirtyDaysFromNow])
                ->sum('amount'),

            'total_leads' => Lead::where('branch_id', $branchId)->count(),
            'new_leads' => Lead::where('branch_id', $branchId)->where('status', Lead::STATUS_NEW)->count(),
        ];

        // ── 2. Collection Summary ─────────────────────────────────────────────
        $collectionSummary = [
            'today_due_count' => DepositCollection::where('branch_id', $branchId)
                ->whereDate('due_date', $today)
                ->count(),
            'today_due_amount' => (float) DepositCollection::where('branch_id', $branchId)
                ->whereDate('due_date', $today)
                ->sum('due_amount'),
            'today_collected_count' => CollectionPayment::where('branch_id', $branchId)
                ->whereDate('payment_date', $today)
                ->count(),
            'today_collected_amount' => (float) CollectionPayment::where('branch_id', $branchId)
                ->whereDate('payment_date', $today)
                ->sum('amount'),
            'overdue_count' => DepositCollection::where('branch_id', $branchId)
                ->where('due_date', '<', $today)
                ->whereNotIn('status', [DepositCollection::STATUS_PAID, DepositCollection::STATUS_CANCELLED])
                ->whereRaw('paid_amount < due_amount')
                ->count(),
            'overdue_amount' => (float) DepositCollection::where('branch_id', $branchId)
                ->where('due_date', '<', $today)
                ->whereNotIn('status', [DepositCollection::STATUS_PAID, DepositCollection::STATUS_CANCELLED])
                ->sum(DB::raw('due_amount - paid_amount')),
            'total_outstanding' => (float) DepositCollection::where('branch_id', $branchId)
                ->whereNotIn('status', [DepositCollection::STATUS_PAID, DepositCollection::STATUS_CANCELLED])
                ->sum(DB::raw('due_amount - paid_amount')),
        ];

        // ── 3. Deposit Summary ────────────────────────────────────────────────
        $depositSummary = [
            'active_count' => Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_ACTIVE)
                ->count(),
            'active_volume' => (float) Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_ACTIVE)
                ->sum('amount'),
            'pending_approval_count' => Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_PENDING_APPROVAL)
                ->count(),
            'pending_approval_volume' => (float) Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_PENDING_APPROVAL)
                ->sum('amount'),
            'matured_count' => Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_MATURED)
                ->count(),
            'matured_volume' => (float) Deposit::where('branch_id', $branchId)
                ->where('status', Deposit::STATUS_MATURED)
                ->sum('amount'),
        ];

        // ── 4. Branch-Scoped Core Banking Analytics ───────────────────────────
        $twelveMonthTrends = $this->analytics->getMonthlyCashflowTrends($branchId, 12);
        $monthlyTrends = array_slice($twelveMonthTrends, -6);
        $portfolioByType = $this->analytics->getPortfolioByScheme($branchId);
        $treasury = $this->analytics->getTreasurySummary($branchId);
        $commissionPipeline = $this->analytics->getCommissionPipeline($branchId);
        $maturityHorizon = $this->analytics->getMaturityHorizon($branchId);
        $leadFunnel = $this->analytics->getLeadFunnel($branchId);
        $mobilizationGoal = $this->analytics->getMobilizationGoal($branchId);
        $currentFy = $this->analytics->getCurrentFinancialYear();

        $recentDeposits = Deposit::with(['customer:id,name,customer_number', 'scheme:id,name,scheme_type'])
            ->where('branch_id', $branchId)
            ->latest()
            ->limit(6)
            ->get();

        // ── 5. Agent Performance (Branch Agents) ──────────────────────────────
        $agentPerformance = Agent::where('branch_id', $branchId)
            ->whereIn('status', ['active', 'ACTIVE'])
            ->with(['rank'])
            ->withCount([
                'customers as active_customers_count' => fn ($q) => $q->where('status', Customer::STATUS_ACTIVE),
                'deposits as active_deposits_count' => fn ($q) => $q->where('status', Deposit::STATUS_ACTIVE),
            ])
            ->withSum([
                'deposits as active_deposits_sum' => fn ($q) => $q->where('status', Deposit::STATUS_ACTIVE),
            ], 'amount')
            ->withSum([
                'commissions as paid_commissions_sum' => fn ($q) => $q->where('status', Commission::STATUS_PAID),
            ], 'commission_amount')
            ->orderByDesc('active_deposits_sum')
            ->limit(6)
            ->get();

        // ── 6. Recent Activity (Branch-Scoped Audit Logs) ──────────────────────
        $recentActivity = AuditLog::where('branch_id', $branchId)
            ->latest()
            ->limit(6)
            ->get();

        return view('branch-admin.dashboard', compact(
            'stats',
            'collectionSummary',
            'depositSummary',
            'monthlyTrends',
            'twelveMonthTrends',
            'portfolioByType',
            'treasury',
            'commissionPipeline',
            'maturityHorizon',
            'leadFunnel',
            'mobilizationGoal',
            'currentFy',
            'recentDeposits',
            'agentPerformance',
            'recentActivity'
        ));
    }
}
