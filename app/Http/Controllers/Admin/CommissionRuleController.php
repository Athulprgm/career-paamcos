<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CommissionRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommissionRuleController extends Controller
{
    /**
     * Redirect legacy commission rule pages to the consolidated commission pages.
     */
    protected function redirectToCommissionDashboard(): RedirectResponse
    {
        if (auth()->check() && auth()->user()->isSuperAdmin()) {
            return redirect()->route('superadmin.fd-commissions.index');
        }

        return redirect()->route('admin.commissions.index');
    }

    /**
     * Display a listing of commission rules (legacy page redirected to Deposit Commissions).
     */
    public function index(Request $request): RedirectResponse
    {
        return $this->redirectToCommissionDashboard();
    }

    /**
     * Legacy create page redirected to Deposit Commissions.
     */
    public function create(): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized: Only administrators can configure commission rules.');
        }

        return $this->redirectToCommissionDashboard();
    }

    /**
     * Store a newly created commission rule.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized: Only administrators can configure commission rules.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50', 'unique:commission_rules,code'],
            'commission_type' => ['required', 'string', 'in:PERCENTAGE,FIXED'],
            'calculation_basis' => ['required', 'string', 'in:COLLECTION_AMOUNT,DEPOSIT_AMOUNT'],
            'rate' => ['required', 'numeric', 'min:0.01'],
            'deposit_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'rank_id' => ['nullable', 'integer', 'exists:ranks,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = CommissionRule::generateRuleCode();
        }

        $validated['created_by'] = auth()->id();

        $rule = CommissionRule::create($validated);

        AuditLog::record(
            event: 'commission_rule_created',
            description: "Created commission rule [{$rule->code}] '{$rule->name}' ({$rule->commission_type}: {$rule->rate}).",
            auditable: $rule,
            newValues: $rule->only(['name', 'code', 'commission_type', 'calculation_basis', 'rate', 'deposit_scheme_id', 'rank_id', 'effective_from', 'effective_to', 'status']),
            amount: (float) $rule->rate
        );

        return redirect()->route('admin.commission-rules.index')
            ->with('success', "Commission rule '{$rule->name}' ({$rule->code}) created successfully.");
    }

    /**
     * Legacy show page redirected to Deposit Commissions.
     */
    public function show(CommissionRule $commissionRule): RedirectResponse
    {
        return $this->redirectToCommissionDashboard();
    }

    /**
     * Legacy edit page redirected to Deposit Commissions.
     */
    public function edit(CommissionRule $commissionRule): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized: Only administrators can configure commission rules.');
        }

        return $this->redirectToCommissionDashboard();
    }

    /**
     * Update commission rule.
     */
    public function update(Request $request, CommissionRule $commissionRule)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized: Only administrators can configure commission rules.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('commission_rules', 'code')->ignore($commissionRule->id)],
            'commission_type' => ['required', 'string', 'in:PERCENTAGE,FIXED'],
            'calculation_basis' => ['required', 'string', 'in:COLLECTION_AMOUNT,DEPOSIT_AMOUNT'],
            'rate' => ['required', 'numeric', 'min:0.01'],
            'deposit_scheme_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'rank_id' => ['nullable', 'integer', 'exists:ranks,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldValues = $commissionRule->only(['name', 'code', 'commission_type', 'calculation_basis', 'rate', 'status']);
        $commissionRule->update($validated);

        AuditLog::record(
            event: 'commission_rule_updated',
            description: "Updated commission rule [{$commissionRule->code}] '{$commissionRule->name}'.",
            auditable: $commissionRule,
            oldValues: $oldValues,
            newValues: $commissionRule->only(['name', 'code', 'commission_type', 'calculation_basis', 'rate', 'status']),
            amount: (float) $commissionRule->rate
        );

        return redirect()->route('admin.commission-rules.index')
            ->with('success', "Commission rule '{$commissionRule->name}' updated successfully.");
    }

    /**
     * Toggle status: ACTIVE <-> INACTIVE.
     */
    public function toggleStatus(CommissionRule $commissionRule)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized: Only administrators can modify commission rules.');
        }

        $newStatus = $commissionRule->isActive() ? CommissionRule::STATUS_INACTIVE : CommissionRule::STATUS_ACTIVE;
        $commissionRule->update(['status' => $newStatus]);

        AuditLog::record(
            event: 'commission_rule_status_toggled',
            description: "Changed status of commission rule [{$commissionRule->code}] to {$newStatus}.",
            auditable: $commissionRule,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()
            ->with('success', "Rule '{$commissionRule->code}' status updated to {$newStatus}.");
    }
}
