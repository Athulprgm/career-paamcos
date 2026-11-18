<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreFdCommissionSchemeRequest;
use App\Http\Requests\SuperAdmin\UpdateFdCommissionSchemeRequest;
use App\Models\AgentRank;
use App\Models\DepositScheme;
use App\Services\FdCommissionSchemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FdCommissionSchemeController extends Controller
{
    public function __construct(
        protected FdCommissionSchemeService $schemeService
    ) {}

    /**
     * Display a listing of deposit commission schemes.
     */
    public function index(Request $request): View
    {
        $baseQuery = DepositScheme::query();

        if ($request->filled('scheme_type')) {
            $schemeType = strtoupper((string) $request->scheme_type);
            if ($schemeType === 'FD') {
                $baseQuery->where(function ($q) {
                    $q->where('scheme_type', DepositScheme::TYPE_FD)
                        ->orWhere('scheme_type', 'fd')
                        ->orWhere('name', 'like', '%Fixed Deposit%')
                        ->orWhere('name', 'like', '%FD%');
                });
            } else {
                $baseQuery->where('scheme_type', $schemeType);
            }
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $baseQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Stats reflect active scope (search and scheme type filters)
        $totalCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('status', DepositScheme::STATUS_ACTIVE)->count();
        $inactiveCount = (clone $baseQuery)->where('status', DepositScheme::STATUS_INACTIVE)->count();
        $configuredCount = (clone $baseQuery)->where(function ($q) {
            $q->where('total_commission_percentage', '>', 0)
                ->orWhereHas('rankCommissions', function ($rq) {
                    $rq->where('is_eligible', true);
                });
        })->count();
        $avgCommission = (float) ((clone $baseQuery)
            ->where('status', DepositScheme::STATUS_ACTIVE)
            ->where('total_commission_percentage', '>', 0)
            ->avg('total_commission_percentage') ?? 0.00);

        $query = (clone $baseQuery)->with([
            'rankCommissions.rank',
            'creator',
        ])->withCount(['deposits', 'commissionLedgers']);

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('commission_status')) {
            $commissionStatus = strtolower(trim((string) $request->commission_status));
            if ($commissionStatus === 'configured') {
                $query->where(function ($q) {
                    $q->where('total_commission_percentage', '>', 0)
                        ->orWhereHas('rankCommissions', function ($rq) {
                            $rq->where('is_eligible', true);
                        });
                });
            } elseif ($commissionStatus === 'unconfigured') {
                $query->where('total_commission_percentage', '<=', 0)
                    ->whereDoesntHave('rankCommissions', function ($rq) {
                        $rq->where('is_eligible', true);
                    });
            }
        }

        $schemes = $query->orderBy('name')->paginate(15)->withQueryString();

        $totalRanksCount = AgentRank::count();
        $schemeTypes = DepositScheme::getSchemeTypes();
        $typeCounts = DepositScheme::selectRaw('scheme_type, count(*) as count')
            ->groupBy('scheme_type')
            ->pluck('count', 'scheme_type')
            ->toArray();

        return view('superadmin.fd-commissions.index', compact(
            'schemes',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'configuredCount',
            'avgCommission',
            'totalRanksCount',
            'schemeTypes',
            'typeCounts'
        ));
    }

    /**
     * Show the form for creating a new deposit commission scheme or configuring an existing scheme.
     */
    public function create(Request $request): View
    {
        $depositSchemes = DepositScheme::with('rankCommissions')->orderBy('name')->get();
        $ranks = AgentRank::orderBy('sort_order')->orderBy('level')->get();
        $schemeTypes = DepositScheme::getSchemeTypes();
        $collectionFrequencies = DepositScheme::getCollectionFrequencies();
        $tenureUnits = DepositScheme::getTenureUnits();
        $payoutFrequencies = DepositScheme::getInterestPayoutFrequencies();

        $selectedSchemeId = $request->query('deposit_scheme_id', $request->query('scheme_id', $request->query('clone_from')));
        $selectedScheme = $selectedSchemeId ? $depositSchemes->firstWhere('id', (int) $selectedSchemeId) : null;
        $isClone = $request->has('clone_from') || $request->query('mode') === 'clone';

        // Structured JSON payload for instantaneous client-side auto-fetch without extra roundtrip
        $schemesJson = $depositSchemes->mapWithKeys(function ($s) {
            return [
                $s->id => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                    'scheme_type' => $s->scheme_type,
                    'collection_frequency' => $s->collection_frequency,
                    'interest_payout_frequency' => $s->interest_payout_frequency ?? DepositScheme::PAYOUT_AT_MATURITY,
                    'interest_rate' => (float) $s->interest_rate,
                    'senior_citizen_extra_rate' => (float) ($s->senior_citizen_extra_rate ?? 0.00),
                    'is_double' => $s->isDouble(),
                    'tenure' => (int) $s->tenure,
                    'tenure_unit' => $s->tenure_unit ?? DepositScheme::TENURE_MONTHS,
                    'min_amount' => $s->min_amount !== null ? (float) $s->min_amount : 1000.00,
                    'max_amount' => $s->max_amount !== null ? (float) $s->max_amount : null,
                    'status' => $s->status ?? DepositScheme::STATUS_ACTIVE,
                    'description' => $s->description ?? '',
                    'total_commission_percentage' => (float) $s->total_commission_percentage,
                    'promotion_target_percentage' => $s->promotion_target_percentage !== null ? (float) $s->promotion_target_percentage : null,
                    // Group rank commissions by year so the JS can show the correct
                    // percentages when the user selects a different payout year.
                    'rank_commissions_by_year' => $s->rankCommissions
                        ->groupBy('payout_year')
                        ->map(fn ($rows) => $rows->map(fn ($rc) => [
                            'rank_id' => $rc->rank_id,
                            'is_eligible' => (bool) $rc->is_eligible,
                            'commission_percentage' => (float) $rc->commission_percentage,
                        ])->values()->toArray())
                        ->toArray(),
                    'rank_commissions' => $s->rankCommissions
                        ->sortByDesc('payout_year')
                        ->unique('rank_id')
                        ->map(fn ($rc) => [
                            'rank_id' => $rc->rank_id,
                            'is_eligible' => (bool) $rc->is_eligible,
                            'commission_percentage' => (float) $rc->commission_percentage,
                        ])->values()->toArray(),
                ],
            ];
        })->toArray();

        return view('superadmin.fd-commissions.create', compact(
            'depositSchemes',
            'schemesJson',
            'selectedScheme',
            'isClone',
            'ranks',
            'schemeTypes',
            'collectionFrequencies',
            'tenureUnits',
            'payoutFrequencies'
        ));
    }

    /**
     * Return JSON details of a deposit scheme for dynamic AJAX auto-fetch.
     */
    public function getSchemeDetails(DepositScheme $fdCommissionScheme): JsonResponse
    {
        $fdCommissionScheme->load(['rankCommissions', 'activeInterestRates']);

        return response()->json([
            'id' => $fdCommissionScheme->id,
            'name' => $fdCommissionScheme->name,
            'code' => $fdCommissionScheme->code,
            'scheme_type' => $fdCommissionScheme->scheme_type,
            'collection_frequency' => $fdCommissionScheme->collection_frequency,
            'interest_payout_frequency' => $fdCommissionScheme->interest_payout_frequency,
            'interest_rate' => (float) $fdCommissionScheme->interest_rate,
            'senior_citizen_extra_rate' => (float) ($fdCommissionScheme->senior_citizen_extra_rate ?? 0.00),
            'is_double' => $fdCommissionScheme->isDouble(),
            'tenure' => (int) $fdCommissionScheme->tenure,
            'tenure_unit' => $fdCommissionScheme->tenure_unit,
            'min_amount' => $fdCommissionScheme->min_amount !== null ? (float) $fdCommissionScheme->min_amount : null,
            'max_amount' => $fdCommissionScheme->max_amount !== null ? (float) $fdCommissionScheme->max_amount : null,
            'status' => $fdCommissionScheme->status,
            'promotion_target_percentage' => $fdCommissionScheme->promotion_target_percentage !== null ? (float) $fdCommissionScheme->promotion_target_percentage : null,
            'description' => $fdCommissionScheme->description,
            'tenure_rates' => $fdCommissionScheme->activeInterestRates->map(function ($r) {
                return [
                    'id' => $r->id,
                    'tenure' => (int) $r->tenure,
                    'tenure_unit' => $r->tenure_unit,
                    'interest_rate' => (float) $r->interest_rate,
                    'senior_citizen_extra_rate' => (float) ($r->senior_citizen_extra_rate ?? 0.00),
                    'label' => $r->getFormattedTenure().' @ '.number_format((float) $r->interest_rate, 2).'% p.a.',
                ];
            })->values()->toArray(),
            'rank_commissions_by_year' => $fdCommissionScheme->rankCommissions
                ->groupBy('payout_year')
                ->map(fn ($rows) => $rows->map(fn ($rc) => [
                    'rank_id' => $rc->rank_id,
                    'is_eligible' => (bool) $rc->is_eligible,
                    'commission_percentage' => (float) $rc->commission_percentage,
                ])->values()->toArray())
                ->toArray(),
            'rank_commissions' => $fdCommissionScheme->rankCommissions
                ->sortByDesc('payout_year')
                ->unique('rank_id')
                ->map(fn ($rc) => [
                    'rank_id' => $rc->rank_id,
                    'is_eligible' => (bool) $rc->is_eligible,
                    'commission_percentage' => (float) $rc->commission_percentage,
                ])->values()->toArray(),
            'active_years' => $fdCommissionScheme->rankCommissions
                ->pluck('payout_year')
                ->map(fn ($y) => (int) ($y ?: 1))
                ->unique()
                ->sort()
                ->values()
                ->toArray(),
        ]);
    }

    /**
     * Store a newly created deposit scheme in storage or update commissions for an existing scheme.
     */
    public function store(StoreFdCommissionSchemeRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $payoutYear = (int) ($validated['payout_year'] ?? 1);
            $existing = $request->filled('deposit_scheme_id') ? DepositScheme::find((int) $request->deposit_scheme_id) : null;
            $isClone = $request->has('clone_from')
                || $request->input('mode') === 'clone'
                || ($request->input('action_mode') === 'create_new' && $existing && trim((string) $request->input('name')) !== trim((string) $existing->name));
            $existingScheme = (! $isClone && $existing) ? $existing : null;

            if ($existingScheme) {
                $scheme = $this->schemeService->updateScheme(
                    $existingScheme,
                    $validated,
                    $request->input('ranks', []),
                    $request->input('notes')
                );

                return redirect()->route('superadmin.fd-commissions.show', $scheme)
                    ->with('success', "Deposit Scheme '{$scheme->name}' ({$scheme->code}) commission configuration saved successfully.");
            }

            // Create new dynamic scheme
            $scheme = $this->schemeService->createScheme(
                $validated,
                $request->input('ranks', [])
            );

            return redirect()->route('superadmin.fd-commissions.show', $scheme)
                ->with('success', "Deposit Scheme '{$scheme->name}' ({$scheme->code}) Year {$payoutYear} rank commission rules created successfully.");
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }
    }

    /**
     * Display the specified deposit scheme details, rank matrix, and audit history.
     */
    public function show(DepositScheme $fdCommissionScheme): View
    {
        $fdCommissionScheme->load([
            'rankCommissions.rank',
            'commissionHistories.user',
            'creator',
        ])->loadCount(['deposits', 'commissionLedgers']);

        $deleteSafety = $this->schemeService->canDeleteScheme($fdCommissionScheme);

        $recentDeposits = $fdCommissionScheme->deposits()
            ->with(['customer', 'branch', 'agent'])
            ->latest()
            ->take(5)
            ->get();

        $allRanks = AgentRank::orderBy('sort_order')->orderBy('level')->get();

        $rankCommissionsMap = $fdCommissionScheme->rankCommissions
            ->sortByDesc('payout_year')
            ->unique('rank_id')
            ->keyBy('rank_id');

        $totalCommission = (float) $fdCommissionScheme->total_commission_percentage;
        $eligibleCount = $fdCommissionScheme->rankCommissions->where('is_eligible', true)->unique('rank_id')->count();

        return view('superadmin.fd-commissions.show', [
            'scheme' => $fdCommissionScheme,
            'deleteSafety' => $deleteSafety,
            'recentDeposits' => $recentDeposits,
            'allRanks' => $allRanks,
            'rankCommissionsMap' => $rankCommissionsMap,
            'totalCommission' => $totalCommission,
            'eligibleCount' => $eligibleCount,
        ]);
    }

    /**
     * Show the form for editing the specified deposit scheme.
     */
    public function edit(DepositScheme $fdCommissionScheme): View
    {
        $fdCommissionScheme->load('rankCommissions');

        $rankCommissionsByYear = $fdCommissionScheme->rankCommissions
            ->groupBy('payout_year')
            ->map(fn ($rows) => $rows->map(fn ($rc) => [
                'rank_id' => $rc->rank_id,
                'is_eligible' => (bool) $rc->is_eligible,
                'commission_percentage' => (float) $rc->commission_percentage,
                'payout_year' => (int) ($rc->payout_year ?? 1),
            ])->values()->toArray())
            ->toArray();

        $activeYears = array_map('intval', array_keys($rankCommissionsByYear));
        if (empty($activeYears)) {
            $activeYears = [1];
        }
        sort($activeYears, SORT_NUMERIC);

        $existingRankCommissions = $fdCommissionScheme->rankCommissions
            ->sortByDesc('payout_year')
            ->unique('rank_id')
            ->keyBy('rank_id');

        $ranks = AgentRank::orderBy('sort_order')->orderBy('level')->get();
        $schemeTypes = DepositScheme::getSchemeTypes();
        $collectionFrequencies = DepositScheme::getCollectionFrequencies();
        $tenureUnits = DepositScheme::getTenureUnits();
        $payoutFrequencies = DepositScheme::getInterestPayoutFrequencies();

        return view('superadmin.fd-commissions.edit', [
            'scheme' => $fdCommissionScheme,
            'ranks' => $ranks,
            'existingRankCommissions' => $existingRankCommissions,
            'rankCommissionsByYear' => $rankCommissionsByYear,
            'activeYears' => $activeYears,
            'schemeTypes' => $schemeTypes,
            'collectionFrequencies' => $collectionFrequencies,
            'tenureUnits' => $tenureUnits,
            'payoutFrequencies' => $payoutFrequencies,
        ]);
    }

    /**
     * Update the specified FD scheme in storage.
     */
    public function update(UpdateFdCommissionSchemeRequest $request, DepositScheme $fdCommissionScheme): RedirectResponse
    {
        try {
            $scheme = $this->schemeService->updateScheme(
                $fdCommissionScheme,
                $request->validated(),
                $request->input('ranks', []),
                $request->input('notes')
            );

            return redirect()->route('superadmin.fd-commissions.show', $scheme)
                ->with('success', "FD Scheme '{$scheme->name}' updated successfully.");
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }
    }

    /**
     * Toggle active/inactive status of the scheme.
     */
    public function toggleStatus(DepositScheme $fdCommissionScheme): RedirectResponse
    {
        $scheme = $this->schemeService->toggleStatus($fdCommissionScheme);

        $statusText = $scheme->isActive() ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "FD Scheme '{$scheme->name}' has been {$statusText} successfully.");
    }

    /**
     * Remove the specified FD scheme from storage.
     */
    public function destroy(DepositScheme $fdCommissionScheme): RedirectResponse
    {
        try {
            $name = $fdCommissionScheme->name;
            $force = request()->boolean('force');
            $this->schemeService->deleteScheme($fdCommissionScheme, null, $force);

            return redirect()->route('superadmin.fd-commissions.index')
                ->with('success', "FD Scheme '{$name}' deleted successfully.");
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
