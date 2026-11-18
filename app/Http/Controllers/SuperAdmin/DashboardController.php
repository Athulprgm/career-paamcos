<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\PromotionRequest;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardAnalyticsService $analytics
    ) {}

    /**
     * Display the executive fintech banking dashboard for the Super Administrator.
     */
    public function index(): View
    {
        $today = Carbon::today()->toDateString();

        // ─── 1. Primary Operational & Financial Indicators ──────────────────────
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', Customer::STATUS_ACTIVE)->count();

        $totalDeposits = Deposit::count();
        $activeDeposits = Deposit::where('status', Deposit::STATUS_ACTIVE)->count();
        $pendingDepositsCount = Deposit::where('status', Deposit::STATUS_PENDING_APPROVAL)->count();
        $totalDepositAmount = (float) Deposit::whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_MATURED])->sum('amount');

        $todayCollectionsCount = DepositCollection::whereDate('due_date', $today)->count();
        $todayCollectionsAmount = (float) DepositCollection::whereDate('due_date', $today)->sum('due_amount');
        $overdueCollectionsCount = DepositCollection::where('status', DepositCollection::STATUS_OVERDUE)->count();
        $totalCollectionsPaidAmount = (float) DepositCollection::where('status', DepositCollection::STATUS_PAID)->sum('paid_amount');

        $pendingCommissionsCount = Commission::where('status', Commission::STATUS_PENDING)->count();
        $pendingCommissionsAmount = (float) Commission::where('status', Commission::STATUS_PENDING)->sum('commission_amount');
        $paidCommissionsAmount = (float) Commission::where('status', Commission::STATUS_PAID)->sum('commission_amount');

        $pendingAgentsCount = Agent::where('status', Agent::STATUS_PENDING)->count();
        $pendingPromotionsCount = PromotionRequest::where('status', PromotionRequest::STATUS_PENDING_REVIEW)->count();

        $stats = [
            'total_branches' => Branch::count(),
            'active_branches' => Branch::active()->count(),
            'branch_admins' => User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                ->where(function ($q) {
                    $q->whereDoesntHave('branch', function ($bq) {
                        $bq->whereColumn('branches.email', 'users.email')
                            ->orWhereRaw('LOWER(users.name) = LOWER(CONCAT(branches.name, " Admin"))');
                    })
                        ->where('users.email', 'not like', '%@branch.multicred.com');
                })
                ->count(),
            'staff_positions' => StaffPosition::count(),
            'agent_ranks' => AgentRank::count(),
            'total_staff' => Staff::count(),
            'total_agents' => Agent::count(),
            'active_agents' => Agent::where('status', Agent::STATUS_ACTIVE)->count(),
            'pending_agents_count' => $pendingAgentsCount,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'total_deposits' => $totalDeposits,
            'active_deposits' => $activeDeposits,
            'pending_deposits_count' => $pendingDepositsCount,
            'total_deposit_amount' => $totalDepositAmount,
            'today_collections_count' => $todayCollectionsCount,
            'today_collections_amount' => $todayCollectionsAmount,
            'overdue_collections_count' => $overdueCollectionsCount,
            'total_collections_paid_amount' => $totalCollectionsPaidAmount,
            'pending_commissions_count' => $pendingCommissionsCount,
            'pending_commissions_amount' => $pendingCommissionsAmount,
            'paid_commissions_amount' => $paidCommissionsAmount,
            'pending_promotions_count' => $pendingPromotionsCount,
            'total_schemes_count' => DepositScheme::count(),
            'active_schemes_count' => DepositScheme::where('status', DepositScheme::STATUS_ACTIVE)->count(),
            'fd_schemes_count' => DepositScheme::where('scheme_type', DepositScheme::TYPE_FD)->count(),
            'active_fd_schemes_count' => DepositScheme::where('scheme_type', DepositScheme::TYPE_FD)->where('status', DepositScheme::STATUS_ACTIVE)->count(),
            'rd_schemes_count' => DepositScheme::where('scheme_type', DepositScheme::TYPE_RD)->count(),
            'dd_schemes_count' => DepositScheme::where('scheme_type', DepositScheme::TYPE_DD)->count(),
        ];

        // ─── 2. Real Core Banking Treasury & Analytics Telemetry ────────────────
        $twelveMonthTrends = $this->analytics->getMonthlyCashflowTrends(null, 12);
        $monthlyTrends = array_slice($twelveMonthTrends, -6);
        $portfolioByType = $this->analytics->getPortfolioByScheme();
        $treasury = $this->analytics->getTreasurySummary();
        $commissionPipeline = $this->analytics->getCommissionPipeline();
        $maturityHorizon = $this->analytics->getMaturityHorizon();
        $leadFunnel = $this->analytics->getLeadFunnel();
        $mobilizationGoal = $this->analytics->getMobilizationGoal();
        $currentFy = $this->analytics->getCurrentFinancialYear();

        // ─── 3. Collection Performance Status Breakdown ─────────────────────────
        $collectionBreakdown = DepositCollection::selectRaw('status, COUNT(*) as count, SUM(paid_amount) as total_paid, SUM(due_amount) as total_due')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $paidCount = (int) ($collectionBreakdown[DepositCollection::STATUS_PAID]->count ?? 0);
        $dueCount = (int) ($collectionBreakdown[DepositCollection::STATUS_DUE]->count ?? 0);
        $overdueCount = (int) ($collectionBreakdown[DepositCollection::STATUS_OVERDUE]->count ?? 0);
        $partialCount = (int) ($collectionBreakdown[DepositCollection::STATUS_PARTIAL]->count ?? 0);
        $totalCollectionTracked = $paidCount + $dueCount + $overdueCount + $partialCount;

        $recoveryRate = $totalCollectionTracked > 0
            ? round(($paidCount / $totalCollectionTracked) * 100, 1)
            : 100.0;

        // ─── 4. Top Performing Field Agents ─────────────────────────────────────
        $topAgents = Agent::with(['branch:id,name,code', 'rank:id,name,level', 'user.roles'])
            ->withCount(['deposits', 'customers'])
            ->withSum(['deposits' => function ($q) {
                $q->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_MATURED]);
            }], 'amount')
            ->orderByDesc('deposits_sum_amount')
            ->take(6)
            ->get();

        // ─── 5. Recent Deposits & Pending Approval Queue ────────────────────────
        $recentDeposits = Deposit::with([
            'customer:id,name,customer_number',
            'branch:id,name,code',
            'scheme:id,name,scheme_type',
            'agent:id,name,agent_id',
        ])
            ->latest()
            ->take(7)
            ->get();

        $pendingApprovalDeposits = Deposit::with([
            'customer:id,name,customer_number',
            'branch:id,name,code',
            'scheme:id,name,scheme_type',
        ])
            ->where('status', Deposit::STATUS_PENDING_APPROVAL)
            ->latest()
            ->take(5)
            ->get();

        // ─── 6. Branch Network & Capital Leaderboard ────────────────────────────
        $branches = Branch::withCount(['staff', 'agents', 'customers', 'deposits'])
            ->withSum(['deposits' => function ($q) {
                $q->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_MATURED]);
            }], 'amount')
            ->with(['admins' => function ($q) {
                $q->select('id', 'name', 'email', 'branch_id')
                    ->orderByRaw("CASE WHEN email LIKE '%@branch.multicred.com' OR name LIKE '% Admin' THEN 1 ELSE 0 END, id ASC");
            }])
            ->orderByDesc('deposits_sum_amount')
            ->latest()
            ->take(6)
            ->get();

        $recent_branches = $branches;

        // ─── 7. Immutable Audit Trail Snapshot ──────────────────────────────────
        $recentAudits = AuditLog::with(['user:id,name', 'branch:id,name,code'])
            ->latest()
            ->take(5)
            ->get();

        return view('superadmin.dashboard', compact(
            'stats',
            'monthlyTrends',
            'twelveMonthTrends',
            'portfolioByType',
            'treasury',
            'commissionPipeline',
            'maturityHorizon',
            'leadFunnel',
            'mobilizationGoal',
            'currentFy',
            'collectionBreakdown',
            'recoveryRate',
            'topAgents',
            'recentDeposits',
            'pendingApprovalDeposits',
            'branches',
            'recent_branches',
            'recentAudits'
        ));
    }
}
