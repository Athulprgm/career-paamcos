<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommissionController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService
    ) {}

    /**
     * Get branch restriction for current user.
     * Null means user is Super Admin (unrestricted).
     */
    protected function getRestrictedBranchId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            return null;
        }
        if ($user && $user->isBranchAdmin()) {
            return $user->branch_id;
        }
        if ($user && $user->isAgent()) {
            return $user->agentProfile?->branch_id ?? $user->branch_id;
        }

        return $user ? ($user->branch_id ?? $user->staffProfile?->branch_id) : null;
    }

    /**
     * Get agent restriction for current user (null for non-agent).
     */
    protected function getRestrictedAgentId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            return $user->agentProfile?->id;
        }

        return null;
    }

    /**
     * Authorize commission view access based on branch and agent scope.
     */
    protected function authorizeCommissionAccess(Commission $commission): void
    {
        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions'))) {
            abort(403, 'Access denied: View commissions privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        // Cross-branch access is strictly forbidden
        if ($restrictedBranchId !== null && (int) $commission->branch_id !== (int) $restrictedBranchId) {
            abort(403, 'Unauthorized access: Cross-branch commission access is strictly prohibited.');
        }

        // Agent restriction: agents can only view their own commissions
        if ($restrictedAgentId !== null && (int) $commission->agent_id !== (int) $restrictedAgentId) {
            abort(403, 'Unauthorized access: You are only permitted to view your own commission records.');
        }
    }

    /**
     * Commission Dashboard Overview showing real database metrics and filtered data.
     */
    public function overview(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions'))) {
            abort(403, 'Access denied: View commissions privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = Commission::query();

        // Branch scope
        if ($restrictedBranchId !== null) {
            $query->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Agent scope
        if ($restrictedAgentId !== null) {
            $query->where('agent_id', $restrictedAgentId);
        } elseif ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        // Scheme filter
        if ($request->filled('scheme_id')) {
            $query->byScheme($request->scheme_id);
        }

        // Period filter
        if ($request->filled('period')) {
            $query->byPeriod($request->period);
        }

        // Real Database Values
        $pendingQuery = (clone $query)->where('status', Commission::STATUS_PENDING);
        $pendingCount = (clone $pendingQuery)->count();
        $pendingAmount = (float) (clone $pendingQuery)->sum('commission_amount');

        $approvedQuery = (clone $query)->where('status', Commission::STATUS_APPROVED);
        $approvedCount = (clone $approvedQuery)->count();
        $approvedAmount = (float) (clone $approvedQuery)->sum('commission_amount');

        $payableQuery = (clone $query)->where('status', Commission::STATUS_PAYABLE);
        $payableCount = (clone $payableQuery)->count();
        $payableAmount = (float) (clone $payableQuery)->sum('commission_amount');

        $paidQuery = (clone $query)->where('status', Commission::STATUS_PAID);
        $paidCount = (clone $paidQuery)->count();
        $paidAmount = (float) (clone $paidQuery)->sum('commission_amount');

        $currentPeriod = now()->format('Y-m');
        $currentPeriodQuery = (clone $query)->where('period', $currentPeriod);
        $currentPeriodAmount = (float) (clone $currentPeriodQuery)->sum('commission_amount');
        $currentPeriodCount = (clone $currentPeriodQuery)->count();

        $totalCommissionAmount = (float) (clone $query)->where('status', '!=', Commission::STATUS_REJECTED)->sum('commission_amount');

        // Recent Commissions
        $recentCommissions = (clone $query)->with(['agent', 'customer', 'deposit', 'collection', 'rule'])
            ->latest()
            ->limit(10)
            ->get();

        // Branches, Agents, Schemes for filter
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $agentQuery = Agent::active()->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where('branch_id', $restrictedBranchId);
        }
        $agents = $agentQuery->get();

        $schemes = DepositScheme::orderBy('name')->get();

        // Distinct periods from database
        $periods = Commission::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');

        return view('admin.commissions.overview', compact(
            'pendingCount',
            'pendingAmount',
            'approvedCount',
            'approvedAmount',
            'payableCount',
            'payableAmount',
            'paidCount',
            'paidAmount',
            'currentPeriod',
            'currentPeriodAmount',
            'currentPeriodCount',
            'totalCommissionAmount',
            'recentCommissions',
            'branches',
            'agents',
            'schemes',
            'periods',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * All Commissions list.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions'))) {
            abort(403, 'Access denied: View commissions privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = Commission::with([
            'agent.careerLevel',
            'branch',
            'customer',
            'deposit.scheme',
            'deposit.agent.careerLevel',
            'deposit.customer',
            'deposit.branch',
            'collection',
            'rule',
        ]);

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
            $query->byStatus($request->status);
        }

        if ($request->filled('period')) {
            $query->byPeriod($request->period);
        }

        if ($request->filled('scheme_id')) {
            $query->byScheme($request->scheme_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $commissions = $query->orderByRaw('COALESCE(deposit_id, 0) DESC, payout_year ASC, id DESC')
            ->paginate(30)
            ->withQueryString();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $agentQuery = Agent::active()->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where('branch_id', $restrictedBranchId);
        }
        $agents = $agentQuery->get();

        $schemes = DepositScheme::orderBy('name')->get();
        $periods = Commission::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');

        // Calculate KPI Metrics and Status Counts (scoped by restrictions)
        $statsBase = Commission::query();
        if ($restrictedBranchId !== null) {
            $statsBase->where('branch_id', $restrictedBranchId);
        }
        if ($restrictedAgentId !== null) {
            $statsBase->where('agent_id', $restrictedAgentId);
        }

        $statusRows = (clone $statsBase)
            ->selectRaw('status, COUNT(*) as cnt, SUM(COALESCE(commission_amount, 0)) as total_sum')
            ->groupBy('status')
            ->get();

        $statusCounts = $statusRows->pluck('cnt', 'status')->map(fn ($v) => (int) $v)->toArray();
        $statusAmounts = $statusRows->pluck('total_sum', 'status')->map(fn ($v) => (float) $v)->toArray();

        $kpis = [
            'total_count' => array_sum($statusCounts),
            'total_amount' => (float) (clone $statsBase)->where('status', '!=', Commission::STATUS_REJECTED)->sum('commission_amount'),
            'pending_count' => (int) ($statusCounts[Commission::STATUS_PENDING] ?? 0),
            'pending_amount' => (float) ($statusAmounts[Commission::STATUS_PENDING] ?? 0),
            'approved_count' => (int) ($statusCounts[Commission::STATUS_APPROVED] ?? 0),
            'approved_amount' => (float) ($statusAmounts[Commission::STATUS_APPROVED] ?? 0),
            'payable_count' => (int) ($statusCounts[Commission::STATUS_PAYABLE] ?? 0),
            'payable_amount' => (float) ($statusAmounts[Commission::STATUS_PAYABLE] ?? 0),
            'paid_count' => (int) ($statusCounts[Commission::STATUS_PAID] ?? 0),
            'paid_amount' => (float) ($statusAmounts[Commission::STATUS_PAID] ?? 0),
            'rejected_count' => (int) ($statusCounts[Commission::STATUS_REJECTED] ?? 0),
            'rejected_amount' => (float) ($statusAmounts[Commission::STATUS_REJECTED] ?? 0),
        ];

        return view('admin.commissions.index', compact(
            'commissions',
            'branches',
            'agents',
            'schemes',
            'periods',
            'restrictedBranchId',
            'restrictedAgentId',
            'kpis',
            'statusCounts'
        ));
    }

    /**
     * Pending commissions view.
     */
    public function pending(Request $request)
    {
        $request->merge(['status' => Commission::STATUS_PENDING]);

        return $this->filteredListView($request, Commission::STATUS_PENDING, 'Pending Commissions');
    }

    /**
     * Approved commissions view.
     */
    public function approved(Request $request)
    {
        $request->merge(['status' => Commission::STATUS_APPROVED]);

        return $this->filteredListView($request, Commission::STATUS_APPROVED, 'Approved Commissions');
    }

    /**
     * Payable commissions view.
     */
    public function payable(Request $request)
    {
        $request->merge(['status' => Commission::STATUS_PAYABLE]);

        return $this->filteredListView($request, Commission::STATUS_PAYABLE, 'Payable Commissions');
    }

    /**
     * Paid commissions view.
     */
    public function paid(Request $request)
    {
        $request->merge(['status' => Commission::STATUS_PAID]);

        return $this->filteredListView($request, Commission::STATUS_PAID, 'Paid Commissions');
    }

    /**
     * Commission History view.
     */
    public function history(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = Commission::with(['agent', 'branch', 'customer', 'deposit', 'collection', 'rule', 'payer', 'approver']);

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
            $query->byStatus($request->status);
        }

        if ($request->filled('period')) {
            $query->byPeriod($request->period);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $commissions = $query->latest('updated_at')->paginate(20)->withQueryString();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $agentQuery = Agent::active()->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where('branch_id', $restrictedBranchId);
        }
        $agents = $agentQuery->get();

        $periods = Commission::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');

        return view('admin.commissions.history', compact(
            'commissions',
            'branches',
            'agents',
            'periods',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * Helper for status-filtered list views.
     */
    protected function filteredListView(Request $request, string $status, string $pageTitle)
    {
        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-commissions') || $user->hasPermission('approve-commissions'))) {
            abort(403, 'Access denied: View commissions privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = Commission::with([
            'agent.careerLevel',
            'branch',
            'customer',
            'deposit.scheme',
            'deposit.agent.careerLevel',
            'deposit.customer',
            'deposit.branch',
            'collection',
            'rule',
        ])->where('status', $status);

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

        if ($request->filled('period')) {
            $query->byPeriod($request->period);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $commissions = $query->orderByRaw('COALESCE(deposit_id, 0) DESC, payout_year ASC, id DESC')
            ->paginate(30)
            ->withQueryString();

        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        $agentQuery = Agent::active()->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where('branch_id', $restrictedBranchId);
        }
        $agents = $agentQuery->get();

        $periods = Commission::select('period')->distinct()->orderBy('period', 'desc')->pluck('period');

        return view('admin.commissions.status-list', compact(
            'commissions',
            'branches',
            'agents',
            'periods',
            'status',
            'pageTitle',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * Show commission details.
     */
    public function show(Commission $commission)
    {
        $this->authorizeCommissionAccess($commission);

        $commission->load([
            'agent.rank',
            'branch',
            'customer',
            'deposit.scheme',
            'collection',
            'rule',
            'creator',
            'approver',
            'payer',
            'rejecter',
            'auditLogs.user',
        ]);

        return view('admin.commissions.show', compact('commission'));
    }

    /**
     * Approve commission: PENDING -> APPROVED.
     */
    public function approve(Commission $commission)
    {
        $this->authorizeCommissionAccess($commission);

        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('approve-commissions')) {
            abort(403, 'Access denied: Approve commissions privileges required.');
        }

        try {
            $this->commissionService->approve($commission, auth()->user());

            return redirect()->back()->with('success', "Commission [{$commission->commission_number}] has been APPROVED successfully.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject commission: PENDING -> REJECTED.
     */
    public function reject(Request $request, Commission $commission)
    {
        $this->authorizeCommissionAccess($commission);

        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('approve-commissions')) {
            abort(403, 'Access denied: Approve commissions privileges required.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->commissionService->reject($commission, $validated['rejection_reason'], auth()->user());

            return redirect()->back()->with('success', "Commission [{$commission->commission_number}] has been REJECTED.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark commission payable: APPROVED -> PAYABLE.
     */
    public function markPayable(Commission $commission)
    {
        $this->authorizeCommissionAccess($commission);

        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('approve-commissions')) {
            abort(403, 'Access denied: Approve commissions privileges required.');
        }

        try {
            $this->commissionService->markPayable($commission, auth()->user());

            return redirect()->back()->with('success', "Commission [{$commission->commission_number}] is now marked as PAYABLE.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show payout / payment form.
     */
    public function showPaymentForm(Commission $commission)
    {
        $this->authorizeCommissionAccess($commission);

        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('approve-commissions')) {
            abort(403, 'Access denied: Commission payment privileges required.');
        }

        // Agents cannot pay commissions!
        if (auth()->user()->isAgent() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized: Agents cannot process commission payments.');
        }

        if (! $commission->canBePaid()) {
            return redirect()->route('admin.commissions.show', $commission)
                ->with('error', "Only commissions in PAYABLE status can be paid. Current status: {$commission->status}.");
        }

        return view('admin.commissions.pay', compact('commission'));
    }

    /**
     * Process commission payment: PAYABLE -> PAID.
     */
    public function processPayment(Request $request, Commission $commission)
    {
        $this->authorizeCommissionAccess($commission);

        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('approve-commissions')) {
            abort(403, 'Access denied: Commission payment privileges required.');
        }

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->commissionService->pay($commission, $validated, auth()->user());

            return redirect()->route('admin.commissions.show', $commission)
                ->with('success', 'Payment of ₹'.number_format($commission->calculated_amount, 2)." recorded successfully for commission [{$commission->commission_number}].");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * On-demand calculation/generation of commission for an eligible collection.
     */
    public function generateForCollection(Request $request, DepositCollection $collection)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId !== null && (int) $collection->branch_id !== (int) $restrictedBranchId) {
            abort(403, 'Unauthorized cross-branch action.');
        }

        try {
            $commission = $this->commissionService->generateForCollection($collection, null, auth()->user());

            return redirect()->route('admin.commissions.show', $commission)
                ->with('success', "Commission [{$commission->commission_number}] generated successfully for collection [{$collection->collection_number}].");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->validator->errors()->first());
        }
    }
}
