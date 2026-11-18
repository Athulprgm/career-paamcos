<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\PromotionRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        protected CareerPerformanceService $careerPerformanceService
    ) {}

    /**
     * Scope query by restricted branch and agent IDs.
     */
    protected function applyScope(
        Builder $query,
        ?int $restrictedBranchId,
        ?int $restrictedAgentId,
        string $branchColumn = 'branch_id',
        string $agentColumn = 'agent_id'
    ): Builder {
        if ($restrictedBranchId !== null) {
            $query->where($branchColumn, $restrictedBranchId);
        }

        if ($restrictedAgentId !== null) {
            $query->where($agentColumn, $restrictedAgentId);
        }

        return $query;
    }

    /**
     * 1. Overview Dashboard Data
     */
    public function getOverviewData(array $filters = [], ?int $restrictedBranchId = null, ?int $restrictedAgentId = null): array
    {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        // Base Queries with Scopes
        $customerQuery = Customer::query();
        $this->applyScope($customerQuery, $branchId, $agentId);

        $depositQuery = Deposit::query();
        $this->applyScope($depositQuery, $branchId, $agentId);

        $collectionQuery = DepositCollection::query();
        $this->applyScope($collectionQuery, $branchId, $agentId);

        $commissionQuery = Commission::query();
        $this->applyScope($commissionQuery, $branchId, $agentId);

        $agentQuery = Agent::query();
        if ($branchId) {
            $agentQuery->where('branch_id', $branchId);
        }
        if ($agentId) {
            $agentQuery->where('id', $agentId);
        }

        $promotionQuery = PromotionRequest::query();
        if ($branchId) {
            $promotionQuery->where('branch_id', $branchId);
        }
        if ($agentId) {
            $promotionQuery->where('agent_id', $agentId);
        }

        // Apply Date Filters where applicable
        if ($dateFrom) {
            $depositQuery->whereDate('start_date', '>=', $dateFrom);
            $collectionQuery->whereDate('due_date', '>=', $dateFrom);
            $commissionQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $depositQuery->whereDate('start_date', '<=', $dateTo);
            $collectionQuery->whereDate('due_date', '<=', $dateTo);
            $commissionQuery->whereDate('created_at', '<=', $dateTo);
        }

        // Metrics: Customers
        $totalCustomers = (clone $customerQuery)->count();
        $activeCustomers = (clone $customerQuery)->where('status', Customer::STATUS_ACTIVE)->count();

        // Metrics: Deposits
        $totalDeposits = (clone $depositQuery)->count();
        $activeDeposits = (clone $depositQuery)->where('status', Deposit::STATUS_ACTIVE)->count();
        $totalDepositValue = (float) (clone $depositQuery)->where('status', '!=', Deposit::STATUS_REJECTED)->sum('amount');
        $activeDepositValue = (float) (clone $depositQuery)->where('status', Deposit::STATUS_ACTIVE)->sum('amount');

        // Metrics: Collections
        $totalDueCollection = (float) (clone $collectionQuery)->where('status', '!=', DepositCollection::STATUS_CANCELLED)->sum('due_amount');
        $totalCollected = (float) (clone $collectionQuery)->sum('paid_amount');
        $outstandingCollection = max(0.0, $totalDueCollection - $totalCollected);

        // Metrics: Commission
        $totalCommission = (float) (clone $commissionQuery)->where('status', '!=', Commission::STATUS_REJECTED)->sum('commission_amount');
        $paidCommission = (float) (clone $commissionQuery)->where('status', Commission::STATUS_PAID)->sum('commission_amount');
        $pendingCommission = (float) (clone $commissionQuery)->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED, Commission::STATUS_PAYABLE])->sum('commission_amount');

        // Metrics: Agents & Career
        $totalAgents = (clone $agentQuery)->count();
        $activeAgents = (clone $agentQuery)->whereIn('status', ['active', 'ACTIVE'])->count();
        $pendingPromotions = (clone $promotionQuery)->where('status', PromotionRequest::STATUS_PENDING_REVIEW)->count();

        // Branch Breakdown (if Super Admin or unrestricted)
        $branchBreakdown = [];
        if ($restrictedBranchId === null) {
            $branches = Branch::where('is_active', true)->get();
            foreach ($branches as $branch) {
                $bDepCount = Deposit::where('branch_id', $branch->id)->where('status', Deposit::STATUS_ACTIVE)->count();
                $bDepVal = (float) Deposit::where('branch_id', $branch->id)->where('status', Deposit::STATUS_ACTIVE)->sum('amount');
                $bCollVal = (float) DepositCollection::where('branch_id', $branch->id)->sum('paid_amount');
                $bCustCount = Customer::where('branch_id', $branch->id)->count();

                $branchBreakdown[] = [
                    'branch' => $branch,
                    'active_deposits' => $bDepCount,
                    'deposit_value' => $bDepVal,
                    'collections_collected' => $bCollVal,
                    'customers' => $bCustCount,
                ];
            }
        }

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'total_deposits' => $totalDeposits,
            'active_deposits' => $activeDeposits,
            'total_deposit_value' => $totalDepositValue,
            'active_deposit_value' => $activeDepositValue,
            'total_due_collection' => $totalDueCollection,
            'total_collected' => $totalCollected,
            'outstanding_collection' => $outstandingCollection,
            'total_commission' => $totalCommission,
            'paid_commission' => $paidCommission,
            'pending_commission' => $pendingCommission,
            'total_agents' => $totalAgents,
            'active_agents' => $activeAgents,
            'pending_promotions' => $pendingPromotions,
            'branch_breakdown' => $branchBreakdown,
        ];
    }

    /**
     * 2. Deposit Report Data
     */
    public function getDepositReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);

        $query = Deposit::with(['customer', 'agent', 'branch', 'scheme']);
        $this->applyScope($query, $branchId, $agentId);

        // Filters
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['deposit_scheme_id'])) {
            $query->where('deposit_scheme_id', $filters['deposit_scheme_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('start_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('start_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('deposit_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('customer_number', 'like', "%{$search}%"));
            });
        }

        // Summary Calculations matching filtered records
        $summaryQuery = clone $query;
        $totalCount = (clone $summaryQuery)->count();
        $totalAmount = (float) (clone $summaryQuery)->sum('amount');
        $activeCount = (clone $summaryQuery)->where('status', Deposit::STATUS_ACTIVE)->count();
        $activeAmount = (float) (clone $summaryQuery)->where('status', Deposit::STATUS_ACTIVE)->sum('amount');
        $maturedCount = (clone $summaryQuery)->where('status', Deposit::STATUS_MATURED)->count();
        $closedCount = (clone $summaryQuery)->where('status', Deposit::STATUS_CLOSED)->count();
        $pendingCount = (clone $summaryQuery)->whereIn('status', [Deposit::STATUS_PENDING_APPROVAL, Deposit::STATUS_DRAFT])->count();
        $rejectedCount = (clone $summaryQuery)->where('status', Deposit::STATUS_REJECTED)->count();

        $query->orderBy('start_date', 'desc')->orderBy('id', 'desc');

        $records = $forExport ? $query->get() : $query->paginate($perPage)->withQueryString();

        return [
            'records' => $records,
            'summary' => [
                'total_count' => $totalCount,
                'total_amount' => $totalAmount,
                'active_count' => $activeCount,
                'active_amount' => $activeAmount,
                'matured_count' => $maturedCount,
                'closed_count' => $closedCount,
                'pending_count' => $pendingCount,
                'rejected_count' => $rejectedCount,
            ],
        ];
    }

    /**
     * 3. Collection Report Data
     */
    public function getCollectionReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);

        $query = DepositCollection::with(['deposit', 'customer', 'agent', 'branch']);
        $this->applyScope($query, $branchId, $agentId);

        // Filters
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['deposit_id'])) {
            $query->where('deposit_id', $filters['deposit_id']);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'OVERDUE') {
                $query->where('due_date', '<', Carbon::today())
                    ->whereColumn('paid_amount', '<', 'due_amount')
                    ->where('status', '!=', DepositCollection::STATUS_CANCELLED);
            } else {
                $query->where('status', $filters['status']);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('due_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('due_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['collection_date_from'])) {
            $query->whereDate('collection_date', '>=', $filters['collection_date_from']);
        }
        if (! empty($filters['collection_date_to'])) {
            $query->whereDate('collection_date', '<=', $filters['collection_date_to']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('collection_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('customer_number', 'like', "%{$search}%"))
                    ->orWhereHas('deposit', fn ($dq) => $dq->where('deposit_number', 'like', "%{$search}%"));
            });
        }

        // Summary calculations
        $summaryQuery = clone $query;
        $totalDue = (float) (clone $summaryQuery)->where('status', '!=', DepositCollection::STATUS_CANCELLED)->sum('due_amount');
        $totalCollected = (float) (clone $summaryQuery)->sum('paid_amount');
        $outstanding = max(0.0, $totalDue - $totalCollected);
        $paidCount = (clone $summaryQuery)->where('status', DepositCollection::STATUS_PAID)->count();
        $partialCount = (clone $summaryQuery)->where('status', DepositCollection::STATUS_PARTIAL)->count();
        $dueCount = (clone $summaryQuery)->where('status', DepositCollection::STATUS_DUE)->count();
        $overdueCount = (clone $summaryQuery)
            ->where('due_date', '<', Carbon::today())
            ->whereColumn('paid_amount', '<', 'due_amount')
            ->where('status', '!=', DepositCollection::STATUS_CANCELLED)
            ->count();

        $query->orderBy('due_date', 'desc')->orderBy('id', 'desc');

        $records = $forExport ? $query->get() : $query->paginate($perPage)->withQueryString();

        return [
            'records' => $records,
            'summary' => [
                'total_due' => $totalDue,
                'total_collected' => $totalCollected,
                'outstanding' => $outstanding,
                'paid_count' => $paidCount,
                'partial_count' => $partialCount,
                'due_count' => $dueCount,
                'overdue_count' => $overdueCount,
            ],
        ];
    }

    /**
     * 4. Commission Report Data
     */
    public function getCommissionReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);

        $query = Commission::with(['agent', 'customer', 'deposit', 'collection', 'branch']);
        $this->applyScope($query, $branchId, $agentId);

        // Filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('commission_number', 'like', "%{$search}%")
                    ->orWhere('commission_id', 'like', "%{$search}%")
                    ->orWhereHas('agent', fn ($aq) => $aq->where('name', 'like', "%{$search}%")->orWhere('agent_code', 'like', "%{$search}%"))
                    ->orWhereHas('deposit', fn ($dq) => $dq->where('deposit_number', 'like', "%{$search}%"));
            });
        }

        // Summaries
        $summaryQuery = clone $query;
        $totalCommission = (float) (clone $summaryQuery)->where('status', '!=', Commission::STATUS_REJECTED)->sum('commission_amount');
        $pendingCommission = (float) (clone $summaryQuery)->where('status', Commission::STATUS_PENDING)->sum('commission_amount');
        $approvedCommission = (float) (clone $summaryQuery)->where('status', Commission::STATUS_APPROVED)->sum('commission_amount');
        $payableCommission = (float) (clone $summaryQuery)->where('status', Commission::STATUS_PAYABLE)->sum('commission_amount');
        $paidCommission = (float) (clone $summaryQuery)->where('status', Commission::STATUS_PAID)->sum('commission_amount');
        $rejectedCommission = (float) (clone $summaryQuery)->where('status', Commission::STATUS_REJECTED)->sum('commission_amount');

        $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        $records = $forExport ? $query->get() : $query->paginate($perPage)->withQueryString();

        return [
            'records' => $records,
            'summary' => [
                'total_commission' => $totalCommission,
                'pending_commission' => $pendingCommission,
                'approved_commission' => $approvedCommission,
                'payable_commission' => $payableCommission,
                'paid_commission' => $paidCommission,
                'rejected_commission' => $rejectedCommission,
            ],
        ];
    }

    /**
     * 5. Agent Performance Report Data (Reuses Phase 7 CareerPerformanceService)
     */
    public function getAgentPerformanceReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);
        $period = $filters['period'] ?? null;

        $agentQuery = Agent::with(['branch', 'careerLevel']);

        if ($branchId) {
            $agentQuery->where('branch_id', $branchId);
        }
        if ($agentId) {
            $agentQuery->where('id', $agentId);
        }
        if (! empty($filters['career_level_id'])) {
            $agentQuery->where('rank_id', $filters['career_level_id']);
        }
        if (! empty($filters['status'])) {
            $agentQuery->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $agentQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('agent_code', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $allMatchingAgents = (clone $agentQuery)->orderBy('name')->get();

        // Calculate metrics using Phase 7 service for each agent
        $performanceList = $allMatchingAgents->map(function (Agent $agent) use ($period) {
            $metrics = $this->careerPerformanceService->calculateAgentPerformance($agent, $period);

            return [
                'agent' => $agent,
                'active_customers' => $metrics['active_customers'],
                'total_customers' => $metrics['total_customers'],
                'deposit_count' => $metrics['deposit_count'],
                'deposit_amount' => $metrics['deposit_amount'],
                'collection_count' => $metrics['collection_count'],
                'collection_amount' => $metrics['collection_amount'],
                'commission_count' => $metrics['commission_count'],
                'commission_amount' => $metrics['commission_amount'],
                'performance_score' => $metrics['performance_score'],
                'career_level' => $agent->careerLevel?->name ?? 'Standard Agent',
                'career_level_code' => $agent->careerLevel?->code ?? '-',
            ];
        });

        // Summary Calculations
        $totalAgentsCount = $performanceList->count();
        $totalActiveCustomers = $performanceList->sum('active_customers');
        $totalDepositCount = $performanceList->sum('deposit_count');
        $totalDepositValue = $performanceList->sum('deposit_amount');
        $totalCollectionCount = $performanceList->sum('collection_count');
        $totalCollectionValue = $performanceList->sum('collection_amount');
        $totalCommissionValue = $performanceList->sum('commission_amount');
        $avgScore = $totalAgentsCount > 0 ? round($performanceList->avg('performance_score'), 2) : 0;

        // Paginate collection in memory if not exporting
        if ($forExport) {
            $records = $performanceList;
        } else {
            $page = LengthAwarePaginator::resolveCurrentPage();
            $itemsForCurrentPage = $performanceList->slice(($page - 1) * $perPage, $perPage)->values();
            $records = new LengthAwarePaginator(
                $itemsForCurrentPage,
                $totalAgentsCount,
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
            );
        }

        return [
            'records' => $records,
            'summary' => [
                'total_agents' => $totalAgentsCount,
                'total_active_customers' => $totalActiveCustomers,
                'total_deposits' => $totalDepositCount,
                'total_deposit_value' => $totalDepositValue,
                'total_collections' => $totalCollectionCount,
                'total_collection_value' => $totalCollectionValue,
                'total_commission' => $totalCommissionValue,
                'avg_performance_score' => $avgScore,
            ],
        ];
    }

    /**
     * 6. Customer Report Data
     */
    public function getCustomerReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);

        $query = Customer::with(['branch', 'agent'])
            ->withCount([
                'deposits as total_deposits_count',
                'deposits as active_deposits_count' => fn ($q) => $q->where('status', Deposit::STATUS_ACTIVE),
            ])
            ->withSum([
                'deposits as total_deposits_sum' => fn ($q) => $q->where('status', '!=', Deposit::STATUS_REJECTED),
            ], 'amount');

        $this->applyScope($query, $branchId, $agentId);

        // Filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_number', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Summary Calculations
        $summaryQuery = clone $query;
        $totalCustomers = (clone $summaryQuery)->count();
        $activeCustomers = (clone $summaryQuery)->where('status', Customer::STATUS_ACTIVE)->count();

        $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        $records = $forExport ? $query->get() : $query->paginate($perPage)->withQueryString();

        return [
            'records' => $records,
            'summary' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'kyc_approved' => 0,
                'kyc_pending' => 0,
                'kyc_under_review' => 0,
                'kyc_rejected' => 0,
            ],
        ];
    }

    /**
     * 7. Career Report Data
     */
    public function getCareerReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $branchId = $restrictedBranchId ?? ($filters['branch_id'] ?? null);
        $agentId = $restrictedAgentId ?? ($filters['agent_id'] ?? null);

        // 1. Agents Breakdown across ladder
        $levels = CareerLevel::ordered()->get();
        $ladderBreakdown = [];

        foreach ($levels as $lvl) {
            $lvlAgentQuery = Agent::where('rank_id', $lvl->id);
            if ($branchId) {
                $lvlAgentQuery->where('branch_id', $branchId);
            }
            if ($agentId) {
                $lvlAgentQuery->where('id', $agentId);
            }

            $countTotal = (clone $lvlAgentQuery)->count();
            $countActive = (clone $lvlAgentQuery)->whereIn('status', ['active', 'ACTIVE'])->count();

            $ladderBreakdown[] = [
                'level' => $lvl,
                'total_agents' => $countTotal,
                'active_agents' => $countActive,
            ];
        }

        // 2. Promotion Requests Query
        $reqQuery = PromotionRequest::with(['agent', 'branch', 'currentLevel', 'proposedLevel', 'requester', 'reviewer']);
        if ($branchId) {
            $reqQuery->where('branch_id', $branchId);
        }
        if ($agentId) {
            $reqQuery->where('agent_id', $agentId);
        }
        if (! empty($filters['status'])) {
            $reqQuery->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $reqQuery->whereDate('request_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $reqQuery->whereDate('request_date', '<=', $filters['date_to']);
        }

        $totalRequests = (clone $reqQuery)->count();
        $pendingRequests = (clone $reqQuery)->where('status', PromotionRequest::STATUS_PENDING_REVIEW)->count();
        $approvedRequests = (clone $reqQuery)->whereIn('status', [PromotionRequest::STATUS_APPROVED, PromotionRequest::STATUS_PROMOTED])->count();
        $rejectedRequests = (clone $reqQuery)->where('status', PromotionRequest::STATUS_REJECTED)->count();

        // 3. Historical Promotions Count
        $histQuery = CareerHistory::with(['agent', 'previousLevel', 'newLevel', 'approver']);
        if ($branchId) {
            $histQuery->whereHas('agent', fn ($q) => $q->where('branch_id', $branchId));
        }
        if ($agentId) {
            $histQuery->where('agent_id', $agentId);
        }
        $totalHistoricalPromotions = (clone $histQuery)->where('action', 'PROMOTION')->count();

        // Calculate count of eligible candidates in current scope
        $eligibleCount = 0;
        $evalAgentQuery = Agent::whereIn('status', ['active', 'ACTIVE']);
        if ($branchId) {
            $evalAgentQuery->where('branch_id', $branchId);
        }
        if ($agentId) {
            $evalAgentQuery->where('id', $agentId);
        }
        $candidates = $evalAgentQuery->get();
        foreach ($candidates as $cand) {
            $nextLvl = $cand->careerLevel?->nextLevel();
            if ($nextLvl) {
                $eval = $this->careerPerformanceService->checkEligibility($cand, $nextLvl);
                if ($eval['is_eligible']) {
                    $eligibleCount++;
                }
            }
        }

        $reqQuery->orderBy('request_date', 'desc')->orderBy('id', 'desc');

        $records = $forExport ? $reqQuery->get() : $reqQuery->paginate($perPage)->withQueryString();

        return [
            'records' => $records,
            'ladder_breakdown' => $ladderBreakdown,
            'summary' => [
                'total_requests' => $totalRequests,
                'pending_requests' => $pendingRequests,
                'approved_requests' => $approvedRequests,
                'rejected_requests' => $rejectedRequests,
                'historical_promotions' => $totalHistoricalPromotions,
                'eligible_agents_count' => $eligibleCount,
            ],
        ];
    }

    /**
     * 8. Audit Report Data
     */
    public function getAuditReportData(
        array $filters = [],
        ?int $restrictedBranchId = null,
        ?int $restrictedAgentId = null,
        int $perPage = 25,
        bool $forExport = false
    ): array {
        $query = AuditLog::with('user');

        // If restricted to branch, filter audits performed by users belonging to that branch
        if ($restrictedBranchId !== null) {
            $query->whereHas('user', fn ($uq) => $uq->where('branch_id', $restrictedBranchId));
        }

        // If restricted to agent, filter audits for this agent's user
        if ($restrictedAgentId !== null) {
            $agent = Agent::find($restrictedAgentId);
            if ($agent && $agent->user_id) {
                $query->where('user_id', $agent->user_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filters
        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }
        if (! empty($filters['auditable_type'])) {
            $query->where('auditable_type', 'like', "%{$filters['auditable_type']}%");
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $summaryQuery = clone $query;
        $totalEvents = (clone $summaryQuery)->count();
        $financialEvents = (clone $summaryQuery)->whereNotNull('financial_amount')->where('financial_amount', '>', 0)->count();
        $totalFinancialAmount = (float) (clone $summaryQuery)->sum('financial_amount');
        $uniqueUsersCount = (clone $summaryQuery)->distinct('user_id')->count('user_id');

        $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        $records = $forExport ? $query->get() : $query->paginate($perPage)->withQueryString();

        return [
            'records' => $records,
            'summary' => [
                'total_events' => $totalEvents,
                'financial_events' => $financialEvents,
                'total_financial_amount' => $totalFinancialAmount,
                'unique_users_count' => $uniqueUsersCount,
            ],
        ];
    }
}
