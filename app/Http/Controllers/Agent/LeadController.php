<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /**
     * Authorize lead access to ensure the logged-in agent only accesses their own leads.
     * Since agents are free agents operating across branches/areas, they can access
     * any lead assigned to them regardless of which branch it was registered in.
     */
    protected function authorizeLeadAccess(Lead $lead): void
    {
        $user = auth()->user();
        $agent = $user->agentProfile;

        if (! $agent) {
            abort(403, 'Unauthorized access: No agent profile linked.');
        }

        if ((int) $lead->agent_id !== (int) $agent->id) {
            abort(403, 'Unauthorized access: You can only view or manage your own leads.');
        }
    }

    /**
     * Display a listing of leads for the logged-in agent.
     */
    public function index(Request $request)
    {
        $agent = auth()->user()->agentProfile;
        if (! $agent) {
            abort(403, 'No agent profile linked.');
        }

        // Auto-sync any leads for this agent that have active or approved deposits to CONVERTED
        Lead::where('agent_id', $agent->id)
            ->where('status', '!=', Lead::STATUS_CONVERTED)
            ->whereHas('deposits', function ($q) {
                $q->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_PENDING_APPROVAL]);
            })
            ->update([
                'status' => Lead::STATUS_CONVERTED,
                'converted_at' => now(),
            ]);

        $query = Lead::with(['branch', 'interestedScheme', 'convertedCustomer'])
            ->where('agent_id', $agent->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('follow_up_filter')) {
            if ($request->follow_up_filter === 'today') {
                $query->whereDate('follow_up_date', today());
            } elseif ($request->follow_up_filter === 'upcoming') {
                $query->whereDate('follow_up_date', '>=', today());
            } elseif ($request->follow_up_filter === 'overdue') {
                $query->whereDate('follow_up_date', '<', today())
                    ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED]);
            }
        }

        if ($request->filled('scheme_id')) {
            $query->where('interested_scheme_id', $request->scheme_id);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        if ($sort === 'amount_desc') {
            $query->orderBy('expected_amount', 'desc')->orderBy('id', 'desc');
        } elseif ($sort === 'amount_asc') {
            $query->orderBy('expected_amount', 'asc')->orderBy('id', 'desc');
        } elseif ($sort === 'follow_up_asc') {
            $query->orderByRaw('follow_up_date IS NULL, follow_up_date ASC')->orderBy('id', 'desc');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name', 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $leads = $query->paginate(15)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('agent.leads.partials.table', compact('leads'))->render(),
                'total' => $leads->total(),
                'first_item' => $leads->firstItem() ?? 0,
                'last_item' => $leads->lastItem() ?? 0,
                'count_text' => $leads->total() === 1 ? '1 lead found' : number_format($leads->total()).' leads found',
            ]);
        }

        $branches = Branch::active()->orderBy('name')->get();
        $schemes = DepositScheme::active()->orderBy('name')->get();

        // Summary counts for this agent
        $statusCounts = [
            'all' => Lead::where('agent_id', $agent->id)->count(),
            'new' => Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_NEW)->count(),
            'contacted' => Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_CONTACTED)->count(),
            'interested' => Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_INTERESTED)->count(),
            'converted' => Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_CONVERTED)->count(),
            'not_interested' => Lead::where('agent_id', $agent->id)->where('status', Lead::STATUS_NOT_INTERESTED)->count(),
        ];

        // Pipeline business metrics
        $pipelineMetrics = [
            'total_pipeline_value' => (float) Lead::where('agent_id', $agent->id)
                ->whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_CONTACTED, Lead::STATUS_INTERESTED])
                ->sum('expected_amount'),
            'converted_value' => (float) Lead::where('agent_id', $agent->id)
                ->where('status', Lead::STATUS_CONVERTED)
                ->sum('expected_amount'),
            'due_today_count' => Lead::where('agent_id', $agent->id)
                ->whereDate('follow_up_date', today())
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
                ->count(),
            'overdue_count' => Lead::where('agent_id', $agent->id)
                ->whereDate('follow_up_date', '<', today())
                ->whereNotIn('status', [Lead::STATUS_CONVERTED, Lead::STATUS_NOT_INTERESTED])
                ->count(),
        ];

        return view('agent.leads.index', compact('leads', 'statusCounts', 'pipelineMetrics', 'branches', 'schemes'));
    }

    /**
     * Show form to record a new lead.
     */
    public function create()
    {
        $agent = auth()->user()->agentProfile;
        $schemes = DepositScheme::active()->orderBy('name')->get();
        $branches = Branch::active()->orderBy('name')->get();

        return view('agent.leads.create', compact('schemes', 'branches', 'agent'));
    }

    /**
     * Store newly created lead.
     */
    public function store(Request $request)
    {
        $agent = auth()->user()->agentProfile;
        if (! $agent) {
            abort(403, 'No agent profile linked.');
        }

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'interested_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in([
                Lead::STATUS_NEW,
                Lead::STATUS_CONTACTED,
                Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED,
                Lead::STATUS_NOT_INTERESTED,
            ])],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $branchId = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : (int) $agent->branch_id;

        $lead = Lead::create([
            'branch_id' => $branchId,
            'agent_id' => $agent->id,
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'interested_scheme_id' => $validated['interested_scheme_id'] ?? null,
            'expected_amount' => $validated['expected_amount'] ?? null,
            'status' => $validated['status'],
            'follow_up_date' => $validated['follow_up_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AuditLog::record(
            event: 'lead_created',
            description: "Agent [{$agent->name}] recorded a new business lead [{$lead->name}] ({$lead->mobile}) for branch [".($lead->branch?->name ?? $lead->branch_id).'].',
            auditable: $lead,
            newValues: $lead->only(['branch_id', 'name', 'mobile', 'status', 'interested_scheme_id', 'expected_amount'])
        );

        return redirect()->route('agent.leads.show', $lead)
            ->with('success', "Lead '{$lead->name}' registered successfully.");
    }

    /**
     * Show detailed lead record.
     */
    public function show(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        // Auto-sync to CONVERTED if deposit exists for this lead
        if (! $lead->isConverted() && $lead->deposits()->whereIn('status', [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_PENDING_APPROVAL])->exists()) {
            $deposit = $lead->deposits()->latest('id')->first();
            $lead->update([
                'status' => Lead::STATUS_CONVERTED,
                'converted_customer_id' => $deposit?->customer_id ?? $lead->converted_customer_id,
                'converted_at' => $lead->converted_at ?? now(),
            ]);
            $lead->refresh();
        }

        $lead->load(['branch', 'interestedScheme', 'convertedCustomer', 'deposits.scheme', 'creator']);

        return view('agent.leads.show', compact('lead'));
    }

    /**
     * Show form to edit lead.
     */
    public function edit(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $schemes = DepositScheme::active()->orderBy('name')->get();
        $branches = Branch::active()->orderBy('name')->get();

        return view('agent.leads.edit', compact('lead', 'schemes', 'branches'));
    }

    /**
     * Update lead details.
     */
    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'interested_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'expected_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in([
                Lead::STATUS_NEW,
                Lead::STATUS_CONTACTED,
                Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED,
                Lead::STATUS_NOT_INTERESTED,
            ])],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $branchId = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : (int) $lead->branch_id;

        $oldValues = $lead->only(['branch_id', 'name', 'mobile', 'email', 'status', 'follow_up_date', 'notes']);

        $lead->update([
            'branch_id' => $branchId,
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'interested_scheme_id' => $validated['interested_scheme_id'] ?? null,
            'expected_amount' => $validated['expected_amount'] ?? null,
            'status' => $validated['status'],
            'follow_up_date' => $validated['follow_up_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::record(
            event: 'lead_updated',
            description: "Lead [{$lead->name}] was updated by agent [".auth()->user()->name.'].',
            auditable: $lead,
            oldValues: $oldValues,
            newValues: $lead->only(['branch_id', 'name', 'mobile', 'email', 'status', 'follow_up_date', 'notes'])
        );

        return redirect()->route('agent.leads.show', $lead)
            ->with('success', "Lead '{$lead->name}' updated successfully.");
    }

    /**
     * Fast inline status and follow-up updating.
     */
    public function updateQuickStatus(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Lead::STATUS_NEW,
                Lead::STATUS_CONTACTED,
                Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED,
                Lead::STATUS_NOT_INTERESTED,
            ])],
            'follow_up_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $lead->status;
        $updates = ['status' => $validated['status']];

        if ($validated['status'] === Lead::STATUS_CONVERTED && ! $lead->converted_at) {
            $updates['converted_at'] = now();
        }

        if ($request->has('follow_up_date')) {
            $updates['follow_up_date'] = $validated['follow_up_date'];
        }

        if (! empty($validated['note'])) {
            $timestamp = now()->format('d M Y, h:i A');
            $existingNotes = trim($lead->notes ?? '');
            $newNote = "[{$timestamp}] ".trim($validated['note']);
            $updates['notes'] = $existingNotes ? $newNote."\n\n".$existingNotes : $newNote;
        }

        $lead->update($updates);

        AuditLog::record(
            event: 'lead_status_updated',
            description: "Lead [{$lead->name}] status updated from [{$oldStatus}] to [{$lead->status}] by agent [".auth()->user()->name.'].',
            auditable: $lead,
            oldValues: ['status' => $oldStatus],
            newValues: $updates
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lead '{$lead->name}' updated to {$lead->status}.",
                'status' => $lead->status,
                'badge_class' => $lead->getStatusBadgeClass(),
            ]);
        }

        return redirect()->back()->with('success', "Lead '{$lead->name}' updated to {$lead->status}.");
    }

    /**
     * Remove or archive lead record.
     */
    public function destroy(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        if ($lead->isConverted()) {
            return redirect()->back()->with('error', 'Converted leads cannot be removed because they are linked to customer and deposit accounts.');
        }

        $leadName = $lead->name;
        $lead->delete();

        AuditLog::record(
            event: 'lead_deleted',
            description: 'Agent ['.auth()->user()->name."] removed lead [{$leadName}].",
            auditable: $lead,
            oldValues: $lead->toArray()
        );

        return redirect()->route('agent.leads.index')
            ->with('success', "Lead '{$leadName}' has been removed.");
    }
}
