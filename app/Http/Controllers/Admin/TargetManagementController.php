<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentRankTarget;
use App\Models\AgentTargetAssignment;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\FinancialYear;
use App\Services\AgentTargetCalculationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TargetManagementController extends Controller
{
    public function __construct(
        protected AgentTargetCalculationService $targetService
    ) {}

    /**
     * Get branch restriction for current user.
     * Null means unrestricted (Super Admin).
     */
    protected function getRestrictedBranchId(): ?int
    {
        $user = auth()->user();
        if (! $user || $user->isSuperAdmin()) {
            return null;
        }

        if ($user->isBranchAdmin()) {
            return $user->branch_id;
        }

        return $user->branch_id ?? $user->staffProfile?->branch_id ?? $user->agentProfile?->branch_id ?? null;
    }

    /**
     * Get agent restriction for current user (null for non-agent).
     */
    protected function getRestrictedAgentId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            $agent = Agent::where('user_id', $user->id)->first();

            return $agent?->id ?? -1;
        }

        return null;
    }

    /**
     * Authorize branch access.
     */
    protected function authorizeBranchAccess(?int $branchId): void
    {
        $restrictedBranchId = $this->getRestrictedBranchId();
        if ($restrictedBranchId !== null && $branchId !== null && (int) $branchId !== (int) $restrictedBranchId) {
            abort(403, 'Unauthorized access: Cross-branch target management is restricted.');
        }
    }

    /**
     * 1. Overview Dashboard -> Consolidated into Promotion Rules & Career Progression.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if ($user && $user->isAgent() && ! $user->isAdmin()) {
            return redirect()->route('agent.performance');
        }

        return redirect()->route('superadmin.promotion-rules.index');
    }

    /**
     * 2. Target Configurations Directory -> Consolidated into Promotion Rules.
     */
    public function configurations(Request $request): RedirectResponse
    {
        return redirect()->route('superadmin.promotion-rules.index');
    }

    /**
     * 3. Show create configuration form -> Consolidated into Promotion Rules.
     */
    public function createConfiguration(Request $request): RedirectResponse
    {
        return redirect()->route('superadmin.promotion-rules.create');
    }

    /**
     * 4. Store a newly created rank target configuration
     */
    public function storeConfiguration(Request $request): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access: Only Super Admin can configure rank targets.');
        }

        $validated = $request->validate([
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'rank_id' => ['required', 'integer', 'exists:ranks,id'],
            'total_deposit_target' => ['required', 'numeric', 'min:0'],
            'fd_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'rd_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'recruitment_target' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $fdPct = (float) $validated['fd_percentage'];
        $rdPct = (float) $validated['rd_percentage'];

        // Strict 100% check
        if (abs(($fdPct + $rdPct) - 100.00) > 0.01) {
            throw ValidationException::withMessages([
                'fd_percentage' => 'FD Percentage and RD Percentage must total exactly 100%. (Current sum: '.($fdPct + $rdPct).'%)',
                'rd_percentage' => 'FD Percentage and RD Percentage must total exactly 100%.',
            ]);
        }

        $existing = AgentRankTarget::where('financial_year_id', $validated['financial_year_id'])
            ->where('rank_id', $validated['rank_id'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'rank_id' => 'A target configuration for this rank and financial year already exists. Please edit the existing configuration instead.',
            ]);
        }

        $total = (float) $validated['total_deposit_target'];
        $fdAmount = round($total * ($fdPct / 100.0), 2);
        $rdAmount = round($total * ($rdPct / 100.0), 2);

        $target = AgentRankTarget::create([
            'financial_year_id' => $validated['financial_year_id'],
            'rank_id' => $validated['rank_id'],
            'total_deposit_target' => $total,
            'fd_percentage' => $fdPct,
            'rd_percentage' => $rdPct,
            'fd_target_amount' => $fdAmount,
            'rd_target_amount' => $rdAmount,
            'recruitment_target' => (int) $validated['recruitment_target'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $rank = CareerLevel::find($validated['rank_id']);
        $fy = FinancialYear::find($validated['financial_year_id']);

        AuditLog::record(
            event: 'target_configured',
            description: "Super Admin configured Target for Rank [{$rank?->name}] in {$fy?->name}: Deposit ₹".number_format($total, 2)." (FD: {$fdPct}%, RD: {$rdPct}%), Recruits: {$target->recruitment_target}.",
            auditable: $target,
            newValues: $target->toArray()
        );

        return redirect()->route('superadmin.promotion-rules.index')
            ->with('success', "Target for {$rank?->name} in {$fy?->name} created successfully.");
    }

    /**
     * 5. Show edit target configuration form -> Consolidated into Promotion Rules.
     */
    public function editConfiguration(AgentRankTarget $target): RedirectResponse
    {
        return redirect()->route('superadmin.promotion-rules.index');
    }

    /**
     * 6. Update rank target configuration
     */
    public function updateConfiguration(Request $request, AgentRankTarget $target): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access: Only Super Admin can edit rank targets.');
        }

        $validated = $request->validate([
            'total_deposit_target' => ['required', 'numeric', 'min:0'],
            'fd_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'rd_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'recruitment_target' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $fdPct = (float) $validated['fd_percentage'];
        $rdPct = (float) $validated['rd_percentage'];

        if (abs(($fdPct + $rdPct) - 100.00) > 0.01) {
            throw ValidationException::withMessages([
                'fd_percentage' => 'FD Percentage and RD Percentage must total exactly 100%. (Current sum: '.($fdPct + $rdPct).'%)',
                'rd_percentage' => 'FD Percentage and RD Percentage must total exactly 100%.',
            ]);
        }

        $total = (float) $validated['total_deposit_target'];
        $fdAmount = round($total * ($fdPct / 100.0), 2);
        $rdAmount = round($total * ($rdPct / 100.0), 2);

        $oldValues = $target->only(['total_deposit_target', 'fd_percentage', 'rd_percentage', 'recruitment_target', 'status']);

        $target->update([
            'total_deposit_target' => $total,
            'fd_percentage' => $fdPct,
            'rd_percentage' => $rdPct,
            'fd_target_amount' => $fdAmount,
            'rd_target_amount' => $rdAmount,
            'recruitment_target' => (int) $validated['recruitment_target'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        $target->load(['financialYear', 'rank']);

        AuditLog::record(
            event: 'target_updated',
            description: "Super Admin updated Target for Rank [{$target->rank?->name}] in {$target->financialYear?->name}: Deposit ₹".number_format($total, 2).", Recruits: {$target->recruitment_target}.",
            auditable: $target,
            oldValues: $oldValues,
            newValues: $target->toArray()
        );

        return redirect()->route('superadmin.promotion-rules.index')
            ->with('success', "Target for {$target->rank?->name} updated successfully.");
    }

    /**
     * 7. Toggle target configuration status
     */
    public function toggleConfigurationStatus(AgentRankTarget $target): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action: Only Super Admin can modify target status.');
        }

        $newStatus = $target->status === AgentRankTarget::STATUS_ACTIVE
            ? AgentRankTarget::STATUS_INACTIVE
            : AgentRankTarget::STATUS_ACTIVE;

        $target->update(['status' => $newStatus]);

        AuditLog::record(
            event: 'target_status_toggled',
            description: "Target configuration for Rank [{$target->rank?->name}] in [{$target->financialYear?->name}] status changed to {$newStatus}.",
            auditable: $target,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()->with('success', "Target configuration status updated to {$newStatus}.");
    }

    /**
     * 8. Individual Agent Performance List -> Redirect to Career Performance Matrix.
     */
    public function agentPerformance(Request $request): RedirectResponse
    {
        return redirect()->route('admin.career.performance');
    }

    /**
     * 9. Individual Agent Target Details Deep-Dive -> Redirect to Career Agent Performance.
     */
    public function agentDetails(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorizeBranchAccess($agent->branch_id);

        return redirect()->route('admin.career.agent-performance', $agent);
    }

    /**
     * 10. Target History & Revisions Audit Report -> Redirect to Career History.
     */
    public function history(Request $request): RedirectResponse
    {
        return redirect()->route('admin.career.history');
    }

    /**
     * 11. Create a new Financial Year (1 April – 31 March)
     */
    public function storeFinancialYear(Request $request): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access: Only Super Admin can create Financial Years.');
        }

        $validated = $request->validate([
            'start_year' => ['required', 'integer', 'min:2020', 'max:2040'],
            'is_current' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:active,closed,upcoming,inactive'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $startYear = (int) $validated['start_year'];
        $calc = FinancialYear::calculatePeriodForStartYear($startYear);

        if (FinancialYear::where('code', $calc['code'])->exists()) {
            throw ValidationException::withMessages([
                'start_year' => "Financial Year {$calc['code']} already exists in the system.",
            ]);
        }

        $isCurrent = ! empty($validated['is_current']);

        // Resolve source FY targets to clone from before switching current flag
        $sourceFyId = FinancialYear::current()?->id ?? FinancialYear::where('status', FinancialYear::STATUS_ACTIVE)->first()?->id ?? 1;

        DB::transaction(function () use ($calc, $isCurrent, $validated, $sourceFyId) {
            if ($isCurrent) {
                FinancialYear::where('is_current', true)->update(['is_current' => false]);
            }

            $fy = FinancialYear::create([
                'name' => $calc['name'],
                'code' => $calc['code'],
                'start_date' => $calc['start_date'],
                'end_date' => $calc['end_date'],
                'is_current' => $isCurrent,
                'status' => $validated['status'],
                'description' => $validated['description'] ?? "Financial Year {$calc['name']}",
                'created_by' => auth()->id(),
            ]);

            // Auto-clone current rank targets as initial baseline for the new FY
            $currentTargets = AgentRankTarget::where('financial_year_id', $sourceFyId)->get();
            if ($currentTargets->isEmpty()) {
                $anyFyId = AgentRankTarget::query()->value('financial_year_id');
                if ($anyFyId) {
                    $currentTargets = AgentRankTarget::where('financial_year_id', $anyFyId)->get();
                }
            }

            foreach ($currentTargets as $ct) {
                AgentRankTarget::create([
                    'financial_year_id' => $fy->id,
                    'rank_id' => $ct->rank_id,
                    'total_deposit_target' => $ct->total_deposit_target,
                    'fd_percentage' => $ct->fd_percentage,
                    'rd_percentage' => $ct->rd_percentage,
                    'fd_target_amount' => $ct->fd_target_amount,
                    'rd_target_amount' => $ct->rd_target_amount,
                    'recruitment_target' => $ct->recruitment_target,
                    'status' => 'active',
                    'description' => "Initial target configuration for {$fy->name} based on previous year",
                    'created_by' => auth()->id(),
                ]);
            }

            AuditLog::record(
                event: 'financial_year_created',
                description: "Super Admin created new Financial Year [{$fy->name}] ({$fy->start_date} to {$fy->end_date}).",
                auditable: $fy,
                newValues: $fy->toArray()
            );
        });

        return redirect()->route('superadmin.promotion-rules.index')
            ->with('success', "Financial Year {$calc['name']} created successfully with default rank target configurations.");
    }

    /**
     * 12. Set Financial Year as Current
     */
    public function setCurrentFinancialYear(FinancialYear $financialYear): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action: Only Super Admin can change the active Financial Year.');
        }

        DB::transaction(function () use ($financialYear) {
            FinancialYear::where('is_current', true)->update(['is_current' => false]);
            $financialYear->update(['is_current' => true, 'status' => FinancialYear::STATUS_ACTIVE]);

            AuditLog::record(
                event: 'current_financial_year_changed',
                description: "Super Admin set [{$financialYear->name}] as the active Current Financial Year.",
                auditable: $financialYear
            );
        });

        return redirect()->back()
            ->with('success', "Financial Year {$financialYear->name} is now set as the active Current Financial Year.");
    }

    /**
     * 13. Assign Authorized Custom Target Revision to an Agent (Super Admin only)
     */
    public function assignCustomTarget(Request $request, Agent $agent): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access: Only Super Admin can authorize custom target revisions.');
        }

        $validated = $request->validate([
            'financial_year_id' => ['required', 'integer', 'exists:financial_years,id'],
            'total_deposit_target' => ['required', 'numeric', 'min:0'],
            'fd_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'rd_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'recruitment_target' => ['required', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'revision_reason' => ['required', 'string', 'max:1000'],
        ]);

        $fdPct = (float) $validated['fd_percentage'];
        $rdPct = (float) $validated['rd_percentage'];

        if (abs(($fdPct + $rdPct) - 100.00) > 0.01) {
            throw ValidationException::withMessages([
                'fd_percentage' => 'FD Percentage and RD Percentage must total exactly 100%.',
            ]);
        }

        $total = (float) $validated['total_deposit_target'];
        $fdAmount = round($total * ($fdPct / 100.0), 2);
        $rdAmount = round($total * ($rdPct / 100.0), 2);

        $assignment = DB::transaction(function () use ($agent, $validated, $total, $fdPct, $rdPct, $fdAmount, $rdAmount) {
            // Close prior active assignment if effective_to was open
            AgentTargetAssignment::where('agent_id', $agent->id)
                ->where('financial_year_id', $validated['financial_year_id'])
                ->where('status', AgentTargetAssignment::STATUS_ACTIVE)
                ->whereNull('effective_to')
                ->update(['effective_to' => Carbon::parse($validated['effective_from'])->subDay()->toDateString()]);

            return AgentTargetAssignment::create([
                'agent_id' => $agent->id,
                'financial_year_id' => $validated['financial_year_id'],
                'rank_id' => $agent->rank_id,
                'total_deposit_target' => $total,
                'fd_percentage' => $fdPct,
                'rd_percentage' => $rdPct,
                'fd_target_amount' => $fdAmount,
                'rd_target_amount' => $rdAmount,
                'recruitment_target' => (int) $validated['recruitment_target'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'is_custom' => true,
                'revision_reason' => $validated['revision_reason'],
                'assigned_by' => auth()->id(),
                'status' => AgentTargetAssignment::STATUS_ACTIVE,
            ]);
        });

        AuditLog::record(
            event: 'custom_target_assigned',
            description: "Super Admin authorized custom target revision for Agent [{$agent->name}] ({$agent->agent_id}): Deposit ₹".number_format($total, 2).", Recruits: {$assignment->recruitment_target}. Reason: {$validated['revision_reason']}",
            auditable: $assignment,
            newValues: $assignment->toArray()
        );

        return redirect()->back()
            ->with('success', "Custom target revision for {$agent->name} applied successfully with effective date {$assignment->effective_from->toDateString()}.");
    }
}
