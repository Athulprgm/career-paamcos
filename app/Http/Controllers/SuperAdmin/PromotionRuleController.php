<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\FinancialYear;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromotionRuleController extends Controller
{
    public static function getCalculationPeriods(): array
    {
        return [
            PromotionRule::PERIOD_ALL_TIME => 'All Time (Cumulative)',
            PromotionRule::PERIOD_FINANCIAL_YEAR => 'Financial Year (1 Apr – 31 Mar)',
            PromotionRule::PERIOD_CURRENT_MONTH => 'Current Calendar Month',
            PromotionRule::PERIOD_LAST_30_DAYS => 'Last 30 Days',
            PromotionRule::PERIOD_LAST_90_DAYS => 'Last 90 Days',
            PromotionRule::PERIOD_LAST_365_DAYS => 'Last 365 Days',
            PromotionRule::PERIOD_CALENDAR_YEAR => 'Current Calendar Year',
        ];
    }

    public static function getApprovalRoles(): array
    {
        return [
            PromotionRule::ROLE_SUPER_ADMIN => 'Super Admin Only',
            PromotionRule::ROLE_ADMIN => 'Branch Admin or Super Admin',
            PromotionRule::ROLE_ANY_ADMIN => 'Any Administrator',
        ];
    }

    public static function getDepositStatuses(): array
    {
        return [
            Deposit::STATUS_ACTIVE => 'Active',
            Deposit::STATUS_APPROVED => 'Approved',
            Deposit::STATUS_MATURED => 'Matured',
        ];
    }

    public static function getGlobalPromotionTargets(): array
    {
        $globalTargets = [
            'rd_composition_percentage' => (float) (DB::table('settings')->where('key', 'career.rd_composition_percentage')->value('value') ?? 10.00),
            'fd_composition_percentage' => (float) (DB::table('settings')->where('key', 'career.fd_composition_percentage')->value('value') ?? 90.00),
            'apply_tenure_targets' => PromotionRule::where('status', PromotionRule::STATUS_ACTIVE)->where('apply_tenure_targets', true)->exists(),
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
        ];
        $storedTenureJson = DB::table('settings')->where('key', 'career.tenure_target_percentages')->value('value');
        if ($storedTenureJson) {
            $decoded = json_decode($storedTenureJson, true);
            if (is_array($decoded)) {
                $globalTargets['tenure_target_percentages'] = array_replace_recursive($globalTargets['tenure_target_percentages'], $decoded);
            }
        }

        return $globalTargets;
    }

    /**
     * Display a listing of dynamic promotion rules.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = PromotionRule::query()
            ->with(['fromRank', 'toRank', 'qualifyingRank', 'financialYear', 'creator'])
            ->withCount('promotionRequests');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('financial_year_id')) {
            if ($request->financial_year_id === 'global') {
                $query->whereNull('financial_year_id');
            } else {
                $query->where('financial_year_id', $request->financial_year_id);
            }
        }

        if ($request->filled('from_rank_id')) {
            $query->where('from_rank_id', $request->from_rank_id);
        }

        if ($request->filled('to_rank_id')) {
            $query->where('to_rank_id', $request->to_rank_id);
        }

        if ($request->filled('period')) {
            $query->where('calculation_period', $request->period);
        }

        if ($request->filled('approval_role')) {
            if ($request->approval_role === 'auto') {
                $query->where('requires_approval', false);
            } else {
                $query->where('requires_approval', true)
                    ->where('authorized_approval_role', $request->approval_role);
            }
        }

        $rules = $query->latest('id')->paginate(15)->withQueryString();

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('superadmin.promotion-rules._table', compact('rules'))->render(),
                'total' => $rules->total(),
                'first_item' => $rules->firstItem() ?? 0,
                'last_item' => $rules->lastItem() ?? 0,
                'has_pages' => $rules->hasPages(),
            ]);
        }
        $ranks = CareerLevel::ordered()->get();
        $financialYears = FinancialYear::ordered()->get();
        $calculationPeriods = self::getCalculationPeriods();
        $approvalRoles = self::getApprovalRoles();
        $depositStatuses = self::getDepositStatuses();
        $globalTargets = self::getGlobalPromotionTargets();

        $totalCount = PromotionRule::count();
        $activeCount = PromotionRule::where('status', PromotionRule::STATUS_ACTIVE)->count();
        $autoPromoteCount = PromotionRule::where('status', PromotionRule::STATUS_ACTIVE)->where('requires_approval', false)->count();

        return view('superadmin.promotion-rules.index', compact(
            'rules',
            'ranks',
            'financialYears',
            'calculationPeriods',
            'approvalRoles',
            'depositStatuses',
            'globalTargets',
            'totalCount',
            'activeCount',
            'autoPromoteCount'
        ));
    }

    /**
     * Show the form for creating a new promotion rule.
     */
    public function create(): View
    {
        $ranks = CareerLevel::ordered()->get();
        $financialYears = FinancialYear::ordered()->get();
        $calculationPeriods = self::getCalculationPeriods();
        $approvalRoles = self::getApprovalRoles();
        $depositStatuses = self::getDepositStatuses();
        $globalTargets = self::getGlobalPromotionTargets();
        $tenureTargets = $globalTargets['tenure_target_percentages'] ?? PromotionRule::DEFAULT_TENURE_TARGETS;

        return view('superadmin.promotion-rules.create', compact('ranks', 'financialYears', 'calculationPeriods', 'approvalRoles', 'depositStatuses', 'tenureTargets', 'globalTargets'));
    }

    /**
     * Store a newly created promotion rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'from_rank_id' => ['required', 'exists:ranks,id'],
            'to_rank_id' => ['required', 'exists:ranks,id', 'different:from_rank_id'],
            'financial_year_id' => ['nullable', 'exists:financial_years,id'],
            'min_individual_deposit' => ['nullable', 'numeric', 'min:0'],
            'min_team_deposit' => ['nullable', 'numeric', 'min:0'],
            'rd_composition_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fd_composition_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'apply_tenure_targets' => ['nullable', 'boolean'],
            'tenure_target_percentages' => ['nullable', 'array'],
            'min_qualifying_direct_facilitators' => ['nullable', 'integer', 'min:0'],
            'qualifying_rank_id' => ['nullable', 'exists:ranks,id'],
            'qualifying_agent_tiers' => ['nullable', 'array'],
            'qualifying_agent_tiers.*.count' => ['nullable', 'integer', 'min:0'],
            'qualifying_agent_tiers.*.rank_id' => ['nullable', 'exists:ranks,id'],
            'calculation_period' => ['required', Rule::in([
                PromotionRule::PERIOD_ALL_TIME,
                PromotionRule::PERIOD_FINANCIAL_YEAR,
                PromotionRule::PERIOD_CURRENT_MONTH,
                PromotionRule::PERIOD_LAST_30_DAYS,
                PromotionRule::PERIOD_LAST_90_DAYS,
                PromotionRule::PERIOD_LAST_365_DAYS,
                PromotionRule::PERIOD_CALENDAR_YEAR,
            ])],
            'qualifying_deposit_statuses' => ['nullable', 'array'],
            'qualifying_deposit_statuses.*' => ['string', Rule::in([Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED])],
            'required_agent_status' => ['required', Rule::in(['active', 'any'])],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'requires_approval' => ['nullable', 'boolean'],
            'authorized_approval_role' => ['required', Rule::in([
                PromotionRule::ROLE_SUPER_ADMIN,
                PromotionRule::ROLE_ADMIN,
                PromotionRule::ROLE_ANY_ADMIN,
            ])],
            'status' => ['required', Rule::in([PromotionRule::STATUS_ACTIVE, PromotionRule::STATUS_INACTIVE])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['min_individual_deposit'] = $validated['min_individual_deposit'] ?? 0;
        $validated['min_team_deposit'] = $validated['min_team_deposit'] ?? 0;
        $validated['rd_composition_percentage'] = isset($validated['rd_composition_percentage']) ? (float) $validated['rd_composition_percentage'] : 10.00;
        $validated['fd_composition_percentage'] = isset($validated['fd_composition_percentage']) ? (float) $validated['fd_composition_percentage'] : 90.00;

        if (abs(($validated['rd_composition_percentage'] + $validated['fd_composition_percentage']) - 100.00) > 0.01) {
            return back()->withInput()->withErrors([
                'rd_composition_percentage' => 'Compulsory RD % and Maximum FD % must total exactly 100%. (Current sum: '.($validated['rd_composition_percentage'] + $validated['fd_composition_percentage']).'%)',
                'fd_composition_percentage' => 'Compulsory RD % and Maximum FD % must total exactly 100%.',
            ]);
        }
        $validated['apply_tenure_targets'] = (bool) $request->boolean('apply_tenure_targets', false);

        $validated['tenure_target_percentages'] = $request->has('tenure_target_percentages') && is_array($request->tenure_target_percentages)
            ? $this->normalizeTenureTargetPercentages($request)
            : PromotionRule::DEFAULT_TENURE_TARGETS;

        $tiers = $this->normalizeQualifyingAgentTiers($validated['qualifying_agent_tiers'] ?? []);
        $validated['qualifying_agent_tiers'] = ! empty($tiers) ? $tiers : null;

        // Keep legacy summary columns synced across all configured tiers for backward-compat.
        $validated['min_qualifying_direct_facilitators'] = ! empty($tiers)
            ? array_sum(array_column($tiers, 'count'))
            : ($validated['min_qualifying_direct_facilitators'] ?? 0);
        $validated['qualifying_rank_id'] = ! empty($tiers) ? $tiers[0]['rank_id'] : ($validated['qualifying_rank_id'] ?? null);

        $validated['min_business_amount'] = $validated['min_team_deposit'];
        $validated['min_downline_agents'] = $validated['min_qualifying_direct_facilitators'];

        $fromRank = CareerLevel::find($validated['from_rank_id']);
        if ($fromRank) {
            $nextRank = $fromRank->nextLevel();
            if (! $nextRank) {
                return back()->withInput()->withErrors([
                    'from_rank_id' => "The designation '{$fromRank->name}' is at the maximum career level and cannot be promoted further.",
                ]);
            }

            if ((int) $validated['to_rank_id'] !== (int) $nextRank->id) {
                return back()->withInput()->withErrors([
                    'to_rank_id' => "The promoted designation must be the sequential next rank ({$nextRank->name}).",
                ]);
            }

            // Prevent duplicate promotion rules between the same designations in store
            $dupQuery = PromotionRule::where('from_rank_id', $validated['from_rank_id'])
                ->where('to_rank_id', $validated['to_rank_id']);
            if (! empty($validated['financial_year_id'])) {
                $dupQuery->where('financial_year_id', $validated['financial_year_id']);
            } else {
                $dupQuery->whereNull('financial_year_id');
            }
            $existingRule = $dupQuery->first();

            if ($existingRule) {
                $fyLabel = $existingRule->financialYear ? " for {$existingRule->financialYear->name}" : ' (Global Policy)';

                return back()->withInput()->withErrors([
                    'from_rank_id' => "A promotion rule already exists between {$fromRank->name} and {$nextRank->name}{$fyLabel} (Rule: \"{$existingRule->name}\"). Please edit the existing rule instead of creating a duplicate.",
                ]);
            }
        }

        if (empty($validated['qualifying_deposit_statuses'])) {
            $validated['qualifying_deposit_statuses'] = [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED];
        }

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $rule = PromotionRule::create($validated);

        AuditLog::record(
            event: 'promotion_rule_created',
            description: "Created promotion rule [{$rule->name}] ({$rule->fromRank?->name} -> {$rule->toRank?->name}).",
            auditable: $rule,
            newValues: $rule->only([
                'name',
                'from_rank_id',
                'to_rank_id',
                'financial_year_id',
                'min_individual_deposit',
                'min_team_deposit',
                'min_qualifying_direct_facilitators',
                'calculation_period',
                'status',
            ])
        );

        return redirect()->route('superadmin.promotion-rules.index')
            ->with('success', "Promotion rule '{$rule->name}' created successfully.");
    }

    /**
     * Display the specified promotion rule.
     */
    public function show(PromotionRule $promotionRule): View
    {
        $promotionRule->load(['fromRank', 'toRank', 'qualifyingRank', 'financialYear', 'creator', 'updater']);

        $requests = $promotionRule->promotionRequests()
            ->with(['agent.branch', 'proposedLevel', 'requester', 'reviewer'])
            ->latest()
            ->paginate(10);

        $ranks = CareerLevel::ordered()->get();

        return view('superadmin.promotion-rules.show', compact('promotionRule', 'requests', 'ranks'));
    }

    /**
     * Show the form for editing the specified promotion rule.
     */
    public function edit(PromotionRule $promotionRule): View
    {
        $ranks = CareerLevel::ordered()->get();
        $financialYears = FinancialYear::ordered()->get();
        $calculationPeriods = self::getCalculationPeriods();
        $approvalRoles = self::getApprovalRoles();
        $depositStatuses = self::getDepositStatuses();
        $tenureTargets = $promotionRule->getTenureTargetPercentages();
        $globalTargets = self::getGlobalPromotionTargets();

        return view('superadmin.promotion-rules.edit', compact('promotionRule', 'ranks', 'financialYears', 'calculationPeriods', 'approvalRoles', 'depositStatuses', 'tenureTargets', 'globalTargets'));
    }

    /**
     * Update the specified promotion rule.
     */
    public function update(Request $request, PromotionRule $promotionRule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'from_rank_id' => ['required', 'exists:ranks,id'],
            'to_rank_id' => ['required', 'exists:ranks,id', 'different:from_rank_id'],
            'financial_year_id' => ['nullable', 'exists:financial_years,id'],
            'min_individual_deposit' => ['nullable', 'numeric', 'min:0'],
            'min_team_deposit' => ['nullable', 'numeric', 'min:0'],
            'rd_composition_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fd_composition_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'apply_tenure_targets' => ['nullable', 'boolean'],
            'tenure_target_percentages' => ['nullable', 'array'],
            'min_qualifying_direct_facilitators' => ['nullable', 'integer', 'min:0'],
            'qualifying_rank_id' => ['nullable', 'exists:ranks,id'],
            'qualifying_agent_tiers' => ['nullable', 'array'],
            'qualifying_agent_tiers.*.count' => ['nullable', 'integer', 'min:0'],
            'qualifying_agent_tiers.*.rank_id' => ['nullable', 'exists:ranks,id'],
            'calculation_period' => ['required', Rule::in([
                PromotionRule::PERIOD_ALL_TIME,
                PromotionRule::PERIOD_FINANCIAL_YEAR,
                PromotionRule::PERIOD_CURRENT_MONTH,
                PromotionRule::PERIOD_LAST_30_DAYS,
                PromotionRule::PERIOD_LAST_90_DAYS,
                PromotionRule::PERIOD_LAST_365_DAYS,
                PromotionRule::PERIOD_CALENDAR_YEAR,
            ])],
            'qualifying_deposit_statuses' => ['nullable', 'array'],
            'qualifying_deposit_statuses.*' => ['string', Rule::in([Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED])],
            'required_agent_status' => ['required', Rule::in(['active', 'any'])],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'requires_approval' => ['nullable', 'boolean'],
            'authorized_approval_role' => ['required', Rule::in([
                PromotionRule::ROLE_SUPER_ADMIN,
                PromotionRule::ROLE_ADMIN,
                PromotionRule::ROLE_ANY_ADMIN,
            ])],
            'status' => ['required', Rule::in([PromotionRule::STATUS_ACTIVE, PromotionRule::STATUS_INACTIVE])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['min_individual_deposit'] = $validated['min_individual_deposit'] ?? 0;
        $validated['min_team_deposit'] = $validated['min_team_deposit'] ?? 0;
        $validated['rd_composition_percentage'] = isset($validated['rd_composition_percentage']) ? (float) $validated['rd_composition_percentage'] : 10.00;
        $validated['fd_composition_percentage'] = isset($validated['fd_composition_percentage']) ? (float) $validated['fd_composition_percentage'] : 90.00;

        if (abs(($validated['rd_composition_percentage'] + $validated['fd_composition_percentage']) - 100.00) > 0.01) {
            return back()->withInput()->withErrors([
                'rd_composition_percentage' => 'Compulsory RD % and Maximum FD % must total exactly 100%. (Current sum: '.($validated['rd_composition_percentage'] + $validated['fd_composition_percentage']).'%)',
                'fd_composition_percentage' => 'Compulsory RD % and Maximum FD % must total exactly 100%.',
            ]);
        }
        $validated['apply_tenure_targets'] = (bool) $request->boolean('apply_tenure_targets', false);

        $validated['tenure_target_percentages'] = $request->has('tenure_target_percentages') && is_array($request->tenure_target_percentages)
            ? $this->normalizeTenureTargetPercentages($request)
            : ($promotionRule->tenure_target_percentages ?? PromotionRule::DEFAULT_TENURE_TARGETS);

        // Prevent duplicate active rule for the same rank progression
        if ($validated['status'] === PromotionRule::STATUS_ACTIVE) {
            $dupActiveQuery = PromotionRule::where('from_rank_id', $validated['from_rank_id'])
                ->where('to_rank_id', $validated['to_rank_id'])
                ->where('id', '!=', $promotionRule->id)
                ->where('status', PromotionRule::STATUS_ACTIVE);
            if (! empty($validated['financial_year_id'])) {
                $dupActiveQuery->where('financial_year_id', $validated['financial_year_id']);
            } else {
                $dupActiveQuery->whereNull('financial_year_id');
            }
            $existingRule = $dupActiveQuery->first();
            if ($existingRule) {
                $fyLabel = $existingRule->financialYear ? " for {$existingRule->financialYear->name}" : ' (Global Policy)';

                return back()->withInput()->withErrors([
                    'from_rank_id' => "Another active promotion rule for transition ({$existingRule->name}){$fyLabel} already exists. Please deactivate it first or edit that rule.",
                ]);
            }
        }

        $tiers = $this->normalizeQualifyingAgentTiers($validated['qualifying_agent_tiers'] ?? []);
        $validated['qualifying_agent_tiers'] = ! empty($tiers) ? $tiers : null;

        // Keep legacy summary columns synced across all configured tiers and team targets.
        $validated['min_qualifying_direct_facilitators'] = ! empty($tiers)
            ? array_sum(array_column($tiers, 'count'))
            : ($validated['min_qualifying_direct_facilitators'] ?? 0);
        $validated['qualifying_rank_id'] = ! empty($tiers) ? $tiers[0]['rank_id'] : ($validated['qualifying_rank_id'] ?? null);

        $validated['min_business_amount'] = $validated['min_team_deposit'];
        $validated['min_downline_agents'] = $validated['min_qualifying_direct_facilitators'];

        $fromRank = CareerLevel::find($validated['from_rank_id']);
        if ($fromRank) {
            $nextRank = $fromRank->nextLevel();
            if (! $nextRank) {
                return back()->withInput()->withErrors([
                    'from_rank_id' => "The designation '{$fromRank->name}' is at the maximum career level and cannot be promoted further.",
                ]);
            }

            if ((int) $validated['to_rank_id'] !== (int) $nextRank->id) {
                return back()->withInput()->withErrors([
                    'to_rank_id' => "The promoted designation must be the sequential next rank ({$nextRank->name}).",
                ]);
            }
        }

        if (empty($validated['qualifying_deposit_statuses'])) {
            $validated['qualifying_deposit_statuses'] = [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED];
        }

        $validated['updated_by'] = auth()->id();

        $oldValues = $promotionRule->only([
            'name',
            'from_rank_id',
            'to_rank_id',
            'financial_year_id',
            'min_individual_deposit',
            'min_team_deposit',
            'min_qualifying_direct_facilitators',
            'calculation_period',
            'status',
            'effective_from',
            'effective_to',
            'authorized_approval_role',
        ]);

        $promotionRule->update($validated);

        AuditLog::record(
            event: 'promotion_rule_updated',
            description: "Updated promotion rule [{$promotionRule->name}].",
            auditable: $promotionRule,
            oldValues: $oldValues,
            newValues: $promotionRule->only([
                'name',
                'from_rank_id',
                'to_rank_id',
                'financial_year_id',
                'min_individual_deposit',
                'min_team_deposit',
                'min_qualifying_direct_facilitators',
                'calculation_period',
                'status',
                'effective_from',
                'effective_to',
                'authorized_approval_role',
            ])
        );

        return redirect()->route('superadmin.promotion-rules.show', $promotionRule)
            ->with('success', "Promotion rule '{$promotionRule->name}' updated successfully.");
    }

    /**
     * Remove or soft-delete the specified promotion rule.
     */
    public function destroy(PromotionRule $promotionRule): RedirectResponse
    {
        $hasPendingRequests = $promotionRule->promotionRequests()
            ->where('status', PromotionRequest::STATUS_PENDING_REVIEW)
            ->exists();

        if ($hasPendingRequests) {
            return redirect()->route('superadmin.promotion-rules.index')
                ->with('error', "Cannot delete rule [{$promotionRule->name}] while pending promotion requests are actively referencing it. Please review or reject those requests first, or deactivate the rule.");
        }

        $ruleName = $promotionRule->name;
        $oldValues = $promotionRule->toArray();

        AuditLog::record(
            event: 'promotion_rule_deleted',
            description: "Deleted promotion rule [{$ruleName}].",
            auditable: $promotionRule,
            oldValues: $oldValues
        );

        $promotionRule->delete();

        return redirect()->route('superadmin.promotion-rules.index')
            ->with('success', "Promotion rule '{$ruleName}' deleted successfully.");
    }

    /**
     * Toggle the status of the promotion rule between active and inactive.
     */
    public function toggleStatus(PromotionRule $promotionRule): RedirectResponse
    {
        $newStatus = $promotionRule->isActive() ? PromotionRule::STATUS_INACTIVE : PromotionRule::STATUS_ACTIVE;
        $promotionRule->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        AuditLog::record(
            event: 'promotion_rule_status_toggled',
            description: "Promotion rule [{$promotionRule->name}] status changed to {$newStatus}.",
            auditable: $promotionRule,
            newValues: ['status' => $newStatus]
        );

        return redirect()->back()
            ->with('success', "Promotion rule status changed to {$newStatus} successfully.");
    }

    /**
     * Update global default compulsory conditions and promotion targets.
     * Optionally bulk-syncs all active promotion rules.
     */
    public function updateGlobalTargets(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rd_composition_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'fd_composition_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'apply_tenure_targets' => ['nullable', 'boolean'],
            'tenure_target_percentages' => ['required', 'array'],
            'sync_all_rules' => ['nullable', 'boolean'],
        ]);

        $applyTenureTargets = $request->boolean('apply_tenure_targets', false);
        $syncAllRules = $request->boolean('sync_all_rules', false);

        $rdPercent = (float) $validated['rd_composition_percentage'];
        $fdPercent = (float) $validated['fd_composition_percentage'];

        if (abs(($rdPercent + $fdPercent) - 100.00) > 0.01) {
            return back()->withInput()->withErrors([
                'rd_composition_percentage' => 'Compulsory RD % and Maximum FD % must total exactly 100%. (Current sum: '.($rdPercent + $fdPercent).'%)',
                'fd_composition_percentage' => 'Compulsory RD % and Maximum FD % must total exactly 100%.',
            ]);
        }

        $sanitizedTenureTargets = [
            'fd' => [
                '1' => (float) ($request->input('tenure_target_percentages.fd.1', $request->input('tenure_target_percentages.fd_rd.1', 20.0))),
                '2' => (float) ($request->input('tenure_target_percentages.fd.2', $request->input('tenure_target_percentages.fd_rd.2', 30.0))),
                '3' => (float) ($request->input('tenure_target_percentages.fd.3', $request->input('tenure_target_percentages.fd_rd.3', 40.0))),
                '5' => (float) ($request->input('tenure_target_percentages.fd.5', $request->input('tenure_target_percentages.fd_rd.5', 80.0))),
                '6' => (float) ($request->input('tenure_target_percentages.fd.6', $request->input('tenure_target_percentages.fd_rd.6', 80.0))),
            ],
            'rd' => [
                '1' => (float) ($request->input('tenure_target_percentages.rd.1', $request->input('tenure_target_percentages.fd_rd.1', 20.0))),
                '2' => (float) ($request->input('tenure_target_percentages.rd.2', $request->input('tenure_target_percentages.fd_rd.2', 30.0))),
                '3' => (float) ($request->input('tenure_target_percentages.rd.3', $request->input('tenure_target_percentages.fd_rd.3', 40.0))),
                '5' => (float) ($request->input('tenure_target_percentages.rd.5', $request->input('tenure_target_percentages.fd_rd.5', 80.0))),
                '6' => (float) ($request->input('tenure_target_percentages.rd.6', $request->input('tenure_target_percentages.fd_rd.6', 80.0))),
                '7' => (float) ($request->input('tenure_target_percentages.rd.7', $request->input('tenure_target_percentages.fd_rd.7', 90.0))),
            ],
            'dd' => [
                '1' => (float) ($request->input('tenure_target_percentages.dd.1', 20.0)),
                '2' => (float) ($request->input('tenure_target_percentages.dd.2', 40.0)),
                '3' => (float) ($request->input('tenure_target_percentages.dd.3', 50.0)),
            ],
        ];

        // Update settings table
        DB::table('settings')->updateOrInsert(
            ['key' => 'career.rd_composition_percentage'],
            ['value' => (string) $rdPercent, 'type' => 'float', 'group' => 'career', 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'career.fd_composition_percentage'],
            ['value' => (string) $fdPercent, 'type' => 'float', 'group' => 'career', 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'career.apply_tenure_targets'],
            ['value' => $applyTenureTargets ? '1' : '0', 'type' => 'boolean', 'group' => 'career', 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'career.tenure_target_percentages'],
            ['value' => json_encode($sanitizedTenureTargets), 'type' => 'json', 'group' => 'career', 'updated_at' => now()]
        );

        $affectedCount = 0;
        if ($syncAllRules) {
            $affectedCount = PromotionRule::query()->update([
                'rd_composition_percentage' => $rdPercent,
                'fd_composition_percentage' => $fdPercent,
                'apply_tenure_targets' => $applyTenureTargets,
                'tenure_target_percentages' => json_encode($sanitizedTenureTargets),
                'updated_by' => auth()->id(),
            ]);
        }

        AuditLog::record(
            event: 'promotion_global_targets_updated',
            description: "Updated global promotion targets and compulsory composition (RD: {$rdPercent}%, FD: {$fdPercent}%".($syncAllRules ? ", synced {$affectedCount} rules)" : ')'),
            newValues: [
                'rd_composition_percentage' => $rdPercent,
                'fd_composition_percentage' => $fdPercent,
                'apply_tenure_targets' => $applyTenureTargets,
                'tenure_target_percentages' => $sanitizedTenureTargets,
                'synced_rules_count' => $affectedCount,
            ]
        );

        $msg = 'Global promotion targets & compulsory conditions updated successfully.'.($syncAllRules ? " Applied to {$affectedCount} promotion rules." : '');

        return redirect()->route('superadmin.promotion-rules.index')->with('success', $msg);
    }

    /**
     * Normalise tenure-based promotion target credit percentages from the request.
     *
     * @return array{fd: array<string, float>, rd: array<string, float>, dd: array<string, float>}
     */
    protected function normalizeTenureTargetPercentages(Request $request): array
    {
        return [
            'fd' => [
                '1' => (float) ($request->input('tenure_target_percentages.fd.1', $request->input('tenure_target_percentages.fd_rd.1', 20.0))),
                '2' => (float) ($request->input('tenure_target_percentages.fd.2', $request->input('tenure_target_percentages.fd_rd.2', 30.0))),
                '3' => (float) ($request->input('tenure_target_percentages.fd.3', $request->input('tenure_target_percentages.fd_rd.3', 40.0))),
                '5' => (float) ($request->input('tenure_target_percentages.fd.5', $request->input('tenure_target_percentages.fd_rd.5', 80.0))),
                '6' => (float) ($request->input('tenure_target_percentages.fd.6', $request->input('tenure_target_percentages.fd_rd.6', 80.0))),
            ],
            'rd' => [
                '1' => (float) ($request->input('tenure_target_percentages.rd.1', $request->input('tenure_target_percentages.fd_rd.1', 20.0))),
                '2' => (float) ($request->input('tenure_target_percentages.rd.2', $request->input('tenure_target_percentages.fd_rd.2', 30.0))),
                '3' => (float) ($request->input('tenure_target_percentages.rd.3', $request->input('tenure_target_percentages.fd_rd.3', 40.0))),
                '5' => (float) ($request->input('tenure_target_percentages.rd.5', $request->input('tenure_target_percentages.fd_rd.5', 80.0))),
                '6' => (float) ($request->input('tenure_target_percentages.rd.6', $request->input('tenure_target_percentages.fd_rd.6', 80.0))),
                '7' => (float) ($request->input('tenure_target_percentages.rd.7', $request->input('tenure_target_percentages.fd_rd.7', 90.0))),
            ],
            'dd' => [
                '1' => (float) ($request->input('tenure_target_percentages.dd.1', 20.0)),
                '2' => (float) ($request->input('tenure_target_percentages.dd.2', 40.0)),
                '3' => (float) ($request->input('tenure_target_percentages.dd.3', 50.0)),
            ],
        ];
    }

    /**
     * Normalise and filter qualifying agent tier definitions.
     *
     * @param  array<int, array{count?: int|string|null, rank_id?: int|string|null}>  $rawTiers
     * @return array<int, array{count: int, rank_id: int|null}>
     */
    protected function normalizeQualifyingAgentTiers(array $rawTiers): array
    {
        return collect($rawTiers)
            ->filter(fn (array $tier): bool => isset($tier['count']) && (int) $tier['count'] > 0)
            ->map(fn (array $tier): array => [
                'count' => (int) $tier['count'],
                'rank_id' => isset($tier['rank_id']) && $tier['rank_id'] !== '' ? (int) $tier['rank_id'] : null,
            ])
            ->values()
            ->all();
    }
}
