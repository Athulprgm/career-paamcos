<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CollectionPayment;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\DepositSchemeInterestRate;
use App\Models\DepositSchemeMrpConfiguration;
use App\Models\DepositSchemeRankCommission;
use App\Models\SchemeType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepositSchemeController extends Controller
{
    /**
     * Display a listing of deposit schemes.
     */
    public function index(Request $request)
    {
        $this->authorizeSchemeManagement();

        $query = DepositScheme::with(['schemeType', 'activeInterestRates'])->withCount('deposits');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $status = strtoupper($request->status);
            $query->where('status', $status);
        }

        $schemes = $query->orderBy('name')->paginate(15)->withQueryString();

        $types = DepositScheme::getSchemeTypes();
        $totalCount = DepositScheme::count();
        $activeCount = DepositScheme::where('status', DepositScheme::STATUS_ACTIVE)->count();
        $inactiveCount = DepositScheme::where('status', DepositScheme::STATUS_INACTIVE)->count();

        return view('admin.deposit-schemes.index', compact('schemes', 'types', 'totalCount', 'activeCount', 'inactiveCount'));
    }

    /**
     * Show the form for creating a new deposit scheme.
     */
    public function create(Request $request)
    {
        $this->authorizeSchemeManagement();

        if ($request->filled('clone_from')) {
            $source = DepositScheme::with(['interestRates', 'rankCommissions', 'mrpConfiguration.rates'])->find($request->input('clone_from'));
            if ($source) {
                return $this->clone($source);
            }
        }

        $types = DepositScheme::getSchemeTypes();
        $tenureUnits = DepositScheme::getTenureUnits();
        $frequencies = DepositScheme::getCollectionFrequencies();
        $payoutFrequencies = DepositScheme::getInterestPayoutFrequencies();

        return view('admin.deposit-schemes.create', compact('types', 'tenureUnits', 'frequencies', 'payoutFrequencies'));
    }

    /**
     * Show the form for cloning an existing deposit scheme.
     */
    public function clone(DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $depositScheme->load(['interestRates', 'rankCommissions', 'mrpConfiguration.rates']);
        $types = DepositScheme::getSchemeTypes();
        $tenureUnits = DepositScheme::getTenureUnits();
        $frequencies = DepositScheme::getCollectionFrequencies();
        $payoutFrequencies = DepositScheme::getInterestPayoutFrequencies();

        $clonedScheme = $depositScheme;

        $baseName = $clonedScheme->name;
        $suggestedName = $baseName.' (Copy)';
        $counter = 2;
        while (DepositScheme::where('name', $suggestedName)->exists()) {
            $cleaned = preg_replace('/\s*\(Copy(\s*\d+)?\)$/i', '', $baseName);
            $suggestedName = $cleaned." (Copy {$counter})";
            $counter++;
        }

        $suggestedCode = DepositScheme::generateSchemeCode(
            (string) ($clonedScheme->scheme_type ?? DepositScheme::TYPE_FD),
            $suggestedName,
            (int) ($clonedScheme->tenure ?? 1),
            (string) ($clonedScheme->tenure_unit ?? DepositScheme::TENURE_MONTHS)
        );

        return view('admin.deposit-schemes.create', compact(
            'types',
            'tenureUnits',
            'frequencies',
            'payoutFrequencies',
            'clonedScheme',
            'suggestedName',
            'suggestedCode'
        ));
    }

    /**
     * Duplicate (1-click direct clone) the specified deposit scheme.
     */
    public function duplicate(DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $depositScheme->load(['interestRates', 'rankCommissions', 'commissionRules', 'mrpConfiguration.rates']);

        $newScheme = DB::transaction(function () use ($depositScheme) {
            $baseName = $depositScheme->name;
            $newName = $baseName.' (Copy)';
            $counter = 2;
            while (DepositScheme::where('name', $newName)->exists()) {
                $cleaned = preg_replace('/\s*\(Copy(\s*\d+)?\)$/i', '', $baseName);
                $newName = $cleaned." (Copy {$counter})";
                $counter++;
            }

            $newCode = DepositScheme::generateSchemeCode(
                (string) ($depositScheme->scheme_type ?? DepositScheme::TYPE_FD),
                $newName,
                (int) ($depositScheme->tenure ?? 1),
                (string) ($depositScheme->tenure_unit ?? DepositScheme::TENURE_MONTHS)
            );

            $newScheme = DepositScheme::create([
                'name' => $newName,
                'code' => $newCode,
                'scheme_type_id' => $depositScheme->scheme_type_id,
                'scheme_type' => $depositScheme->scheme_type,
                'description' => $depositScheme->description,
                'min_amount' => $depositScheme->min_amount,
                'max_amount' => $depositScheme->max_amount,
                'tenure' => $depositScheme->tenure,
                'tenure_unit' => $depositScheme->tenure_unit,
                'interest_rate' => $depositScheme->interest_rate,
                'is_double' => $depositScheme->is_double,
                'total_commission_percentage' => $depositScheme->total_commission_percentage,
                'interest_payout_frequency' => $depositScheme->interest_payout_frequency,
                'senior_citizen_extra_rate' => $depositScheme->senior_citizen_extra_rate,
                'lock_in_months' => $depositScheme->lock_in_months,
                'allow_premature_withdrawal' => $depositScheme->allow_premature_withdrawal,
                'premature_penalty_percent' => $depositScheme->premature_penalty_percent,
                'allow_loan_against_deposit' => $depositScheme->allow_loan_against_deposit,
                'max_loan_percentage' => $depositScheme->max_loan_percentage,
                'grace_period_days' => $depositScheme->grace_period_days,
                'collection_frequency' => $depositScheme->collection_frequency,
                'status' => $depositScheme->status,
                'promotion_target_percentage' => $depositScheme->promotion_target_percentage,
                'created_by' => auth()->id(),
            ]);

            $newScheme->syncPrimaryInterestRate(auth()->id());

            // Clone multi-tier interest rates
            foreach ($depositScheme->interestRates as $rate) {
                if ((int) $rate->tenure === (int) $newScheme->tenure && strtoupper((string) $rate->tenure_unit) === strtoupper((string) $newScheme->tenure_unit)) {
                    continue;
                }
                $newScheme->interestRates()->create([
                    'tenure' => $rate->tenure,
                    'tenure_unit' => $rate->tenure_unit,
                    'interest_rate' => $rate->interest_rate,
                    'senior_citizen_extra_rate' => $rate->senior_citizen_extra_rate,
                    'min_amount' => $rate->min_amount ?? $newScheme->min_amount,
                    'max_amount' => $rate->max_amount ?? $newScheme->max_amount,
                    'effective_from' => $rate->effective_from ?? now()->toDateString(),
                    'effective_to' => $rate->effective_to,
                    'status' => $rate->status ?? DepositSchemeInterestRate::STATUS_ACTIVE,
                    'calculation_method' => $rate->calculation_method,
                    'notes' => $rate->notes ? "Cloned from {$depositScheme->code}: {$rate->notes}" : "Cloned from {$depositScheme->code}",
                    'created_by' => auth()->id(),
                ]);
            }

            // Clone rank commissions
            foreach ($depositScheme->rankCommissions as $rc) {
                DepositSchemeRankCommission::create([
                    'deposit_scheme_id' => $newScheme->id,
                    'rank_id' => $rc->rank_id,
                    'commission_percentage' => $rc->commission_percentage,
                    'is_eligible' => $rc->is_eligible,
                    'payout_year' => $rc->payout_year ?? 1,
                ]);
            }

            // Clone commission rules
            foreach ($depositScheme->commissionRules as $rule) {
                $newRule = $rule->replicate(['code']);
                $newRule->deposit_scheme_id = $newScheme->id;
                $newRule->created_by = auth()->id();
                $newRule->code = CommissionRule::generateRuleCode();
                if ($depositScheme->name && str_contains((string) $newRule->name, (string) $depositScheme->name)) {
                    $newRule->name = str_replace($depositScheme->name, $newScheme->name, (string) $newRule->name);
                }
                $newRule->save();
            }

            // Clone MRP configuration
            if ($depositScheme->mrpConfiguration) {
                $ratesArray = $depositScheme->mrpRates->map(fn ($r) => [
                    'year' => $r->year,
                    'general_rate' => $r->general_rate,
                    'senior_rate' => $r->senior_rate,
                ])->toArray();

                $newScheme->syncMrpConfiguration(
                    [
                        'is_enabled' => $depositScheme->mrpConfiguration->is_enabled,
                        'contribution_amount' => $depositScheme->mrpConfiguration->contribution_amount,
                        'min_tenure' => $depositScheme->mrpConfiguration->min_tenure,
                        'max_tenure' => $depositScheme->mrpConfiguration->max_tenure,
                        'tenure_unit' => $depositScheme->mrpConfiguration->tenure_unit,
                        'calculation_method' => $depositScheme->mrpConfiguration->calculation_method,
                        'general_rate' => $depositScheme->mrpConfiguration->general_rate,
                        'senior_rate' => $depositScheme->mrpConfiguration->senior_rate,
                        'notes' => $depositScheme->mrpConfiguration->notes,
                    ],
                    $ratesArray
                );
            }

            AuditLog::record(
                event: 'deposit_scheme_duplicated',
                description: "Directly duplicated deposit scheme [{$newScheme->name}] ({$newScheme->code}) from source [{$depositScheme->name}] ({$depositScheme->code}).",
                auditable: $newScheme,
                newValues: $newScheme->only(['name', 'code', 'scheme_type', 'min_amount', 'max_amount', 'tenure', 'tenure_unit', 'interest_rate', 'collection_frequency', 'status'])
            );

            return $newScheme;
        });

        return redirect()->route('admin.deposit-schemes.show', $newScheme)
            ->with('success', "Deposit Scheme '{$depositScheme->name}' successfully duplicated as '{$newScheme->name}' ({$newScheme->code}).");
    }

    /**
     * Store a newly created deposit scheme in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeSchemeManagement();

        $validTypes = array_keys(DepositScheme::getSchemeTypes());
        $validTypes = array_unique(array_merge($validTypes, [
            DepositScheme::TYPE_FD,
            DepositScheme::TYPE_RD,
            DepositScheme::TYPE_DD,
            DepositScheme::TYPE_DOUBLE,
            DepositScheme::TYPE_OTHER,
        ]));

        if ($request->filled('name') && (! $request->filled('scheme_type') || ! in_array($request->scheme_type, $validTypes, true))) {
            $request->merge(['scheme_type' => DepositScheme::detectSchemeType($request->name)]);
        } elseif ($request->filled('scheme_type') && ! $request->filled('name')) {
            $request->merge(['name' => DepositScheme::getSchemeTypes()[$request->scheme_type] ?? $request->scheme_type]);
        }

        if (! $request->filled('code') && $request->filled('name')) {
            $request->merge([
                'code' => DepositScheme::generateSchemeCode(
                    $request->input('scheme_type', DepositScheme::TYPE_FD),
                    $request->input('name', ''),
                    $request->input('tenure', 1),
                    $request->input('tenure_unit', DepositScheme::TENURE_MONTHS)
                ),
            ]);
        } elseif ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        if ($request->filled('tenure_rates_json') && is_string($request->input('tenure_rates_json'))) {
            $decoded = json_decode($request->input('tenure_rates_json'), true);
            if (is_array($decoded)) {
                $request->merge(['tenure_rates' => $decoded]);
            }
        }

        $kspValidation = $this->prepareAndGetKspValidation($request);
        $isKsp = $kspValidation['is_ksp'];

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_\-]+$/', 'unique:deposit_schemes,code'],
            'scheme_type' => ['required', 'string', 'max:50', Rule::in($validTypes)],
            'description' => ['nullable', 'string', 'max:1000'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'tenure' => ['required', 'integer', 'min:1'],
            'tenure_unit' => ['nullable', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_double' => ['nullable', 'boolean'],
            'interest_payout_frequency' => ['nullable', 'string', Rule::in(array_keys(DepositScheme::getInterestPayoutFrequencies()))],
            'senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'lock_in_months' => ['nullable', 'integer', 'min:0', 'max:360'],
            'allow_premature_withdrawal' => ['nullable'],
            'premature_penalty_percent' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'allow_loan_against_deposit' => ['nullable'],
            'max_loan_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'collection_frequency' => ['nullable', 'string', Rule::in([
                DepositScheme::FREQ_DAILY,
                DepositScheme::FREQ_WEEKLY,
                DepositScheme::FREQ_MONTHLY,
                DepositScheme::FREQ_QUARTERLY,
                DepositScheme::FREQ_ONE_TIME,
            ])],
            'status' => ['nullable', 'string', Rule::in([
                DepositScheme::STATUS_ACTIVE,
                DepositScheme::STATUS_INACTIVE,
            ])],
            'promotion_target_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cloned_from_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'tenure_rates' => ['nullable', 'array'],
            'tenure_rates.*.tenure' => ['nullable', 'integer', 'min:1'],
            'tenure_rates.*.tenure_unit' => ['nullable', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'tenure_rates.*.interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenure_rates.*.senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'mrp_enabled' => ['nullable'],
            'mrp_contribution_amount' => ['nullable', 'numeric', 'min:1'],
            'mrp_min_tenure' => ['nullable', 'integer', 'min:1'],
            'mrp_max_tenure' => ['nullable', 'integer', 'min:1'],
            'mrp_tenure_unit' => ['nullable', 'string', Rule::in(['YEARS', 'MONTHS'])],
            'mrp_calculation_method' => ['nullable', 'string', Rule::in(['STANDARD', 'BROCHURE_FLAT_10', 'ROUND_INTEGER'])],
            'mrp_general_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mrp_senior_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mrp_rates' => ['nullable', 'array'],
            'mrp_rates.*.year' => ['nullable', 'integer', 'min:1'],
            'mrp_rates.*.general_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mrp_rates.*.senior_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], $kspValidation['rules']), array_merge([
            'code.unique' => 'The scheme code has already been taken. Scheme codes must be unique.',
            'code.regex' => 'The scheme code must contain uppercase letters, numbers, hyphens, or underscores only.',
            'max_amount.gte' => 'Maximum amount cannot be less than minimum amount.',
        ], $kspValidation['messages']));

        if ($request->boolean('mrp_enabled')) {
            if (! $request->filled('mrp_contribution_amount') || (float) $request->input('mrp_contribution_amount') <= 0) {
                throw ValidationException::withMessages([
                    'mrp_contribution_amount' => 'Monthly Contribution Amount is required when MRP is enabled.',
                ]);
            }
            if (! $request->filled('mrp_min_tenure') || (int) $request->input('mrp_min_tenure') < 1) {
                throw ValidationException::withMessages([
                    'mrp_min_tenure' => 'Minimum Tenure is required and must be at least 1.',
                ]);
            }
            if (! $request->filled('mrp_max_tenure') || (int) $request->input('mrp_max_tenure') < (int) $request->input('mrp_min_tenure')) {
                throw ValidationException::withMessages([
                    'mrp_max_tenure' => 'Maximum Tenure must be greater than or equal to Minimum Tenure.',
                ]);
            }

            $rawMrpRates = $request->input('mrp_rates', []);
            if (is_array($rawMrpRates)) {
                $seenYears = [];
                foreach ($rawMrpRates as $rIdx => $rItem) {
                    if (isset($rItem['year']) && $rItem['year'] !== '') {
                        $y = (int) $rItem['year'];
                        if (in_array($y, $seenYears, true)) {
                            throw ValidationException::withMessages([
                                "mrp_rates.{$rIdx}.year" => "Year {$y} is duplicated. Each year in the MRP configuration must be unique.",
                                'mrp_rates' => "Duplicate year {$y} detected in MRP configuration. Each year must be unique.",
                            ]);
                        }
                        $seenYears[] = $y;
                    }
                }
            }
        }

        $schemeTypeUpper = strtoupper(trim($validated['scheme_type']));
        $typeModel = SchemeType::where('code', $schemeTypeUpper)->first();

        // Smart defaults for collection frequency based on scheme type
        $defaultFrequency = $typeModel?->default_frequency ?: match ($schemeTypeUpper) {
            DepositScheme::TYPE_RD => DepositScheme::FREQ_MONTHLY,
            DepositScheme::TYPE_DD => DepositScheme::FREQ_DAILY,
            default => DepositScheme::FREQ_ONE_TIME,
        };
        $collectionFreq = $validated['collection_frequency'] ?? $defaultFrequency;
        if (empty($collectionFreq)) {
            $collectionFreq = DepositScheme::FREQ_ONE_TIME;
        }

        $code = strtoupper(trim($validated['code']));

        $isDouble = $request->boolean('is_double') || $schemeTypeUpper === DepositScheme::TYPE_DOUBLE;
        if ($isDouble) {
            $validated['interest_rate'] = 0.00;
        } else {
            $validated['interest_rate'] = (float) ($validated['interest_rate'] ?? 0.00);
        }

        $scheme = DB::transaction(function () use ($validated, $code, $typeModel, $schemeTypeUpper, $collectionFreq, $request, $isDouble, $isKsp) {
            $scheme = DepositScheme::create([
                'name' => $validated['name'],
                'code' => $code,
                'scheme_type_id' => $typeModel?->id,
                'scheme_type' => $schemeTypeUpper,
                'description' => $validated['description'] ?? null,
                'min_amount' => $validated['min_amount'],
                'max_amount' => $validated['max_amount'] ?? null,
                'tenure' => $validated['tenure'],
                'tenure_unit' => $validated['tenure_unit'] ?? DepositScheme::TENURE_MONTHS,
                'interest_rate' => $validated['interest_rate'],
                'is_double' => $isDouble,
                'interest_payout_frequency' => $validated['interest_payout_frequency'] ?? DepositScheme::PAYOUT_AT_MATURITY,
                'senior_citizen_extra_rate' => $validated['senior_citizen_extra_rate'] ?? 0.00,
                'lock_in_months' => $validated['lock_in_months'] ?? 0,
                'allow_premature_withdrawal' => $request->has('allow_premature_withdrawal') ? $request->boolean('allow_premature_withdrawal') : true,
                'premature_penalty_percent' => $validated['premature_penalty_percent'] ?? 1.00,
                'allow_loan_against_deposit' => $request->has('allow_loan_against_deposit') ? $request->boolean('allow_loan_against_deposit') : true,
                'max_loan_percentage' => $validated['max_loan_percentage'] ?? 80.00,
                'grace_period_days' => $validated['grace_period_days'] ?? 7,
                'collection_frequency' => $collectionFreq,
                'status' => isset($validated['status']) ? strtoupper($validated['status']) : DepositScheme::STATUS_ACTIVE,
                'promotion_target_percentage' => array_key_exists('promotion_target_percentage', $validated) && $validated['promotion_target_percentage'] !== null ? (float) $validated['promotion_target_percentage'] : null,
                'ksp_configuration' => $isKsp ? $this->extractKspConfiguration($validated) : null,
                'created_by' => auth()->id(),
            ]);

            $scheme->syncPrimaryInterestRate(auth()->id());

            if ($request->has('tenure_rates_submitted') || ($request->has('tenure_rates') && is_array($request->input('tenure_rates')))) {
                $rawRates = $request->input('tenure_rates', []);
                if (count($rawRates) === 1) {
                    $first = reset($rawRates);
                    $sameTenure = (int) ($first['tenure'] ?? 0) === (int) $scheme->tenure;
                    $sameUnit = strtoupper(trim((string) ($first['tenure_unit'] ?? ''))) === strtoupper(trim((string) $scheme->tenure_unit));
                    $sameRate = abs((float) ($first['interest_rate'] ?? 0) - (float) $scheme->interest_rate) < 0.001;
                    if ($sameTenure && $sameUnit && $sameRate) {
                        $rawRates = [];
                    }
                }
                $scheme->syncInterestRates($rawRates, auth()->id());
            }

            if ($request->filled('cloned_from_id')) {
                $sourceScheme = DepositScheme::with(['interestRates', 'rankCommissions', 'commissionRules'])->find($request->input('cloned_from_id'));
                if ($sourceScheme) {
                    if ($sourceScheme->total_commission_percentage > 0 || $sourceScheme->promotion_target_percentage !== null) {
                        $scheme->update([
                            'total_commission_percentage' => $sourceScheme->total_commission_percentage,
                            'promotion_target_percentage' => $sourceScheme->promotion_target_percentage,
                        ]);
                    }

                    foreach ($sourceScheme->rankCommissions as $rc) {
                        DepositSchemeRankCommission::create([
                            'deposit_scheme_id' => $scheme->id,
                            'rank_id' => $rc->rank_id,
                            'commission_percentage' => $rc->commission_percentage,
                            'is_eligible' => $rc->is_eligible,
                            'payout_year' => $rc->payout_year ?? 1,
                        ]);
                    }

                    foreach ($sourceScheme->commissionRules as $rule) {
                        $newRule = $rule->replicate(['code']);
                        $newRule->deposit_scheme_id = $scheme->id;
                        $newRule->created_by = auth()->id();
                        $newRule->code = CommissionRule::generateRuleCode();
                        if ($sourceScheme->name && str_contains((string) $newRule->name, (string) $sourceScheme->name)) {
                            $newRule->name = str_replace($sourceScheme->name, $scheme->name, (string) $newRule->name);
                        }
                        $newRule->save();
                    }

                    if (! $request->has('tenure_rates_submitted') && ! $request->has('tenure_rates')) {
                        foreach ($sourceScheme->interestRates as $rate) {
                            if ((int) $rate->tenure === (int) $scheme->tenure && strtoupper((string) $rate->tenure_unit) === strtoupper((string) $scheme->tenure_unit)) {
                                continue;
                            }
                            $scheme->interestRates()->create([
                                'tenure' => $rate->tenure,
                                'tenure_unit' => $rate->tenure_unit,
                                'interest_rate' => $rate->interest_rate,
                                'senior_citizen_extra_rate' => $rate->senior_citizen_extra_rate,
                                'min_amount' => $rate->min_amount ?? $scheme->min_amount,
                                'max_amount' => $rate->max_amount ?? $scheme->max_amount,
                                'effective_from' => $rate->effective_from ?? now()->toDateString(),
                                'effective_to' => $rate->effective_to,
                                'status' => $rate->status ?? DepositSchemeInterestRate::STATUS_ACTIVE,
                                'calculation_method' => $rate->calculation_method,
                                'notes' => $rate->notes ? "Cloned from {$sourceScheme->code}: {$rate->notes}" : "Cloned from {$sourceScheme->code}",
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            if ($request->boolean('mrp_enabled')) {
                $scheme->syncMrpConfiguration(
                    [
                        'is_enabled' => true,
                        'contribution_amount' => $request->input('mrp_contribution_amount', 100000.00),
                        'min_tenure' => $request->input('mrp_min_tenure', 1),
                        'max_tenure' => $request->input('mrp_max_tenure', 5),
                        'tenure_unit' => $request->input('mrp_tenure_unit', 'YEARS'),
                        'calculation_method' => $request->input('mrp_calculation_method', DepositSchemeMrpConfiguration::METHOD_STANDARD),
                        'general_rate' => $request->input('mrp_general_rate'),
                        'senior_rate' => $request->input('mrp_senior_rate'),
                    ],
                    $request->input('mrp_rates', [])
                );
            } elseif ($request->filled('cloned_from_id')) {
                $sourceScheme = DepositScheme::with('mrpConfiguration.rates')->find($request->input('cloned_from_id'));
                if ($sourceScheme?->mrpConfiguration) {
                    $ratesArray = $sourceScheme->mrpRates->map(fn ($r) => [
                        'year' => $r->year,
                        'general_rate' => $r->general_rate,
                        'senior_rate' => $r->senior_rate,
                    ])->toArray();

                    $scheme->syncMrpConfiguration(
                        [
                            'is_enabled' => $sourceScheme->mrpConfiguration->is_enabled,
                            'contribution_amount' => $sourceScheme->mrpConfiguration->contribution_amount,
                            'min_tenure' => $sourceScheme->mrpConfiguration->min_tenure,
                            'max_tenure' => $sourceScheme->mrpConfiguration->max_tenure,
                            'tenure_unit' => $sourceScheme->mrpConfiguration->tenure_unit,
                            'calculation_method' => $sourceScheme->mrpConfiguration->calculation_method,
                            'general_rate' => $sourceScheme->mrpConfiguration->general_rate,
                            'senior_rate' => $sourceScheme->mrpConfiguration->senior_rate,
                            'notes' => $sourceScheme->mrpConfiguration->notes,
                        ],
                        $ratesArray
                    );
                }
            }

            return $scheme;
        });

        $isCloned = $request->filled('cloned_from_id');
        AuditLog::record(
            event: $isCloned ? 'deposit_scheme_cloned' : 'deposit_scheme_created',
            description: $isCloned
                ? "Cloned deposit scheme [{$scheme->name}] ({$scheme->code}) from scheme #{$request->input('cloned_from_id')} with tenure {$scheme->tenure} {$scheme->tenure_unit} and {$scheme->interest_rate}% rate."
                : "Created deposit scheme [{$scheme->name}] ({$scheme->code}) with tenure {$scheme->tenure} {$scheme->tenure_unit} and {$scheme->interest_rate}% rate.",
            auditable: $scheme,
            newValues: $scheme->only(['name', 'code', 'scheme_type', 'min_amount', 'max_amount', 'tenure', 'tenure_unit', 'interest_rate', 'collection_frequency', 'status'])
        );

        $flashMessage = $isCloned
            ? "Deposit Scheme '{$scheme->name}' cloned successfully."
            : "Deposit Scheme '{$scheme->name}' created successfully.";

        return redirect()->route('admin.deposit-schemes.show', $scheme)
            ->with('success', $flashMessage);
    }

    /**
     * Display the specified deposit scheme.
     */
    public function show(DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $depositScheme->loadCount('deposits');
        $depositScheme->load(['schemeType', 'interestRates', 'mrpConfiguration.rates']);
        $recentDeposits = $depositScheme->deposits()
            ->with(['customer', 'branch', 'agent'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.deposit-schemes.show', compact('depositScheme', 'recentDeposits'));
    }

    /**
     * Show the form for editing the specified deposit scheme.
     */
    public function edit(DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $depositScheme->load(['interestRates', 'mrpConfiguration.rates']);
        $types = DepositScheme::getSchemeTypes();
        $tenureUnits = DepositScheme::getTenureUnits();
        $frequencies = DepositScheme::getCollectionFrequencies();
        $payoutFrequencies = DepositScheme::getInterestPayoutFrequencies();

        return view('admin.deposit-schemes.edit', compact('depositScheme', 'types', 'tenureUnits', 'frequencies', 'payoutFrequencies'));
    }

    /**
     * Update the specified deposit scheme in storage.
     */
    public function update(Request $request, DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $validTypes = array_keys(DepositScheme::getSchemeTypes());
        $validTypes = array_unique(array_merge($validTypes, [
            DepositScheme::TYPE_FD,
            DepositScheme::TYPE_RD,
            DepositScheme::TYPE_DD,
            DepositScheme::TYPE_DOUBLE,
            DepositScheme::TYPE_OTHER,
        ]));

        if ($request->filled('name') && (! $request->filled('scheme_type') || ! in_array($request->scheme_type, $validTypes, true))) {
            $request->merge(['scheme_type' => DepositScheme::detectSchemeType($request->name)]);
        } elseif ($request->filled('scheme_type') && ! $request->filled('name')) {
            $request->merge(['name' => DepositScheme::getSchemeTypes()[$request->scheme_type] ?? $request->scheme_type]);
        }

        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        if ($request->filled('tenure_rates_json') && is_string($request->input('tenure_rates_json'))) {
            $decoded = json_decode($request->input('tenure_rates_json'), true);
            if (is_array($decoded)) {
                $request->merge(['tenure_rates' => $decoded]);
            }
        }

        $kspValidation = $this->prepareAndGetKspValidation($request);
        $isKsp = $kspValidation['is_ksp'];

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_\-]+$/', Rule::unique('deposit_schemes', 'code')->ignore($depositScheme->id)],
            'scheme_type' => ['required', 'string', 'max:50', Rule::in($validTypes)],
            'description' => ['nullable', 'string', 'max:1000'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'tenure' => ['required', 'integer', 'min:1'],
            'tenure_unit' => ['nullable', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_double' => ['nullable', 'boolean'],
            'interest_payout_frequency' => ['nullable', 'string', Rule::in(array_keys(DepositScheme::getInterestPayoutFrequencies()))],
            'senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'lock_in_months' => ['nullable', 'integer', 'min:0', 'max:360'],
            'allow_premature_withdrawal' => ['nullable'],
            'premature_penalty_percent' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'allow_loan_against_deposit' => ['nullable'],
            'max_loan_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'collection_frequency' => ['nullable', 'string', Rule::in([
                DepositScheme::FREQ_DAILY,
                DepositScheme::FREQ_WEEKLY,
                DepositScheme::FREQ_MONTHLY,
                DepositScheme::FREQ_QUARTERLY,
                DepositScheme::FREQ_ONE_TIME,
            ])],
            'status' => ['nullable', 'string', Rule::in([
                DepositScheme::STATUS_ACTIVE,
                DepositScheme::STATUS_INACTIVE,
            ])],
            'promotion_target_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cloned_from_id' => ['nullable', 'integer', 'exists:deposit_schemes,id'],
            'tenure_rates' => ['nullable', 'array'],
            'tenure_rates.*.tenure' => ['nullable', 'integer', 'min:1'],
            'tenure_rates.*.tenure_unit' => ['nullable', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'tenure_rates.*.interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tenure_rates.*.senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'mrp_enabled' => ['nullable'],
            'mrp_contribution_amount' => ['nullable', 'numeric', 'min:1'],
            'mrp_min_tenure' => ['nullable', 'integer', 'min:1'],
            'mrp_max_tenure' => ['nullable', 'integer', 'min:1'],
            'mrp_tenure_unit' => ['nullable', 'string', Rule::in(['YEARS', 'MONTHS'])],
            'mrp_calculation_method' => ['nullable', 'string', Rule::in(['STANDARD', 'BROCHURE_FLAT_10', 'ROUND_INTEGER'])],
            'mrp_general_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mrp_senior_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mrp_rates' => ['nullable', 'array'],
            'mrp_rates.*.year' => ['nullable', 'integer', 'min:1'],
            'mrp_rates.*.general_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mrp_rates.*.senior_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], $kspValidation['rules']), array_merge([
            'code.unique' => 'The scheme code has already been taken. Scheme codes must be unique.',
            'code.regex' => 'The scheme code must contain uppercase letters, numbers, and hyphens only.',
            'max_amount.gte' => 'Maximum amount cannot be less than minimum amount.',
        ], $kspValidation['messages']));

        if ($request->boolean('mrp_enabled')) {
            if (! $request->filled('mrp_contribution_amount') || (float) $request->input('mrp_contribution_amount') <= 0) {
                throw ValidationException::withMessages([
                    'mrp_contribution_amount' => 'Monthly Contribution Amount is required when MRP is enabled.',
                ]);
            }
            if (! $request->filled('mrp_min_tenure') || (int) $request->input('mrp_min_tenure') < 1) {
                throw ValidationException::withMessages([
                    'mrp_min_tenure' => 'Minimum Tenure is required and must be at least 1.',
                ]);
            }
            if (! $request->filled('mrp_max_tenure') || (int) $request->input('mrp_max_tenure') < (int) $request->input('mrp_min_tenure')) {
                throw ValidationException::withMessages([
                    'mrp_max_tenure' => 'Maximum Tenure must be greater than or equal to Minimum Tenure.',
                ]);
            }

            $rawMrpRates = $request->input('mrp_rates', []);
            if (is_array($rawMrpRates)) {
                $seenYears = [];
                foreach ($rawMrpRates as $rIdx => $rItem) {
                    if (isset($rItem['year']) && $rItem['year'] !== '') {
                        $y = (int) $rItem['year'];
                        if (in_array($y, $seenYears, true)) {
                            throw ValidationException::withMessages([
                                "mrp_rates.{$rIdx}.year" => "Year {$y} is duplicated. Each year in the MRP configuration must be unique.",
                                'mrp_rates' => "Duplicate year {$y} detected in MRP configuration. Each year must be unique.",
                            ]);
                        }
                        $seenYears[] = $y;
                    }
                }
            }
        }

        $schemeTypeUpper = strtoupper(trim($validated['scheme_type']));
        $typeModel = SchemeType::where('code', $schemeTypeUpper)->first();

        $defaultFrequency = $typeModel?->default_frequency ?: match ($schemeTypeUpper) {
            DepositScheme::TYPE_RD => DepositScheme::FREQ_MONTHLY,
            DepositScheme::TYPE_DD => DepositScheme::FREQ_DAILY,
            default => DepositScheme::FREQ_ONE_TIME,
        };
        $collectionFreq = $validated['collection_frequency'] ?? $depositScheme->collection_frequency ?? $defaultFrequency;

        $isDouble = $request->boolean('is_double') || $schemeTypeUpper === DepositScheme::TYPE_DOUBLE;
        if ($isDouble) {
            $validated['interest_rate'] = 0.00;
        } else {
            $validated['interest_rate'] = (float) ($validated['interest_rate'] ?? 0.00);
        }

        $oldValues = $depositScheme->only(['name', 'code', 'scheme_type', 'min_amount', 'max_amount', 'tenure', 'tenure_unit', 'interest_rate', 'is_double', 'collection_frequency', 'status']);

        DB::transaction(function () use ($depositScheme, $validated, $typeModel, $schemeTypeUpper, $collectionFreq, $request, $oldValues, $isDouble, $isKsp) {
            $depositScheme->update([
                'name' => $validated['name'],
                'code' => trim($validated['code']),
                'scheme_type_id' => $typeModel?->id,
                'scheme_type' => $schemeTypeUpper,
                'description' => $validated['description'] ?? null,
                'min_amount' => $validated['min_amount'],
                'max_amount' => $validated['max_amount'] ?? null,
                'tenure' => $validated['tenure'],
                'tenure_unit' => $validated['tenure_unit'] ?? $depositScheme->tenure_unit ?? DepositScheme::TENURE_MONTHS,
                'interest_rate' => $validated['interest_rate'],
                'is_double' => $isDouble,
                'interest_payout_frequency' => $validated['interest_payout_frequency'] ?? $depositScheme->interest_payout_frequency ?? DepositScheme::PAYOUT_AT_MATURITY,
                'senior_citizen_extra_rate' => $validated['senior_citizen_extra_rate'] ?? $depositScheme->senior_citizen_extra_rate ?? 0.00,
                'lock_in_months' => $validated['lock_in_months'] ?? $depositScheme->lock_in_months ?? 0,
                'allow_premature_withdrawal' => $request->has('allow_premature_withdrawal') ? $request->boolean('allow_premature_withdrawal') : ($depositScheme->allow_premature_withdrawal ?? true),
                'premature_penalty_percent' => $validated['premature_penalty_percent'] ?? $depositScheme->premature_penalty_percent ?? 1.00,
                'allow_loan_against_deposit' => $request->has('allow_loan_against_deposit') ? $request->boolean('allow_loan_against_deposit') : ($depositScheme->allow_loan_against_deposit ?? true),
                'max_loan_percentage' => $validated['max_loan_percentage'] ?? $depositScheme->max_loan_percentage ?? 80.00,
                'grace_period_days' => $validated['grace_period_days'] ?? $depositScheme->grace_period_days ?? 7,
                'collection_frequency' => $collectionFreq,
                'status' => isset($validated['status']) ? strtoupper($validated['status']) : $depositScheme->status,
                'promotion_target_percentage' => array_key_exists('promotion_target_percentage', $validated) && $validated['promotion_target_percentage'] !== null ? (float) $validated['promotion_target_percentage'] : $depositScheme->promotion_target_percentage,
                'ksp_configuration' => $isKsp ? $this->extractKspConfiguration($validated) : ($depositScheme->isKsp() ? null : $depositScheme->ksp_configuration),
            ]);

            $depositScheme->syncPrimaryInterestRate(auth()->id());

            if ($request->has('tenure_rates_submitted') || ($request->has('tenure_rates') && is_array($request->input('tenure_rates')))) {
                $rawRates = $request->input('tenure_rates', []);
                if (count($rawRates) === 1) {
                    $first = reset($rawRates);
                    $sameTenure = (int) ($first['tenure'] ?? 0) === (int) $depositScheme->tenure;
                    $sameUnit = strtoupper(trim((string) ($first['tenure_unit'] ?? ''))) === strtoupper(trim((string) $depositScheme->tenure_unit));
                    $sameRate = abs((float) ($first['interest_rate'] ?? 0) - (float) $depositScheme->interest_rate) < 0.001;
                    if ($sameTenure && $sameUnit && $sameRate) {
                        $rawRates = [];
                    }
                }
                $depositScheme->syncInterestRates($rawRates, auth()->id());
            }

            if ($request->boolean('mrp_enabled')) {
                $depositScheme->syncMrpConfiguration(
                    [
                        'is_enabled' => true,
                        'contribution_amount' => $request->input('mrp_contribution_amount', 100000.00),
                        'min_tenure' => $request->input('mrp_min_tenure', 1),
                        'max_tenure' => $request->input('mrp_max_tenure', 5),
                        'tenure_unit' => $request->input('mrp_tenure_unit', 'YEARS'),
                        'calculation_method' => $request->input('mrp_calculation_method', DepositSchemeMrpConfiguration::METHOD_STANDARD),
                        'general_rate' => $request->input('mrp_general_rate'),
                        'senior_rate' => $request->input('mrp_senior_rate'),
                    ],
                    $request->input('mrp_rates', [])
                );
            } else {
                if ($depositScheme->mrpConfiguration) {
                    $depositScheme->mrpConfiguration->update(['is_enabled' => false]);
                }
            }

            AuditLog::record(
                event: 'deposit_scheme_updated',
                description: "Updated deposit scheme [{$depositScheme->name}] ({$depositScheme->code}).",
                auditable: $depositScheme,
                oldValues: $oldValues,
                newValues: $depositScheme->only(['name', 'code', 'scheme_type', 'min_amount', 'max_amount', 'tenure', 'tenure_unit', 'interest_rate', 'collection_frequency', 'status'])
            );
        });

        return redirect()->route('admin.deposit-schemes.show', $depositScheme)
            ->with('success', "Deposit Scheme '{$depositScheme->name}' updated successfully.");
    }

    /**
     * Toggle the status of the specified deposit scheme between ACTIVE and INACTIVE.
     */
    public function toggleStatus(DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $oldStatus = strtoupper((string) $depositScheme->status);
        $newStatus = ($oldStatus === DepositScheme::STATUS_ACTIVE)
            ? DepositScheme::STATUS_INACTIVE
            : DepositScheme::STATUS_ACTIVE;

        $depositScheme->update(['status' => $newStatus]);
        $depositScheme->syncPrimaryInterestRate(auth()->id());

        AuditLog::record(
            event: 'deposit_scheme_status_toggled',
            description: "Toggled status of scheme [{$depositScheme->name}] from {$oldStatus} to {$newStatus}.",
            auditable: $depositScheme,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus]
        );

        $actionWord = ($newStatus === DepositScheme::STATUS_ACTIVE) ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Deposit Scheme '{$depositScheme->name}' has been {$actionWord}.");
    }

    /**
     * Remove the specified deposit scheme from storage.
     */
    public function destroy(DepositScheme $depositScheme)
    {
        $this->authorizeSchemeManagement();

        $force = request()->boolean('force');

        if (! $force && $depositScheme->deposits()->exists()) {
            return redirect()->back()
                ->with('error', "Cannot delete scheme '{$depositScheme->name}' because it has existing deposits linked to it. Consider deactivating it instead or check force delete.");
        }

        $schemeName = $depositScheme->name;
        DB::transaction(function () use ($depositScheme, $force) {
            if ($force) {
                $depositIds = $depositScheme->deposits()->pluck('id')->all();
                if (! empty($depositIds)) {
                    CollectionPayment::whereIn('deposit_id', $depositIds)->delete();
                    DepositCollection::whereIn('deposit_id', $depositIds)->delete();
                    Commission::whereIn('deposit_id', $depositIds)->delete();
                    $depositScheme->deposits()->delete();
                }
            }
            $depositScheme->interestRates()->delete();
            $depositScheme->rankCommissions()->delete();
            CommissionRule::where('deposit_scheme_id', $depositScheme->id)->delete();
            $depositScheme->delete();
        });

        AuditLog::record(
            event: 'deposit_scheme_deleted',
            description: "Deleted deposit scheme [{$schemeName}] ({$depositScheme->code}).",
            auditable: $depositScheme,
            oldValues: $depositScheme->toArray()
        );

        return redirect()->route('admin.deposit-schemes.index')
            ->with('success', "Deposit Scheme '{$schemeName}' deleted successfully.");
    }

    /**
     * Preview and validate a generated scheme code or check code uniqueness.
     */
    public function previewCode(Request $request)
    {
        $this->authorizeSchemeManagement();

        $name = (string) $request->input('name', '');
        $type = (string) $request->input('scheme_type', DepositScheme::TYPE_FD);
        $tenure = (int) $request->input('tenure', 1);
        $tenureUnit = (string) $request->input('tenure_unit', DepositScheme::TENURE_MONTHS);
        $ignoreId = $request->filled('ignore_id') ? (int) $request->input('ignore_id') : null;
        $customCode = $request->filled('code') ? strtoupper(trim((string) $request->input('code'))) : null;

        $generatedCode = DepositScheme::generateSchemeCode(
            $type,
            $name,
            $tenure,
            $tenureUnit,
            $ignoreId
        );

        $codeToCheck = $customCode ?: $generatedCode;

        $exists = DepositScheme::where('code', $codeToCheck)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return response()->json([
            'success' => true,
            'code' => $codeToCheck,
            'generated_code' => $generatedCode,
            'is_unique' => ! $exists,
            'message' => $exists
                ? "Code '{$codeToCheck}' is already taken. Suggested unique code: {$generatedCode}"
                : "Code '{$codeToCheck}' is available.",
        ]);
    }

    /**
     * Get interest rate and details for a specific scheme and tenure duration.
     */
    public function getRateForTenure(Request $request, DepositScheme $depositScheme): JsonResponse
    {
        $tenure = max(1, (int) $request->input('tenure', $depositScheme->tenure ?? 1));
        $tenureUnit = (string) $request->input('tenure_unit', $depositScheme->tenure_unit ?? DepositScheme::TENURE_YEARS);
        $isSeniorCitizen = $request->boolean('is_senior_citizen');
        $isDouble = $depositScheme->isDouble() || $request->boolean('is_double');

        if ($isDouble) {
            $baseRate = 0.00;
            $seniorExtra = 0.00;
            $effectiveRate = 0.00;
            $matchedTier = null;
        } else {
            $baseRate = $depositScheme->getInterestRateForTenure($tenure, $tenureUnit);
            $seniorExtra = $depositScheme->getSeniorCitizenExtraRateForTenure($tenure, $tenureUnit);
            $effectiveRate = $depositScheme->getApplicableInterestRate($tenure, $tenureUnit, $isSeniorCitizen);
            $matchedTier = $depositScheme->findInterestRateRecord($tenure, $tenureUnit);
        }

        return response()->json([
            'success' => true,
            'scheme_id' => $depositScheme->id,
            'scheme_name' => $depositScheme->name,
            'scheme_code' => $depositScheme->code,
            'scheme_type' => $depositScheme->scheme_type,
            'collection_frequency' => $depositScheme->getEffectiveCollectionFrequency(),
            'interest_payout_frequency' => $depositScheme->getEffectivePayoutFrequency(),
            'is_double' => $isDouble,
            'is_mrp' => $depositScheme->isMrp(),
            'is_ksp' => $depositScheme->isKsp(),
            'ksp_configuration' => $depositScheme->isKsp() ? $depositScheme->getKspConfig() : null,
            'tenure' => $tenure,
            'tenure_unit' => strtoupper(trim($tenureUnit)),
            'interest_rate' => $baseRate,
            'senior_citizen_extra_rate' => $seniorExtra,
            'effective_rate' => $effectiveRate,
            'applicable_interest_rate' => $effectiveRate,
            'is_tier_rate' => $matchedTier !== null,
            'is_senior_citizen' => $isSeniorCitizen,
            'has_duration_rates' => $depositScheme->hasConfiguredTenureRates(),
        ]);
    }

    /**
     * Get all configured duration rates for a deposit scheme.
     */
    public function getRates(DepositScheme $depositScheme): JsonResponse
    {
        $depositScheme->load(['activeInterestRates', 'mrpConfiguration', 'mrpRates']);
        $isMrp = $depositScheme->isMrp();
        $mrpConfig = $depositScheme->mrpConfiguration;

        return response()->json([
            'success' => true,
            'scheme_id' => $depositScheme->id,
            'scheme_name' => $depositScheme->name,
            'scheme_code' => $depositScheme->code,
            'scheme_type' => $depositScheme->scheme_type,
            'collection_frequency' => $depositScheme->getEffectiveCollectionFrequency(),
            'collection_frequency_label' => $depositScheme->getCollectionFrequencyLabel(),
            'interest_payout_frequency' => $depositScheme->getEffectivePayoutFrequency(),
            'interest_payout_frequency_label' => $depositScheme->getInterestPayoutFrequencyLabel(),
            'is_double' => $depositScheme->isDouble(),
            'is_mrp' => $isMrp,
            'is_ksp' => $depositScheme->isKsp(),
            'ksp_configuration' => $depositScheme->isKsp() ? $depositScheme->getKspConfig() : null,
            'mrp_calculation_method' => $mrpConfig?->calculation_method ?? DepositSchemeMrpConfiguration::METHOD_STANDARD,
            'min_amount' => (float) $depositScheme->min_amount,
            'max_amount' => $depositScheme->max_amount !== null ? (float) $depositScheme->max_amount : null,
            'default_interest_rate' => (float) $depositScheme->interest_rate,
            'default_tenure' => (int) $depositScheme->tenure,
            'default_tenure_unit' => $depositScheme->tenure_unit ?? DepositScheme::TENURE_MONTHS,
            'senior_citizen_extra_rate' => (float) ($depositScheme->senior_citizen_extra_rate ?? 0.00),
            'mrp_configuration' => ($mrpConfig && $mrpConfig->is_enabled) ? [
                'is_enabled' => true,
                'contribution_amount' => (float) $mrpConfig->contribution_amount,
                'min_tenure' => (int) $mrpConfig->min_tenure,
                'max_tenure' => (int) $mrpConfig->max_tenure,
                'tenure_unit' => $mrpConfig->tenure_unit,
                'calculation_method' => $mrpConfig->calculation_method,
                'general_rate' => $mrpConfig->general_rate !== null ? (float) $mrpConfig->general_rate : null,
                'senior_rate' => $mrpConfig->senior_rate !== null ? (float) $mrpConfig->senior_rate : null,
                'rates' => $depositScheme->mrpRates->map(function ($mr) {
                    return [
                        'year' => (int) $mr->year,
                        'general_rate' => (float) $mr->general_rate,
                        'general_monthly_return' => (float) $mr->general_monthly_return,
                        'senior_rate' => (float) $mr->senior_rate,
                        'senior_monthly_return' => (float) $mr->senior_monthly_return,
                    ];
                })->values(),
            ] : null,
            'rates' => $depositScheme->activeInterestRates->map(function ($r) {
                return [
                    'id' => $r->id,
                    'tenure' => (int) $r->tenure,
                    'tenure_unit' => $r->tenure_unit,
                    'interest_rate' => (float) $r->interest_rate,
                    'senior_citizen_extra_rate' => (float) ($r->senior_citizen_extra_rate ?? 0.00),
                    'formatted_tenure' => $r->getFormattedTenure(),
                    'label' => $r->getFormattedTenure().' @ '.number_format((float) $r->interest_rate, 2).'% p.a.',
                ];
            })->values(),
        ]);
    }

    /**
     * Prepare request inputs and return validation rules and messages for KSP configuration when applicable.
     *
     * @return array{is_ksp: bool, rules: array<string, mixed>, messages: array<string, string>}
     */
    protected function prepareAndGetKspValidation(Request $request): array
    {
        $isKsp = DepositScheme::isKspCode($request->input('code'), $request->input('name'));

        if (! $isKsp) {
            return [
                'is_ksp' => false,
                'rules' => [],
                'messages' => [],
            ];
        }

        $nestedKsp = is_array($request->input('ksp_configuration')) ? $request->input('ksp_configuration') : [];
        $rawPeriod = $request->input('ksp_deposit_period', $nestedKsp['deposit_period'] ?? $request->input('deposit_period'));
        $detectedPeriod = (is_numeric($rawPeriod) && (int) $rawPeriod >= 1)
            ? (int) $rawPeriod
            : DepositScheme::extractKspDepositPeriod($request->input('code'), $request->input('name'), 10);

        $periodDefaults = DepositScheme::getKspConfigForPeriod($detectedPeriod);

        foreach ($periodDefaults as $key => $defaultVal) {
            $prefixedKey = 'ksp_'.$key;
            if (! $request->has($prefixedKey)) {
                if (array_key_exists($key, $nestedKsp)) {
                    $request->merge([$prefixedKey => $nestedKsp[$key]]);
                } elseif ($request->has($key)) {
                    $request->merge([$prefixedKey => $request->input($key)]);
                } else {
                    $request->merge([$prefixedKey => $defaultVal]);
                }
            }
        }

        foreach (['ksp_annual_deposit_amount', 'ksp_insurance_coverage', 'ksp_annual_return_amount'] as $currencyField) {
            $raw = $request->input($currencyField);
            if (is_string($raw) && preg_match('/^\s*₹?\s*[\d,]+(?:\.\d+)?\s*$/u', $raw)) {
                $cleaned = preg_replace('/[^\d.]/', '', $raw);
                $request->merge([$currencyField => $cleaned]);
            }
        }

        foreach (['ksp_deposit_period', 'ksp_return_start_year', 'ksp_return_end_year'] as $yearField) {
            $raw = $request->input($yearField);
            if (is_string($raw) && preg_match('/^\s*(\d+)\s*(?:years?|yrs?)?\s*$/i', $raw, $m)) {
                $request->merge([$yearField => $m[1]]);
            }
        }

        if (! $request->filled('min_amount') && is_numeric($request->input('ksp_annual_deposit_amount'))) {
            $request->merge(['min_amount' => $request->input('ksp_annual_deposit_amount')]);
        }
        if (! $request->filled('tenure') && is_numeric($request->input('ksp_deposit_period'))) {
            $request->merge(['tenure' => (int) $request->input('ksp_deposit_period')]);
        }
        if (! $request->filled('tenure_unit')) {
            $request->merge(['tenure_unit' => DepositScheme::TENURE_YEARS]);
        }
        if (! $request->filled('interest_rate')) {
            $request->merge(['interest_rate' => 0.00]);
        }

        $effectivePeriod = (int) ($request->input('ksp_deposit_period') ?: $detectedPeriod);
        $endYearComparisonRule = $effectivePeriod === 1 ? 'gte:ksp_return_start_year' : 'gt:ksp_return_start_year';

        return [
            'is_ksp' => true,
            'rules' => [
                'ksp_annual_deposit_amount' => ['required', 'numeric', 'min:15000'],
                'ksp_deposit_period' => ['required', 'integer', 'min:1', 'max:100'],
                'ksp_insurance_coverage' => ['required', 'numeric', 'min:0'],
                'ksp_return_start_year' => ['required', 'integer', 'min:1', 'max:100'],
                'ksp_return_end_year' => ['required', 'integer', 'min:1', 'max:100', $endYearComparisonRule],
                'ksp_annual_return_amount' => ['required', 'numeric', 'min:0'],
                'ksp_return_frequency' => ['required', 'string', Rule::in([
                    'Yearly', 'Half-Yearly', 'Quarterly', 'Monthly', 'At Maturity',
                    'YEARLY', 'HALF_YEARLY', 'QUARTERLY', 'MONTHLY', 'AT_MATURITY',
                ])],
                'ksp_premature_withdrawal' => ['required', 'string', Rule::in([
                    'Not Allowed', 'Allowed', 'Allowed with Penalty',
                    'NOT_ALLOWED', 'ALLOWED', 'ALLOWED_WITH_PENALTY',
                ])],
            ],
            'messages' => [
                'ksp_annual_deposit_amount.required' => 'Annual Deposit Amount is required for KSP scheme.',
                'ksp_annual_deposit_amount.numeric' => 'Annual Deposit Amount must be a valid number.',
                'ksp_annual_deposit_amount.min' => 'Annual Deposit Amount must be at least ₹15,000.',
                'ksp_deposit_period.required' => 'Deposit Period is required for KSP scheme.',
                'ksp_deposit_period.integer' => 'Deposit Period must be a valid whole number of years.',
                'ksp_deposit_period.min' => 'Deposit Period must be at least 1 year.',
                'ksp_insurance_coverage.required' => 'Insurance Coverage is required for KSP scheme.',
                'ksp_insurance_coverage.numeric' => 'Insurance Coverage must be a valid numeric amount.',
                'ksp_insurance_coverage.min' => 'Insurance Coverage cannot be negative.',
                'ksp_return_start_year.required' => 'Return Start Year is required for KSP scheme.',
                'ksp_return_start_year.integer' => 'Return Start Year must be a valid year number.',
                'ksp_return_end_year.required' => 'Return End Year is required for KSP scheme.',
                'ksp_return_end_year.integer' => 'Return End Year must be a valid year number.',
                'ksp_return_end_year.gt' => 'Return End Year must be greater than Return Start Year.',
                'ksp_return_end_year.gte' => 'Return End Year must be greater than or equal to Return Start Year.',
                'ksp_annual_return_amount.required' => 'Annual Return Amount is required for KSP scheme.',
                'ksp_annual_return_amount.numeric' => 'Annual Return Amount must be a valid numeric amount.',
                'ksp_annual_return_amount.min' => 'Annual Return Amount cannot be negative.',
                'ksp_return_frequency.required' => 'Return Frequency is required for KSP scheme.',
                'ksp_return_frequency.in' => 'Selected Return Frequency is invalid.',
                'ksp_premature_withdrawal.required' => 'Premature Withdrawal setting is required for KSP scheme.',
                'ksp_premature_withdrawal.in' => 'Selected Premature Withdrawal setting is invalid.',
            ],
        ];
    }

    /**
     * Build the normalized KSP configuration array from validated data.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function extractKspConfiguration(array $validated): array
    {
        return [
            'annual_deposit_amount' => round((float) $validated['ksp_annual_deposit_amount'], 2),
            'deposit_period' => (int) $validated['ksp_deposit_period'],
            'insurance_coverage' => round((float) $validated['ksp_insurance_coverage'], 2),
            'return_start_year' => (int) $validated['ksp_return_start_year'],
            'return_end_year' => (int) $validated['ksp_return_end_year'],
            'annual_return_amount' => round((float) $validated['ksp_annual_return_amount'], 2),
            'return_frequency' => (string) $validated['ksp_return_frequency'],
            'premature_withdrawal' => (string) $validated['ksp_premature_withdrawal'],
        ];
    }

    /**
     * Authorize scheme management. Super Admin and Branch Admin have authority.
     */
    protected function authorizeSchemeManagement(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized access: Admin privileges required to manage deposit schemes.');
        }
    }
}
