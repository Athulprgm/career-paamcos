<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
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

        return $user ? ($user->branch_id ?? $user->staffProfile?->branch_id ?? $user->agentProfile?->branch_id) : null;
    }

    /**
     * Enforce branch restriction on a specific branch ID.
     */
    protected function authorizeBranchAccess(?int $branchId): void
    {
        $restricted = $this->getRestrictedBranchId();
        if ($restricted !== null && (int) $branchId !== (int) $restricted) {
            abort(403, 'Unauthorized access: You are restricted to your assigned branch.');
        }
    }

    /**
     * Display a listing of leads for the branch (or all branches for Super Admin).
     */
    public function index(Request $request)
    {
        $restrictedBranchId = $this->getRestrictedBranchId();

        $query = Lead::with(['branch', 'agent.rank', 'interestedScheme', 'convertedCustomer']);

        if ($restrictedBranchId !== null) {
            $query->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $leads = $query->latest('id')->paginate(15)->withQueryString();

        // Agents dropdown (restricted to branch if branch admin, free-field agents accessible across branches)
        $agentsQuery = Agent::active()->orderBy('name');
        if ($restrictedBranchId !== null) {
            $agentsQuery->where(function ($q) use ($restrictedBranchId) {
                $q->where('branch_id', $restrictedBranchId)
                    ->orWhereNull('branch_id');
            });
        } elseif ($request->filled('branch_id')) {
            $agentsQuery->where('branch_id', $request->branch_id);
        }
        $agents = $agentsQuery->get();

        // Branches dropdown (for super admin)
        $branches = $restrictedBranchId !== null
            ? Branch::where('id', $restrictedBranchId)->get()
            : Branch::active()->orderBy('name')->get();

        // Status counts
        $countQuery = Lead::query();
        if ($restrictedBranchId !== null) {
            $countQuery->where('branch_id', $restrictedBranchId);
        } elseif ($request->filled('branch_id')) {
            $countQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('agent_id')) {
            $countQuery->where('agent_id', $request->agent_id);
        }

        $statusCounts = [
            'all' => (clone $countQuery)->count(),
            'new' => (clone $countQuery)->where('status', Lead::STATUS_NEW)->count(),
            'contacted' => (clone $countQuery)->where('status', Lead::STATUS_CONTACTED)->count(),
            'interested' => (clone $countQuery)->where('status', Lead::STATUS_INTERESTED)->count(),
            'converted' => (clone $countQuery)->where('status', Lead::STATUS_CONVERTED)->count(),
            'not_interested' => (clone $countQuery)->where('status', Lead::STATUS_NOT_INTERESTED)->count(),
        ];

        $selectedAgent = $request->filled('agent_id') ? Agent::find($request->agent_id) : null;

        return view('admin.leads.index', compact(
            'leads',
            'agents',
            'branches',
            'statusCounts',
            'restrictedBranchId',
            'selectedAgent'
        ));
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead)
    {
        $this->authorizeBranchAccess($lead->branch_id);

        $lead->load([
            'branch',
            'agent.rank',
            'interestedScheme',
            'convertedCustomer',
            'creator',
            'deposits.scheme',
            'deposits.commissions',
        ]);

        $existingCustomer = Customer::where('mobile', $lead->mobile)->first();

        return view('admin.leads.show', compact('lead', 'existingCustomer'));
    }

    /**
     * Update the lead's status or follow-up details.
     */
    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeBranchAccess($lead->branch_id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Lead::STATUS_NEW,
                Lead::STATUS_CONTACTED,
                Lead::STATUS_INTERESTED,
                Lead::STATUS_CONVERTED,
                Lead::STATUS_NOT_INTERESTED,
            ])],
            'follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $lead->status;
        $lead->update($validated);

        AuditLog::record(
            event: 'lead_status_updated',
            description: "Updated lead [{$lead->name}] status from [{$oldStatus}] to [{$lead->status}].",
            auditable: $lead,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $lead->status]
        );

        return redirect()->back()
            ->with('success', "Lead '{$lead->name}' status updated to {$lead->status}.");
    }

    /**
     * Delete the specified lead from storage.
     */
    public function destroy(Lead $lead): RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! ($user->isSuperAdmin() || $user->isAdmin() || $user->can('delete-leads'))) {
            abort(403, 'Unauthorized: You do not have permission to delete leads.');
        }

        $leadName = $lead->name;
        $leadCode = $lead->lead_code ?? '#'.$lead->id;

        DB::transaction(function () use ($lead, $leadName, $leadCode) {
            Deposit::where('lead_id', $lead->id)->update(['lead_id' => null]);

            AuditLog::record(
                event: 'lead_deleted',
                description: "Deleted agent lead [{$leadName}] ({$leadCode}).",
                auditable: $lead,
                oldValues: $lead->toArray()
            );

            $lead->delete();
        });

        return redirect()->route('admin.leads.index')
            ->with('success', "Lead '{$leadName}' deleted successfully.");
    }
}
