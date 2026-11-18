<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\Lead;
use App\Services\CareerPerformanceService;
use App\Services\CommissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepositController extends Controller
{
    /**
     * Get branch restriction for the current user.
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
     * Authorize deposit access based on branch and agent scope.
     */
    protected function authorizeDepositAccess(Deposit $deposit): void
    {
        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-deposits') || $user->hasPermission('manage-deposits'))) {
            abort(403, 'Access denied: View deposits privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        // Agent restriction: agents can only view deposits belonging to their assigned customers
        if ($restrictedAgentId !== null) {
            if ((int) $deposit->agent_id !== (int) $restrictedAgentId) {
                abort(403, 'Unauthorized access: You can only view deposits belonging to your assigned customers.');
            }

            return;
        }

        // Cross-branch access is strictly forbidden
        if ($restrictedBranchId !== null && (int) $deposit->branch_id !== (int) $restrictedBranchId) {
            abort(403, 'Unauthorized access: You cannot view or modify a deposit from another branch.');
        }
    }

    /**
     * Authorize approval and administrative actions (agents cannot approve/reject/activate/mature/close).
     */
    protected function authorizeApprovalAction(Deposit $deposit): void
    {
        $this->authorizeDepositAccess($deposit);

        $user = auth()->user();
        if (! $user || ! ($user->isSuperAdmin() || $user->isBranchAdmin())) {
            abort(403, 'Unauthorized action: Only branch managers or administrators may approve, reject, or change deposit status.');
        }
    }

    /**
     * Display a listing of deposits.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-deposits') || $user->hasPermission('manage-deposits'))) {
            abort(403, 'Access denied: View deposits privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = Deposit::with(['branch', 'customer', 'agent', 'scheme', 'creator', 'approver']);

        // Agent restrictions
        if ($restrictedAgentId !== null) {
            $query->where('agent_id', $restrictedAgentId);
        } else {
            // Structural branch restrictions
            if ($restrictedBranchId !== null) {
                $query->where('branch_id', $restrictedBranchId);
            } elseif ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
        }

        // Search term
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Scheme filter
        if ($request->filled('scheme_id')) {
            $query->byScheme($request->scheme_id);
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->where('start_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('start_date', '<=', $request->to_date);
        }

        $deposits = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // Branches for dropdown
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        // Agents for dropdown
        $agentQuery = Agent::active()->with(['rank', 'careerLevel'])->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        $agents = $agentQuery->get();

        // Schemes for filter
        $schemes = DepositScheme::orderBy('name')->get();

        return view('admin.deposits.index', compact(
            'deposits',
            'branches',
            'agents',
            'schemes',
            'restrictedBranchId',
            'restrictedAgentId'
        ));
    }

    /**
     * Get active leads for a given agent (used by AJAX in deposit create form).
     */
    public function getAgentLeads(Agent $agent): JsonResponse
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Access denied: Agents are not permitted to manage deposits.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId !== null && $agent->branch_id !== null && (int) $agent->branch_id !== (int) $restrictedBranchId) {
            abort(403, 'Access denied: Agent does not belong to your assigned branch.');
        }

        $leadQuery = Lead::where('agent_id', $agent->id)
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', Lead::STATUS_NOT_INTERESTED);
            })
            ->with(['branch', 'interestedScheme'])
            ->latest('id');

        if ($restrictedBranchId !== null) {
            $leadQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }

        $leads = $leadQuery->get()->map(function ($lead) {
            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'mobile' => $lead->mobile,
                'agent_id' => (string) $lead->agent_id,
                'branch_id' => $lead->branch_id,
                'branch_name' => $lead->branch?->name,
                'interested_scheme_id' => $lead->interested_scheme_id,
                'scheme_name' => $lead->interestedScheme?->name,
                'expected_amount' => $lead->expected_amount ? (float) $lead->expected_amount : null,
                'status' => $lead->status,
            ];
        });

        return response()->json($leads);
    }

    /**
     * Show the form for creating a new deposit.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Access denied: Agents are not permitted to create deposits.');
        }

        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('create-deposits')) {
            abort(403, 'Access denied: Create deposits privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        // Branches
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        // Active Agents (structural branch restriction: free-field agents with null branch are accessible across branches)
        $agentQuery = Agent::where('status', 'active')->with(['branch', 'rank'])->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        if ($restrictedAgentId !== null) {
            $agentQuery->where('id', $restrictedAgentId);
        }
        $agents = $agentQuery->get();

        // Active Schemes only!
        $schemes = DepositScheme::active()
            ->with(['activeInterestRates', 'mrpConfiguration', 'mrpRates'])
            ->orderBy('name')
            ->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Agent Leads available for conversion to deposit accounts
        $selectedLeadId = $request->query('lead_id');
        $selectedLead = $selectedLeadId
            ? Lead::with(['branch', 'agent.rank', 'interestedScheme', 'convertedCustomer'])->find($selectedLeadId)
            : null;

        // Pre-selected existing customer (e.g. from Customer Profile "Open Deposit" action)
        $selectedCustomer = null;
        if ($request->filled('customer_id')) {
            $candidateCustomer = Customer::with(['branch', 'agent', 'leads' => fn ($q) => $q->latest('id')])
                ->find($request->query('customer_id'));

            if ($candidateCustomer && ($restrictedBranchId === null || (int) $candidateCustomer->branch_id === (int) $restrictedBranchId)) {
                $selectedCustomer = $candidateCustomer;
                if (! $selectedLead && $candidateCustomer->leads->isNotEmpty()) {
                    $selectedLead = $candidateCustomer->leads->first()->load(['branch', 'agent.rank', 'interestedScheme', 'convertedCustomer']);
                    $selectedLeadId = (string) $selectedLead->id;
                }
            }
        }

        $leadQuery = Lead::with(['branch', 'agent.rank', 'interestedScheme', 'convertedCustomer'])
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', Lead::STATUS_NOT_INTERESTED);
            })
            ->latest('id');

        if ($restrictedBranchId !== null) {
            $leadQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        if ($restrictedAgentId !== null) {
            $leadQuery->where('agent_id', $restrictedAgentId);
        }
        $agentLeads = $leadQuery->get();

        if ($selectedLead && ! $agentLeads->contains('id', $selectedLead->id)) {
            $agentLeads->prepend($selectedLead);
        }

        // Also ensure all agents linked to selectable leads or pre-selected customer are present in $agents
        $leadAgentIds = $agentLeads->pluck('agent_id')->filter()->unique();
        if ($selectedLead && $selectedLead->agent_id) {
            $leadAgentIds->push($selectedLead->agent_id);
        }
        if ($selectedCustomer && $selectedCustomer->agent_id) {
            $leadAgentIds->push($selectedCustomer->agent_id);
        }
        $leadAgentIds = $leadAgentIds->unique();

        if ($leadAgentIds->isNotEmpty()) {
            $missingAgents = Agent::whereIn('id', $leadAgentIds)
                ->where('status', 'active')
                ->with(['branch', 'rank'])
                ->get();
            $agents = $agents->concat($missingAgents)->unique('id')->sortBy('name')->values();
        }

        $leadsJson = $agentLeads->map(function ($l) {
            $isSenior = false;
            $cust = $l->convertedCustomer;
            if ($cust && ! empty($cust->date_of_birth)) {
                $isSenior = Carbon::parse($cust->date_of_birth)->age >= 60;
            }

            return [
                'id' => (string) $l->id,
                'name' => $l->name,
                'mobile' => $l->mobile,
                'agent_id' => (string) $l->agent_id,
                'branch_id' => $l->branch_id,
                'branch_name' => $l->branch?->name,
                'interested_scheme_id' => $l->interested_scheme_id,
                'scheme_name' => $l->interestedScheme?->name,
                'expected_amount' => $l->expected_amount ? (float) $l->expected_amount : null,
                'status' => $l->status,
                'is_senior' => $isSenior,
            ];
        })->values();

        return view('admin.deposits.create', compact(
            'branches',
            'agentLeads',
            'leadsJson',
            'agents',
            'schemes',
            'restrictedBranchId',
            'restrictedAgentId',
            'selectedLead',
            'selectedLeadId',
            'selectedCustomer'
        ));
    }

    /**
     * Store a newly created deposit in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Access denied: Agents are not permitted to create deposits.');
        }

        if ($user && $user->isStaff() && ! $user->isAdmin() && ! $user->hasPermission('create-deposits')) {
            abort(403, 'Access denied: Create deposits privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        // If restricted, enforce branch and agent from context
        if ($restrictedBranchId !== null) {
            $request->merge(['branch_id' => $restrictedBranchId]
            );
        }
        if ($restrictedAgentId !== null) {
            $request->merge(['agent_id' => $restrictedAgentId]);
        }

        // Resolve lead conversion if lead_id or customer_id format "lead_X" is sent
        $rawCustomerId = $request->input('customer_id');
        if (is_string($rawCustomerId) && str_starts_with($rawCustomerId, 'lead_')) {
            $leadId = (int) str_replace('lead_', '', $rawCustomerId);
            $request->merge(['customer_id' => null, 'lead_id' => $leadId]);
        }

        // If lead_id is provided, auto-fill agent_id and branch_id if omitted
        if ($request->filled('lead_id')) {
            $leadObj = Lead::with(['agent', 'branch'])->find($request->input('lead_id'));
            if ($leadObj) {
                if (! $request->filled('agent_id')) {
                    $request->merge(['agent_id' => $leadObj->agent_id]);
                }
                if (! $request->filled('branch_id')) {
                    $request->merge(['branch_id' => $leadObj->branch_id]);
                }
            }
        }

        // Basic structural validation
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'manual_customer_name' => ['nullable', 'string', 'max:255'],
            'manual_customer_mobile' => ['nullable', 'string', 'max:20'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'deposit_scheme_id' => ['required', 'integer', 'exists:deposit_schemes,id'],
            'is_double' => ['nullable', 'boolean'],
            'tenure' => ['nullable', 'integer', 'min:1'],
            'tenure_unit' => ['nullable', 'string', 'in:DAYS,MONTHS,YEARS'],
            'is_senior_citizen' => ['nullable', 'boolean'],
            'deposit_number' => ['nullable', 'string', 'max:50', 'unique:deposits,deposit_number'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'deposit_date' => ['nullable', 'date'],
            'status_action' => ['nullable', 'string', 'in:active,pending,draft'],
            'status' => ['nullable', 'string', 'in:ACTIVE,PENDING_APPROVAL,DRAFT'],
            'submit_as_active' => ['nullable', 'boolean'],
            'payment_mode' => ['nullable', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE,ONLINE_UPI,DEMAND_DRAFT'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'nominee_name' => ['nullable', 'string', 'max:255'],
            'nominee_relation' => ['nullable', 'string', 'max:50'],
            'nominee_phone' => ['nullable', 'string', 'max:20'],
            'nominee_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'auto_renewal' => ['nullable', 'string', 'in:NONE,PRINCIPAL_ONLY,PRINCIPAL_AND_INTEREST'],
            'payout_frequency' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submit_as_draft' => ['nullable', 'boolean'],
        ]);

        // Resolve Lead assignment if lead_id is present
        $lead = null;
        if (! empty($validated['lead_id'])) {
            $lead = Lead::find($validated['lead_id']);
            if ($lead) {
                if ($lead->agent_id) {
                    $leadAgent = Agent::find($lead->agent_id);
                    if ($leadAgent && $leadAgent->isActive()) {
                        $validated['agent_id'] = $lead->agent_id;
                    }
                }
                if (empty($validated['branch_id'])) {
                    $validated['branch_id'] = $lead->branch_id;
                }
            }
        }

        if (empty($validated['agent_id'])) {
            throw ValidationException::withMessages([
                'agent_id' => 'The sourcing advisor / agent is missing or inactive.',
            ]);
        }

        $agent = Agent::find($validated['agent_id']);
        if (! $agent || $agent->status !== 'active') {
            throw ValidationException::withMessages([
                'agent_id' => 'The selected sourcing advisor / agent is inactive or not found.',
            ]);
        }

        // Auto-fill branch if omitted (agents are free field, so fall back to user's branch or default)
        if (empty($validated['branch_id'])) {
            $validated['branch_id'] = $agent->branch_id ?? auth()->user()->branch_id ?? Branch::first()?->id;
        }

        // Resolve existing customer without duplicating leads or matching customer records
        $customer = $this->findExistingDepositCustomer($validated, $lead);
        $validated['customer_id'] = $customer?->id;

        if (! $customer && ! $lead && empty(trim((string) ($validated['manual_customer_name'] ?? '')))) {
            throw ValidationException::withMessages([
                'customer_id' => 'Please select a customer lead or existing account holder.',
            ]);
        }

        // 3. Scheme Check: MUST be ACTIVE
        $scheme = DepositScheme::find($validated['deposit_scheme_id']);
        if (! $scheme || ! $scheme->isActive()) {
            throw ValidationException::withMessages([
                'deposit_scheme_id' => 'The selected deposit scheme is INACTIVE. Inactive schemes cannot be used for opening new deposit accounts.',
            ]);
        }

        // 4. Branch Isolation Checks: Existing customer must belong to deposit branch
        if ($customer && (int) $customer->branch_id !== (int) $validated['branch_id']) {
            throw ValidationException::withMessages([
                'branch_id' => 'Cross-branch restriction: Account holder belongs to a different branch than the operating deposit branch.',
            ]);
        }

        if ($agent->branch_id !== null && (int) $agent->branch_id !== (int) $validated['branch_id']) {
            throw ValidationException::withMessages([
                'agent_id' => 'Cross-branch restriction: Sourcing advisor belongs to a different branch than the operating deposit branch.',
            ]);
        }

        if ($restrictedAgentId !== null && (int) $agent->id !== (int) $restrictedAgentId) {
            throw ValidationException::withMessages([
                'agent_id' => 'Authorization restricted: You are only permitted to open deposit accounts for your assigned clientele.',
            ]);
        }

        // 5. Monetary Bounds Validation (using DECIMAL accuracy)
        $amount = (float) $validated['amount'];
        $minAmount = (float) $scheme->min_amount;
        if ($amount < $minAmount) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit principal ₹'.number_format($amount, 2).' is below the minimum required amount of ₹'.number_format($minAmount, 2).' for this scheme.',
            ]);
        }

        if ($scheme->max_amount !== null) {
            $maxAmount = (float) $scheme->max_amount;
            if ($amount > $maxAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Deposit principal ₹'.number_format($amount, 2).' exceeds the maximum allowed limit of ₹'.number_format($maxAmount, 2).' for this scheme.',
                ]);
            }
        }

        // 6. Maturity Date & Interest Rate Lookup
        $startDate = Carbon::parse($validated['start_date']);
        $tenure = $request->filled('tenure') ? (int) $request->input('tenure') : (int) $scheme->tenure;
        $tenureUnit = $request->filled('tenure_unit')
            ? strtoupper(trim((string) $request->input('tenure_unit')))
            : (string) ($scheme->tenure_unit ?? DepositScheme::TENURE_MONTHS);

        $isKsp = $scheme->isKsp();
        $kspProjection = $isKsp ? $scheme->calculateKspProjection($amount) : null;

        // Determine if Double feature applies
        $isDouble = ! $isKsp && ($request->boolean('is_double') || (bool) ($scheme->is_double ?? false) || ($scheme->scheme_type === DepositScheme::TYPE_DOUBLE));

        $isSeniorCitizen = false;
        if ($isKsp) {
            $interestRate = 0.00;
            $depositInterestRateId = null;
            $tenure = (int) ($kspProjection['deposit_period'] ?: $tenure ?: 10);
            $tenureUnit = DepositScheme::TENURE_YEARS;
        } elseif ($isDouble) {
            $interestRate = 0.00;
            $depositInterestRateId = null;
        } else {
            // Determine if senior citizen rate applies
            $isSeniorCitizen = $request->boolean('is_senior_citizen')
                || ($customer && ! empty($customer->date_of_birth) && Carbon::parse($customer->date_of_birth)->age >= 60);

            // Exact rate lookup: verify against configured multi-year tiers
            $matchingRate = $scheme->findInterestRateRecord($tenure, $tenureUnit, $startDate);
            $depositInterestRateId = null;

            if ($scheme->hasConfiguredTenureRates()) {
                if (! $matchingRate) {
                    throw ValidationException::withMessages([
                        'tenure' => "No active interest rate configuration exists for scheme '{$scheme->name}' with duration {$tenure} {$tenureUnit} on {$startDate->format('Y-m-d')}. Please select an eligible duration.",
                    ]);
                }

                $baseRate = (float) $matchingRate->interest_rate;
                $seniorExtra = (float) ($matchingRate->senior_citizen_extra_rate ?? 0.00);
                if ($seniorExtra <= 0 && (float) ($scheme->senior_citizen_extra_rate ?? 0.00) > 0) {
                    $seniorExtra = (float) $scheme->senior_citizen_extra_rate;
                }
                $depositInterestRateId = $matchingRate->id;
            } else {
                // If no multi-year rates are configured, verify tenure matches base scheme tenure
                $schemeTenure = (int) $scheme->tenure;
                $schemeUnit = strtoupper(trim((string) ($scheme->tenure_unit ?? DepositScheme::TENURE_MONTHS)));
                if ($tenure !== $schemeTenure || $tenureUnit !== $schemeUnit) {
                    throw ValidationException::withMessages([
                        'tenure' => "Invalid duration: Scheme '{$scheme->name}' only supports {$schemeTenure} {$schemeUnit}. Please configure interest rates under Super Admin or select the permitted tenure.",
                    ]);
                }

                $baseRate = (float) ($scheme->interest_rate ?? 0.00);
                $seniorExtra = (float) ($scheme->senior_citizen_extra_rate ?? 0.00);
            }

            $interestRate = round($baseRate + ($isSeniorCitizen ? $seniorExtra : 0.00), 2);
        }

        $payoutFrequency = $validated['payout_frequency'] ?? $scheme->getEffectivePayoutFrequency();
        $maturityDate = Deposit::calculateMaturityDate($startDate, $tenure, $tenureUnit);
        $maturityAmount = $isKsp && $kspProjection
            ? $kspProjection['total_return']
            : Deposit::calculateMaturityAmount(
                $amount,
                (float) $interestRate,
                (int) $tenure,
                (string) $tenureUnit,
                (string) ($scheme->scheme_type ?? 'FD'),
                (string) ($scheme->collection_frequency ?? 'ONE_TIME'),
                isDouble: $isDouble,
                payoutFrequency: $payoutFrequency
            );

        // 7. Initial Status determination
        if (
            $request->input('status_action') === 'active' ||
            $request->input('status') === Deposit::STATUS_ACTIVE ||
            ! empty($validated['submit_as_active'])
        ) {
            $initialStatus = Deposit::STATUS_ACTIVE;
        } elseif (! empty($validated['submit_as_draft']) || $request->input('status_action') === 'draft') {
            $initialStatus = Deposit::STATUS_DRAFT;
        } else {
            $initialStatus = Deposit::STATUS_PENDING_APPROVAL;
        }

        // 8. Create Customer (if new) and Deposit atomically inside transaction
        [$deposit, $commission] = DB::transaction(function () use ($validated, $customer, $lead, $agent, $scheme, $startDate, $tenure, $tenureUnit, $interestRate, $depositInterestRateId, $maturityDate, $maturityAmount, $initialStatus, $isDouble, $isSeniorCitizen, $payoutFrequency, $isKsp, $kspProjection) {
            if (! $customer) {
                $customer = $this->createDepositCustomer($validated, $lead, $agent);
            }

            // If lead was not explicitly passed, check if customer has an unconverted lead for this agent
            if (! $lead) {
                $lead = Lead::where('agent_id', $agent->id)
                    ->where(function ($q) use ($customer) {
                        $q->where('converted_customer_id', $customer->id);
                        if (! empty($customer->mobile)) {
                            $q->orWhere('mobile', $customer->mobile);
                        }
                    })
                    ->where('status', '!=', Lead::STATUS_CONVERTED)
                    ->first();
            }

            if ($lead) {
                $validated['lead_id'] = $lead->id;
                $lead->update([
                    'status' => Lead::STATUS_CONVERTED,
                    'converted_customer_id' => $customer->id,
                    'converted_at' => $lead->converted_at ?? now(),
                ]);
            }
            $depositNumber = ! empty($validated['deposit_number'])
                ? trim($validated['deposit_number'])
                : Deposit::generateDepositNumber();
            $paymentMode = $validated['payment_mode'] ?? Deposit::PAYMENT_MODE_CASH;
            $autoRenewal = $validated['auto_renewal'] ?? Deposit::AUTO_RENEWAL_NONE;
            $depositDate = ! empty($validated['deposit_date']) ? Carbon::parse($validated['deposit_date'])->format('Y-m-d') : $startDate->format('Y-m-d');

            $deposit = Deposit::create([
                'deposit_number' => $depositNumber,
                'customer_id' => $customer->id,
                'lead_id' => $validated['lead_id'] ?? null,
                'branch_id' => $validated['branch_id'],
                'agent_id' => $agent->id,
                'deposit_scheme_id' => $scheme->id,
                'deposit_interest_rate_id' => $depositInterestRateId,
                'deposit_type' => $scheme->scheme_type ?? DepositScheme::TYPE_FD,
                'amount' => $validated['amount'],
                'start_date' => $startDate->format('Y-m-d'),
                'deposit_date' => $depositDate,
                'tenure' => $tenure,
                'tenure_unit' => $tenureUnit,
                'collection_frequency' => $scheme->collection_frequency,
                'interest_rate' => $interestRate,
                'is_double' => $isDouble,
                'is_senior_citizen' => $isSeniorCitizen,
                'maturity_date' => $maturityDate->format('Y-m-d'),
                'maturity_amount' => $maturityAmount,
                'payment_mode' => $paymentMode,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'nominee_name' => $validated['nominee_name'] ?? null,
                'nominee_relation' => $validated['nominee_relation'] ?? null,
                'nominee_phone' => $validated['nominee_phone'] ?? null,
                'nominee_age' => ! empty($validated['nominee_age']) ? (int) $validated['nominee_age'] : null,
                'auto_renewal' => $autoRenewal,
                'payout_frequency' => $payoutFrequency,
                'status' => $initialStatus,
                'approved_by' => $initialStatus === Deposit::STATUS_ACTIVE ? auth()->id() : null,
                'approved_at' => $initialStatus === Deposit::STATUS_ACTIVE ? now() : null,
                'activated_at' => $initialStatus === Deposit::STATUS_ACTIVE ? now() : null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $auditSuffix = $isKsp && $kspProjection
                ? ', KSP Plan (Total Return ₹'.number_format($deposit->maturity_amount, 2).', Insurance Coverage ₹'.number_format($kspProjection['insurance_coverage'], 2).')'
                : ($isDouble ? ', Doubling Scheme' : '');

            // Audit Log
            AuditLog::record(
                event: 'deposit_created',
                description: "Created deposit [{$deposit->deposit_number}] ({$deposit->deposit_type}) for customer [{$customer->name}] under scheme [{$scheme->name}] with amount ₹".number_format($deposit->amount, 2).' (Maturity ₹'.number_format($deposit->maturity_amount, 2).$auditSuffix."). Payment: {$paymentMode}. Status: {$initialStatus}.",
                auditable: $deposit,
                newValues: $deposit->only(['deposit_number', 'customer_id', 'lead_id', 'branch_id', 'agent_id', 'deposit_scheme_id', 'deposit_type', 'amount', 'start_date', 'tenure', 'is_double', 'maturity_date', 'maturity_amount', 'payment_mode', 'status']),
                amount: (float) $deposit->amount
            );

            // Generate initial collection schedule if deposit is immediately active
            if ($initialStatus === Deposit::STATUS_ACTIVE && $deposit->collections()->count() === 0) {
                DepositCollection::create([
                    'deposit_id' => $deposit->id,
                    'customer_id' => $deposit->customer_id,
                    'branch_id' => $deposit->branch_id,
                    'agent_id' => $deposit->agent_id,
                    'installment_number' => 1,
                    'due_date' => $deposit->start_date ?? now()->toDateString(),
                    'due_amount' => $deposit->amount,
                    'paid_amount' => 0.00,
                    'status' => DepositCollection::STATUS_DUE,
                ]);
            }

            // Automatic Commission Calculation based on existing commission rules
            $commission = null;
            try {
                $commission = app(CommissionService::class)->calculateAndCreateForDeposit($deposit, auth()->user());
            } catch (\Exception $e) {
                // Non-blocking
            }

            return [$deposit, $commission];
        });

        $totalCommissionAmount = $commission instanceof Collection
            ? (float) $commission->sum('commission_amount')
            : (float) ($commission?->commission_amount ?? 0);

        $commissionCount = $commission instanceof Collection
            ? $commission->count()
            : ($commission ? 1 : 0);

        $commissionMsg = $totalCommissionAmount > 0
            ? ' Agent commission of ₹'.number_format($totalCommissionAmount, 2).' automatically calculated and pending admin approval.'
            : '';

        // Reload updated attributes/agent before checking promotion eligibility
        $deposit->refresh();

        // Automatically promote agent and upline if promotional targets are complete
        if ($deposit->agent && in_array($deposit->status, [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED], true)) {
            try {
                app(CareerPerformanceService::class)->checkAndAutoPromote($deposit->agent, auth()->user());
            } catch (\Throwable $e) {
                // Non-blocking
            }
        }

        return redirect()->route('admin.deposits.show', $deposit)
            ->with('success', "Deposit account '{$deposit->deposit_number}' registered successfully with status {$initialStatus}.{$commissionMsg}");
    }

    /**
     * Show the form for editing the specified deposit.
     */
    public function edit(Deposit $deposit)
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Access denied: Agents are not permitted to edit deposits.');
        }

        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('create-deposits') || $user->hasPermission('manage-deposits'))) {
            abort(403, 'Access denied: Edit deposits privileges required.');
        }

        $this->authorizeDepositAccess($deposit);

        if (in_array($deposit->status, [Deposit::STATUS_CLOSED, Deposit::STATUS_MATURED, Deposit::STATUS_REJECTED], true) && ! ($user && $user->isSuperAdmin())) {
            return redirect()->route('admin.deposits.show', $deposit)
                ->with('error', "Deposit accounts in {$deposit->status} status cannot be edited.");
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        // Branches
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        // Active Agents
        $agentQuery = Agent::where('status', 'active')->with(['branch', 'rank'])->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        if ($restrictedAgentId !== null) {
            $agentQuery->where('id', $restrictedAgentId);
        }
        $agents = $agentQuery->get();

        if ($deposit->agent && ! $agents->contains('id', $deposit->agent_id)) {
            $agents->prepend($deposit->agent);
        }

        // Active Schemes + current scheme if inactive
        $schemes = DepositScheme::active()
            ->with(['activeInterestRates', 'mrpConfiguration', 'mrpRates'])
            ->orderBy('name')
            ->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        if ($deposit->scheme && ! $schemes->contains('id', $deposit->deposit_scheme_id)) {
            $schemes->prepend($deposit->scheme);
        }

        // Agent Leads
        $leadQuery = Lead::with(['branch', 'agent.rank', 'interestedScheme'])
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', Lead::STATUS_NOT_INTERESTED);
            })
            ->latest('id');

        if ($restrictedBranchId !== null) {
            $leadQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        }
        if ($restrictedAgentId !== null) {
            $leadQuery->where('agent_id', $restrictedAgentId);
        }
        $agentLeads = $leadQuery->get();

        if ($deposit->lead && ! $agentLeads->contains('id', $deposit->lead_id)) {
            $agentLeads->prepend($deposit->lead);
        }

        $deposit->load(['customer', 'scheme.activeInterestRates', 'agent', 'lead', 'branch']);

        return view('admin.deposits.edit', compact(
            'deposit',
            'branches',
            'agents',
            'schemes',
            'agentLeads'
        ));
    }

    /**
     * Update the specified deposit in storage.
     */
    public function update(Request $request, Deposit $deposit)
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Access denied: Agents are not permitted to edit deposits.');
        }

        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('create-deposits') || $user->hasPermission('manage-deposits'))) {
            abort(403, 'Access denied: Edit deposits privileges required.');
        }

        $this->authorizeDepositAccess($deposit);

        if (in_array($deposit->status, [Deposit::STATUS_CLOSED, Deposit::STATUS_MATURED, Deposit::STATUS_REJECTED], true) && ! ($user && $user->isSuperAdmin())) {
            return redirect()->route('admin.deposits.show', $deposit)
                ->with('error', "Deposit accounts in {$deposit->status} status cannot be edited.");
        }

        $validated = $request->validate([
            'deposit_scheme_id' => ['required', 'integer', 'exists:deposit_schemes,id'],
            'is_double' => ['nullable', 'boolean'],
            'is_senior_citizen' => ['nullable', 'boolean'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tenure' => ['required', 'integer', 'min:1'],
            'tenure_unit' => ['required', 'string', 'in:DAYS,MONTHS,YEARS'],
            'start_date' => ['required', 'date'],
            'deposit_date' => ['nullable', 'date'],
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'payment_mode' => ['nullable', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE,ONLINE_UPI,DEMAND_DRAFT'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'nominee_name' => ['nullable', 'string', 'max:255'],
            'nominee_relation' => ['nullable', 'string', 'max:50'],
            'nominee_phone' => ['nullable', 'string', 'max:20'],
            'nominee_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'auto_renewal' => ['nullable', 'string', 'in:NONE,PRINCIPAL_ONLY,PRINCIPAL_AND_INTEREST'],
            'payout_frequency' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $scheme = DepositScheme::with('activeInterestRates')->findOrFail($validated['deposit_scheme_id']);

        // Check scheme min/max amount
        if ($scheme->min_amount !== null && (float) $validated['amount'] < (float) $scheme->min_amount) {
            throw ValidationException::withMessages([
                'amount' => 'The minimum deposit amount for '.$scheme->name.' is ₹'.number_format($scheme->min_amount, 2).'.',
            ]);
        }
        if ($scheme->max_amount !== null && (float) $validated['amount'] > (float) $scheme->max_amount) {
            throw ValidationException::withMessages([
                'amount' => 'The maximum deposit amount for '.$scheme->name.' is ₹'.number_format($scheme->max_amount, 2).'.',
            ]);
        }

        $tenure = (int) $validated['tenure'];
        $tenureUnit = strtoupper(trim((string) $validated['tenure_unit']));
        $startDate = Carbon::parse($validated['start_date']);

        $isKsp = $scheme->isKsp();
        $kspProjection = $isKsp ? $scheme->calculateKspProjection((float) $validated['amount']) : null;
        $isDouble = ! $isKsp && ($request->boolean('is_double') || (bool) ($scheme->is_double ?? false) || ($scheme->scheme_type === DepositScheme::TYPE_DOUBLE));

        $isSeniorCitizen = false;
        if ($isKsp) {
            $depositInterestRateId = null;
            $interestRate = 0.00;
            $tenure = (int) ($kspProjection['deposit_period'] ?: $tenure ?: 10);
            $tenureUnit = DepositScheme::TENURE_YEARS;
        } elseif ($isDouble) {
            $depositInterestRateId = null;
            $interestRate = 0.00;
        } else {
            $isSeniorCitizen = $request->boolean('is_senior_citizen')
                || (! empty($deposit->customer?->date_of_birth) && Carbon::parse($deposit->customer->date_of_birth)->age >= 60);

            // Check interest rate matching tenure
            $depositInterestRateId = null;
            $baseRate = (float) ($scheme->interest_rate ?? 0.00);
            $seniorExtra = (float) ($scheme->senior_citizen_extra_rate ?? 0.00);

            $matchingRate = $scheme->activeInterestRates
                ->where('tenure', $tenure)
                ->where('tenure_unit', $tenureUnit)
                ->first();

            if ($matchingRate) {
                $depositInterestRateId = $matchingRate->id;
                $baseRate = (float) $matchingRate->interest_rate;
                $tierSeniorExtra = (float) ($matchingRate->senior_citizen_extra_rate ?? 0.00);
                if ($tierSeniorExtra > 0) {
                    $seniorExtra = $tierSeniorExtra;
                }
            }

            $interestRate = round($baseRate + ($isSeniorCitizen ? $seniorExtra : 0.00), 2);
        }

        $payoutFrequency = $validated['payout_frequency']
            ?? ((int) $scheme->id !== (int) $deposit->deposit_scheme_id
                ? $scheme->getEffectivePayoutFrequency()
                : ($deposit->payout_frequency ?: $scheme->getEffectivePayoutFrequency()));

        $maturityDate = match ($tenureUnit) {
            'YEARS', 'YEAR' => $startDate->copy()->addYears($tenure),
            'DAYS', 'DAY' => $startDate->copy()->addDays($tenure),
            default => $startDate->copy()->addMonths($tenure),
        };

        $maturityAmount = $isKsp && $kspProjection
            ? $kspProjection['total_return']
            : Deposit::calculateMaturityAmount(
                amount: (float) $validated['amount'],
                interestRate: $interestRate,
                tenure: $tenure,
                tenureUnit: $tenureUnit,
                schemeType: $scheme->scheme_type ?? DepositScheme::TYPE_FD,
                frequency: $scheme->collection_frequency ?? DepositScheme::FREQ_ONE_TIME,
                isDouble: $isDouble,
                payoutFrequency: $payoutFrequency
            );

        $recalculatedCommissions = DB::transaction(function () use ($deposit, $validated, $scheme, $tenure, $tenureUnit, $startDate, $interestRate, $depositInterestRateId, $maturityDate, $maturityAmount, $isDouble, $isSeniorCitizen, $payoutFrequency) {
            $lockedDeposit = Deposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            $oldValues = $lockedDeposit->only([
                'deposit_scheme_id', 'amount', 'tenure', 'tenure_unit',
                'interest_rate', 'is_double', 'is_senior_citizen', 'maturity_date', 'maturity_amount',
                'agent_id', 'lead_id', 'start_date', 'status',
            ]);

            $financialTermsChanged = (
                (int) $lockedDeposit->deposit_scheme_id !== (int) $scheme->id ||
                (float) $lockedDeposit->amount !== (float) $validated['amount'] ||
                (bool) $lockedDeposit->is_double !== $isDouble ||
                (bool) $lockedDeposit->is_senior_citizen !== $isSeniorCitizen ||
                (int) $lockedDeposit->tenure !== $tenure ||
                strtoupper((string) $lockedDeposit->tenure_unit) !== $tenureUnit ||
                (int) $lockedDeposit->agent_id !== (int) $validated['agent_id'] ||
                (int) ($lockedDeposit->lead_id ?? 0) !== (int) ($validated['lead_id'] ?? 0)
            );

            $depositDate = ! empty($validated['deposit_date'])
                ? Carbon::parse($validated['deposit_date'])->format('Y-m-d')
                : $startDate->format('Y-m-d');

            $lockedDeposit->update([
                'deposit_scheme_id' => $scheme->id,
                'deposit_interest_rate_id' => $depositInterestRateId,
                'deposit_type' => $scheme->scheme_type ?? $lockedDeposit->deposit_type,
                'amount' => $validated['amount'],
                'start_date' => $startDate->format('Y-m-d'),
                'deposit_date' => $depositDate,
                'tenure' => $tenure,
                'tenure_unit' => $tenureUnit,
                'interest_rate' => $interestRate,
                'is_double' => $isDouble,
                'is_senior_citizen' => $isSeniorCitizen,
                'maturity_date' => $maturityDate->format('Y-m-d'),
                'maturity_amount' => $maturityAmount,
                'agent_id' => $validated['agent_id'],
                'lead_id' => $validated['lead_id'] ?? null,
                'payment_mode' => $validated['payment_mode'] ?? $lockedDeposit->payment_mode,
                'payment_reference' => $validated['payment_reference'] ?? $lockedDeposit->payment_reference,
                'nominee_name' => $validated['nominee_name'] ?? $lockedDeposit->nominee_name,
                'nominee_relation' => $validated['nominee_relation'] ?? $lockedDeposit->nominee_relation,
                'nominee_phone' => $validated['nominee_phone'] ?? $lockedDeposit->nominee_phone,
                'nominee_age' => ! empty($validated['nominee_age']) ? (int) $validated['nominee_age'] : $lockedDeposit->nominee_age,
                'auto_renewal' => $validated['auto_renewal'] ?? $lockedDeposit->auto_renewal,
                'payout_frequency' => $payoutFrequency,
                'notes' => $validated['notes'] ?? $lockedDeposit->notes,
            ]);

            // If collections exist and first collection is still due, update its due amount
            $firstDueColl = $lockedDeposit->collections()
                ->where('installment_number', 1)
                ->where('status', DepositCollection::STATUS_DUE)
                ->first();
            if ($firstDueColl) {
                $firstDueColl->update(['due_amount' => $validated['amount']]);
            }

            AuditLog::record(
                event: 'deposit_updated',
                description: "Updated deposit [{$lockedDeposit->deposit_number}] terms. Amount: ₹".number_format($lockedDeposit->amount, 2).", Tenure: {$tenure} {$tenureUnit}, Rate: {$interestRate}%.",
                auditable: $lockedDeposit,
                oldValues: $oldValues,
                newValues: $lockedDeposit->only([
                    'deposit_scheme_id', 'amount', 'tenure', 'tenure_unit',
                    'interest_rate', 'is_double', 'is_senior_citizen', 'maturity_date', 'maturity_amount',
                    'agent_id', 'lead_id', 'start_date', 'status',
                ]),
                amount: (float) $lockedDeposit->amount
            );

            // Re-calculate commission if financial or attribution terms changed
            $newComms = collect();
            if ($financialTermsChanged) {
                $newComms = app(CommissionService::class)->recalculateForDeposit(
                    $lockedDeposit,
                    'Deposit terms modified via Admin form',
                    auth()->user()
                );
            }

            return $newComms;
        });

        $commMsg = '';
        if ($recalculatedCommissions instanceof Collection && $recalculatedCommissions->isNotEmpty()) {
            $totalComm = (float) $recalculatedCommissions->sum('commission_amount');
            $commMsg = ' Commission successfully recalculated: ₹'.number_format($totalComm, 2).' (Status: PENDING Admin Approval).';
        }

        // Reload updated attributes/agent before checking promotion eligibility
        $deposit->refresh();

        // Automatically promote agent and upline if promotional targets are complete
        if ($deposit->agent && in_array($deposit->status, [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED], true)) {
            try {
                app(CareerPerformanceService::class)->checkAndAutoPromote($deposit->agent, auth()->user());
            } catch (\Throwable $e) {
                // Non-blocking
            }
        }

        return redirect()->route('admin.deposits.show', $deposit)
            ->with('success', "Deposit account '{$deposit->deposit_number}' updated successfully.{$commMsg}");
    }

    /**
     * Display the specified deposit.
     */
    public function show(Deposit $deposit)
    {
        $this->authorizeDepositAccess($deposit);

        $deposit->load([
            'branch',
            'customer',
            'lead',
            'agent.rank',
            'scheme',
            'creator',
            'approver',
            'rejecter',
            'closer',
            'collections',
            'commissions.rule',
            'commissions.approver',
            'auditLogs.user',
        ]);

        return view('admin.deposits.show', compact('deposit'));
    }

    /**
     * Approve deposit: PENDING_APPROVAL -> APPROVED
     */
    public function approve(Deposit $deposit)
    {
        $this->authorizeApprovalAction($deposit);

        if (! $deposit->canBeApproved()) {
            return redirect()->back()
                ->with('error', "Invalid transition: Deposit with status '{$deposit->status}' cannot be approved.");
        }

        DB::transaction(function () use ($deposit) {
            $oldStatus = $deposit->status;
            $deposit->update([
                'status' => Deposit::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            AuditLog::record(
                event: 'deposit_approved',
                description: "Deposit [{$deposit->deposit_number}] was approved by ".auth()->user()->name.'.',
                auditable: $deposit,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => Deposit::STATUS_APPROVED, 'approved_by' => auth()->id(), 'approved_at' => now()->toDateTimeString()],
                amount: (float) $deposit->amount
            );

            // Ensure initial collection schedule exists
            if ($deposit->collections()->count() === 0) {
                DepositCollection::create([
                    'deposit_id' => $deposit->id,
                    'customer_id' => $deposit->customer_id,
                    'branch_id' => $deposit->branch_id,
                    'agent_id' => $deposit->agent_id,
                    'installment_number' => 1,
                    'due_date' => $deposit->start_date ?? now()->toDateString(),
                    'due_amount' => $deposit->amount,
                    'paid_amount' => 0.00,
                    'status' => DepositCollection::STATUS_DUE,
                ]);
            }

            // Ensure agent commission is calculated
            try {
                app(CommissionService::class)->calculateAndCreateForDeposit($deposit, auth()->user());
            } catch (\Exception $e) {
                // Non-blocking
            }
        });

        // Automatically promote agent and upline if promotional targets are complete
        try {
            if ($deposit->agent) {
                app(CareerPerformanceService::class)->checkAndAutoPromote($deposit->agent, auth()->user());
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' has been APPROVED successfully.");
    }

    /**
     * Verify deposit application: records verification remarks and forwards for final approval.
     */
    public function verify(Request $request, Deposit $deposit)
    {
        $this->authorizeDepositAccess($deposit);

        $user = auth()->user();
        if (! ($user->isAdmin() || $user->hasPermission('manage-deposits') || $user->hasPermission('view-deposits'))) {
            abort(403, 'Unauthorized action: Deposit verification privileges required.');
        }

        if (! $deposit->isPendingApproval()) {
            return redirect()->back()
                ->with('error', "Deposit is '{$deposit->status}'. Only pending deposits can be verified.");
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($deposit, $validated, $user) {
            $deposit->update([
                'verified_by' => $user->id,
                'verified_at' => now(),
                'remarks' => $validated['remarks'] ?? $deposit->remarks,
            ]);

            AuditLog::record(
                event: 'deposit_verified',
                description: "Deposit application [{$deposit->deposit_number}] verified by {$user->name} and forwarded for final approval.",
                auditable: $deposit,
                newValues: [
                    'verified_by' => $user->id,
                    'verified_at' => now()->toDateTimeString(),
                    'remarks' => $validated['remarks'] ?? null,
                ],
                amount: (float) $deposit->amount
            );
        });

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' verified successfully and forwarded for final approval.");
    }

    /**
     * Return deposit application for correction: PENDING_APPROVAL -> DRAFT
     */
    public function returnForCorrection(Request $request, Deposit $deposit)
    {
        $this->authorizeDepositAccess($deposit);

        $user = auth()->user();
        if (! ($user->isAdmin() || $user->hasPermission('manage-deposits') || $user->hasPermission('view-deposits'))) {
            abort(403, 'Unauthorized action: Deposit verification privileges required.');
        }

        if (! $deposit->isPendingApproval()) {
            return redirect()->back()
                ->with('error', "Deposit is '{$deposit->status}'. Only pending deposits can be returned for correction.");
        }

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($deposit, $validated, $user) {
            $oldStatus = $deposit->status;
            $deposit->update([
                'status' => Deposit::STATUS_DRAFT,
                'remarks' => 'Returned for correction: '.$validated['remarks'],
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            AuditLog::record(
                event: 'deposit_returned_for_correction',
                description: "Deposit [{$deposit->deposit_number}] returned for correction by {$user->name}. Notes: {$validated['remarks']}.",
                auditable: $deposit,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => Deposit::STATUS_DRAFT,
                    'remarks' => $validated['remarks'],
                ],
                amount: (float) $deposit->amount
            );
        });

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' returned for correction.");
    }

    /**
     * Reject deposit: PENDING_APPROVAL -> REJECTED
     */
    public function reject(Request $request, Deposit $deposit)
    {
        $this->authorizeDepositAccess($deposit);

        $user = auth()->user();
        if (! ($user->isSuperAdmin() || $user->isBranchAdmin() || $user->hasPermission('manage-deposits'))) {
            abort(403, 'Unauthorized action: Only branch managers or authorized deposit staff may reject deposits.');
        }

        if (! $deposit->canBeRejected()) {
            return redirect()->back()
                ->with('error', "Invalid transition: Deposit with status '{$deposit->status}' cannot be rejected.");
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($deposit, $validated) {
            $oldStatus = $deposit->status;
            $deposit->update([
                'status' => Deposit::STATUS_REJECTED,
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            AuditLog::record(
                event: 'deposit_rejected',
                description: "Deposit [{$deposit->deposit_number}] was rejected. Reason: {$validated['rejection_reason']}.",
                auditable: $deposit,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => Deposit::STATUS_REJECTED,
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now()->toDateTimeString(),
                    'rejection_reason' => $validated['rejection_reason'],
                ],
                amount: (float) $deposit->amount
            );

            // Atomically cancel and reverse all active commissions for this rejected deposit
            app(CommissionService::class)->cancelOrReverseForDeposit($deposit, $validated['rejection_reason'], auth()->user());
        });

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' has been REJECTED.");
    }

    /**
     * Activate deposit: APPROVED -> ACTIVE
     */
    public function activate(Deposit $deposit)
    {
        $this->authorizeApprovalAction($deposit);

        if (! $deposit->canBeActivated()) {
            return redirect()->back()
                ->with('error', 'Invalid transition: Deposit must be in APPROVED status to be activated.');
        }

        DB::transaction(function () use ($deposit) {
            $oldStatus = $deposit->status;
            $deposit->update([
                'status' => Deposit::STATUS_ACTIVE,
                'activated_at' => now(),
            ]);

            AuditLog::record(
                event: 'deposit_activated',
                description: "Deposit [{$deposit->deposit_number}] transitioned from APPROVED to ACTIVE.",
                auditable: $deposit,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => Deposit::STATUS_ACTIVE, 'activated_at' => now()->toDateTimeString()],
                amount: (float) $deposit->amount
            );

            // Ensure initial collection schedule exists
            if ($deposit->collections()->count() === 0) {
                DepositCollection::create([
                    'deposit_id' => $deposit->id,
                    'customer_id' => $deposit->customer_id,
                    'branch_id' => $deposit->branch_id,
                    'agent_id' => $deposit->agent_id,
                    'installment_number' => 1,
                    'due_date' => $deposit->start_date ?? now()->toDateString(),
                    'due_amount' => $deposit->amount,
                    'paid_amount' => 0.00,
                    'status' => DepositCollection::STATUS_DUE,
                ]);
            }

            // Ensure agent commission is calculated
            try {
                app(CommissionService::class)->calculateAndCreateForDeposit($deposit, auth()->user());
            } catch (\Exception $e) {
                // Non-blocking
            }
        });

        // Automatically promote agent and upline if promotional targets are complete
        try {
            if ($deposit->agent) {
                app(CareerPerformanceService::class)->checkAndAutoPromote($deposit->agent, auth()->user());
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' is now ACTIVE.");
    }

    /**
     * Mature deposit: ACTIVE -> MATURED
     */
    public function mature(Deposit $deposit)
    {
        $this->authorizeApprovalAction($deposit);

        if (! $deposit->canBeMatured()) {
            return redirect()->back()
                ->with('error', 'Invalid transition: Only ACTIVE deposits can be matured.');
        }

        DB::transaction(function () use ($deposit) {
            $oldStatus = $deposit->status;
            $deposit->update([
                'status' => Deposit::STATUS_MATURED,
                'matured_at' => now(),
            ]);

            AuditLog::record(
                event: 'deposit_matured',
                description: "Deposit [{$deposit->deposit_number}] was matured.",
                auditable: $deposit,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => Deposit::STATUS_MATURED, 'matured_at' => now()->toDateTimeString()],
                amount: (float) $deposit->amount
            );
        });

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' is now MATURED.");
    }

    /**
     * Close deposit: MATURED or ACTIVE -> CLOSED
     */
    public function close(Request $request, Deposit $deposit)
    {
        $this->authorizeApprovalAction($deposit);

        if (! $deposit->canBeClosed()) {
            return redirect()->back()
                ->with('error', "Invalid transition: Deposit cannot be closed from status '{$deposit->status}'.");
        }

        // If closing an ACTIVE deposit, check premature closure rules
        $prematureDetails = null;
        if ($deposit->isActive()) {
            $prematureDetails = $deposit->calculatePrematureClosureDetails(now());
            if (! $prematureDetails['allowed']) {
                return redirect()->back()
                    ->with('error', 'Premature closure rejected: '.$prematureDetails['reason']);
            }
        }

        DB::transaction(function () use ($deposit, $request, $prematureDetails) {
            $oldStatus = $deposit->status;
            $closureNotes = $request->filled('closure_notes') ? trim($request->closure_notes) : null;
            if ($prematureDetails && $prematureDetails['is_premature']) {
                $prematureSummary = 'Premature closure: Payout ₹'.number_format($prematureDetails['payout_amount'], 2)
                    ." (Penalty: {$prematureDetails['penalty_percent']}% = ₹".number_format($prematureDetails['penalty_amount'], 2).')';
                $closureNotes = $closureNotes ? "{$closureNotes} | {$prematureSummary}" : $prematureSummary;
            }

            $deposit->update([
                'status' => Deposit::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'notes' => $closureNotes ? trim(($deposit->notes ? $deposit->notes."\n" : '').'Closure note: '.$closureNotes) : $deposit->notes,
            ]);

            AuditLog::record(
                event: 'deposit_closed',
                description: "Deposit [{$deposit->deposit_number}] was CLOSED by ".auth()->user()->name.($prematureDetails && $prematureDetails['is_premature'] ? " (Premature closure, payout: ₹{$prematureDetails['payout_amount']})" : '').'.',
                auditable: $deposit,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => Deposit::STATUS_CLOSED,
                    'closed_at' => now()->toDateTimeString(),
                    'closed_by' => auth()->id(),
                    'premature' => $prematureDetails['is_premature'] ?? false,
                    'payout_amount' => $prematureDetails['payout_amount'] ?? (float) $deposit->maturity_amount,
                ],
                amount: (float) ($prematureDetails['payout_amount'] ?? $deposit->amount)
            );
        });

        return redirect()->back()
            ->with('success', "Deposit '{$deposit->deposit_number}' has been CLOSED.");
    }

    /**
     * Audit history for deposits.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            abort(403, 'Access denied: Agents are not permitted to view deposit history.');
        }

        if ($user && $user->isStaff() && ! $user->isAdmin() && ! ($user->hasPermission('view-deposits') || $user->hasPermission('manage-deposits'))) {
            abort(403, 'Access denied: View deposits privileges required.');
        }

        $restrictedBranchId = $this->getRestrictedBranchId();
        $restrictedAgentId = $this->getRestrictedAgentId();

        $query = AuditLog::with('user')
            ->where('auditable_type', Deposit::class)
            ->latest();

        // Scope logs if branch or agent restricted
        if ($restrictedBranchId !== null || $restrictedAgentId !== null) {
            $depositIdsQuery = Deposit::query();
            if ($restrictedBranchId !== null) {
                $depositIdsQuery->where('branch_id', $restrictedBranchId);
            }
            if ($restrictedAgentId !== null) {
                $depositIdsQuery->where('agent_id', $restrictedAgentId);
            }
            $depositIds = $depositIdsQuery->pluck('id');
            $query->whereIn('auditable_id', $depositIds);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('user_name', 'like', "%{$term}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.deposits.history', compact('logs'));
    }

    /**
     * Remove the specified deposit from storage along with its collections and commissions.
     */
    public function destroy(Deposit $deposit): RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! ($user->isSuperAdmin() || $user->isAdmin() || $user->can('delete-deposits'))) {
            abort(403, 'Unauthorized: You do not have permission to delete deposit accounts.');
        }

        $depositNumber = $deposit->deposit_number;
        $depositId = $deposit->id;
        $amount = $deposit->amount;

        DB::transaction(function () use ($deposit) {
            $lockedDeposit = Deposit::where('id', $deposit->id)->lockForUpdate()->first();

            // Delete linked commissions
            $lockedDeposit->commissions()->delete();

            // Delete linked collection payments and collections
            $lockedDeposit->collectionPayments()->delete();
            $lockedDeposit->collections()->delete();

            // Revert lead status only if no other active/non-deleted deposits remain for this lead
            if ($lockedDeposit->lead_id) {
                $hasOtherDeposits = Deposit::where('lead_id', $lockedDeposit->lead_id)
                    ->where('id', '!=', $lockedDeposit->id)
                    ->exists();

                if (! $hasOtherDeposits) {
                    Lead::where('id', $lockedDeposit->lead_id)->update([
                        'status' => Lead::STATUS_INTERESTED,
                        'converted_at' => null,
                    ]);
                }
            }

            AuditLog::record(
                event: 'deposit_deleted',
                description: "Permanently deleted deposit account #{$lockedDeposit->deposit_number} (Principal: ₹{$lockedDeposit->amount}).",
                auditable: $lockedDeposit,
                oldValues: $lockedDeposit->toArray()
            );

            $lockedDeposit->delete();
        });

        return redirect()->route('admin.deposits.index')
            ->with('success', "Deposit account #{$depositNumber} deleted successfully.");
    }

    /**
     * Find an existing customer from explicit selection, converted lead, or matching branch/name/mobile.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function findExistingDepositCustomer(array $validated, ?Lead $lead): ?Customer
    {
        if (! empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            if (! $customer || $customer->status !== Customer::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected account holder is not ACTIVE. Only active accounts can open deposit accounts.',
                ]);
            }

            return $customer;
        }

        if ($lead && $lead->converted_customer_id) {
            $convertedCustomer = Customer::find($lead->converted_customer_id);
            if ($convertedCustomer) {
                if ($convertedCustomer->status !== Customer::STATUS_ACTIVE) {
                    throw ValidationException::withMessages([
                        'lead_id' => 'The customer linked to this lead is not ACTIVE. Only active accounts can open deposit accounts.',
                    ]);
                }

                return $convertedCustomer;
            }
        }

        $name = trim((string) ($validated['manual_customer_name'] ?? ''));
        if ($name === '' && $lead) {
            $name = trim((string) $lead->name);
        }

        $mobile = trim((string) ($validated['manual_customer_mobile'] ?? ''));
        if ($mobile === '' && $lead) {
            $mobile = trim((string) ($lead->mobile ?? ''));
        }

        if ($name === '' || $mobile === '') {
            return null;
        }

        $existingCustomer = Customer::where('branch_id', $validated['branch_id'])
            ->whereRaw('TRIM(mobile) = ?', [$mobile])
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->orderByDesc('id')
            ->first();

        if ($existingCustomer && $existingCustomer->status !== Customer::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'manual_customer_name' => 'A matching customer account exists but is currently inactive.',
            ]);
        }

        return $existingCustomer;
    }

    /**
     * Create a new active customer record from deposit submission or lead details.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function createDepositCustomer(array $validated, ?Lead $lead, Agent $agent): Customer
    {
        $manualName = trim((string) ($validated['manual_customer_name'] ?? ''));
        $manualMobile = trim((string) ($validated['manual_customer_mobile'] ?? ''));

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => $manualName !== '' ? $manualName : trim((string) $lead?->name),
            'customer_type' => 'individual',
            'mobile' => $manualMobile !== '' ? $manualMobile : ($lead?->mobile ?? null),
            'email' => $lead?->email,
            'date_of_birth' => null,
            'address' => $lead?->address,
            'pin_code' => null,
            'branch_id' => $validated['branch_id'],
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => auth()->id(),
        ]);

        AuditLog::record(
            event: 'customer_created',
            description: $lead
                ? "Created customer [{$customer->name}] from lead [{$lead->name}]."
                : "Created customer [{$customer->name}] during deposit registration.",
            auditable: $customer,
            newValues: $customer->only(['customer_number', 'name', 'mobile', 'email', 'branch_id', 'agent_id', 'status'])
        );

        return $customer;
    }
}
