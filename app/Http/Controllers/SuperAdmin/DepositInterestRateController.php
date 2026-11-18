<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DepositScheme;
use App\Models\DepositSchemeInterestRate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepositInterestRateController extends Controller
{
    /**
     * Display a listing of configured multi-year deposit interest rates.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('admin.deposit-schemes.index');
    }

    /**
     * Show the form for creating a new interest rate configuration.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('admin.deposit-schemes.index');
    }

    /**
     * Store a newly created interest rate configuration in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deposit_scheme_id' => ['required', 'integer', 'exists:deposit_schemes,id'],
            'tenure' => ['required', 'integer', 'min:1', 'max:600'],
            'tenure_unit' => ['required', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'string', Rule::in([
                DepositSchemeInterestRate::STATUS_ACTIVE,
                DepositSchemeInterestRate::STATUS_INACTIVE,
            ])],
            'calculation_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'deposit_scheme_id.required' => 'Please select a deposit scheme.',
            'tenure.required' => 'Please specify the deposit duration/tenure.',
            'interest_rate.required' => 'Please specify the annual interest rate percentage.',
            'effective_from.required' => 'Please select the effective start date.',
            'effective_to.after_or_equal' => 'Effective end date cannot be prior to start date.',
        ]);

        $scheme = DepositScheme::findOrFail($validated['deposit_scheme_id']);

        // Check for conflicting / overlapping active configurations for the same scheme and tenure
        if ($validated['status'] === DepositSchemeInterestRate::STATUS_ACTIVE) {
            $hasOverlap = DepositSchemeInterestRate::hasOverlap(
                (int) $validated['deposit_scheme_id'],
                (int) $validated['tenure'],
                (string) $validated['tenure_unit'],
                (string) $validated['effective_from'],
                $validated['effective_to'] ?? null
            );

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'tenure' => "An active interest rate configuration already exists for scheme '{$scheme->name}' for {$validated['tenure']} {$validated['tenure_unit']} with an overlapping effective period.",
                ]);
            }
        }

        $rate = DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $validated['deposit_scheme_id'],
            'tenure' => $validated['tenure'],
            'tenure_unit' => strtoupper(trim($validated['tenure_unit'])),
            'interest_rate' => $validated['interest_rate'],
            'senior_citizen_extra_rate' => $validated['senior_citizen_extra_rate'] ?? 0.00,
            'min_amount' => $validated['min_amount'] ?? $scheme->min_amount,
            'max_amount' => $validated['max_amount'] ?? $scheme->max_amount,
            'effective_from' => Carbon::parse($validated['effective_from'])->toDateString(),
            'effective_to' => ! empty($validated['effective_to']) ? Carbon::parse($validated['effective_to'])->toDateString() : null,
            'status' => $validated['status'],
            'calculation_method' => $validated['calculation_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Synchronize base scheme rate if this tier matches the scheme's standard duration
        $isMatchingDuration = (
            ((int) $rate->tenure === (int) $scheme->tenure && strtoupper(trim((string) $rate->tenure_unit)) === strtoupper(trim((string) $scheme->tenure_unit)))
            || abs($rate->getDurationInMonths() - $scheme->getDurationInMonths()) < 0.2
        );

        if ($rate->isActive() && $isMatchingDuration) {
            $scheme->update([
                'interest_rate' => $rate->interest_rate,
                'senior_citizen_extra_rate' => $rate->senior_citizen_extra_rate ?? 0.00,
            ]);
        }

        AuditLog::record(
            event: 'deposit_interest_rate_created',
            description: "Created interest rate configuration for scheme [{$scheme->name}]: {$rate->formatted_tenure} @ {$rate->interest_rate}% p.a. (Effective from {$rate->effective_from->format('Y-m-d')}). Status: {$rate->status}.",
            auditable: $rate,
            newValues: $rate->only(['deposit_scheme_id', 'tenure', 'tenure_unit', 'interest_rate', 'effective_from', 'effective_to', 'status'])
        );

        return redirect()->route('admin.deposit-schemes.index')
            ->with('success', "Interest rate of {$rate->interest_rate}% for {$rate->formatted_tenure} under '{$scheme->name}' configured successfully.");
    }

    /**
     * Show the form for editing an existing interest rate configuration.
     */
    public function edit(DepositSchemeInterestRate $interestRate): RedirectResponse
    {
        return redirect()->route('admin.deposit-schemes.index');
    }

    /**
     * Update an existing interest rate configuration in storage.
     */
    public function update(Request $request, DepositSchemeInterestRate $interestRate): RedirectResponse
    {
        $validated = $request->validate([
            'deposit_scheme_id' => ['required', 'integer', 'exists:deposit_schemes,id'],
            'tenure' => ['required', 'integer', 'min:1', 'max:600'],
            'tenure_unit' => ['required', 'string', Rule::in([
                DepositScheme::TENURE_DAYS,
                DepositScheme::TENURE_MONTHS,
                DepositScheme::TENURE_YEARS,
            ])],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'senior_citizen_extra_rate' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'string', Rule::in([
                DepositSchemeInterestRate::STATUS_ACTIVE,
                DepositSchemeInterestRate::STATUS_INACTIVE,
            ])],
            'calculation_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'effective_to.after_or_equal' => 'Effective end date cannot be prior to start date.',
        ]);

        $scheme = DepositScheme::findOrFail($validated['deposit_scheme_id']);

        // Check for conflicting / overlapping active configurations for the same scheme and tenure
        if ($validated['status'] === DepositSchemeInterestRate::STATUS_ACTIVE) {
            $hasOverlap = DepositSchemeInterestRate::hasOverlap(
                (int) $validated['deposit_scheme_id'],
                (int) $validated['tenure'],
                (string) $validated['tenure_unit'],
                (string) $validated['effective_from'],
                $validated['effective_to'] ?? null,
                $interestRate->id
            );

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'tenure' => "An active interest rate configuration already exists for scheme '{$scheme->name}' for {$validated['tenure']} {$validated['tenure_unit']} with an overlapping effective period.",
                ]);
            }
        }

        $oldValues = $interestRate->only(['deposit_scheme_id', 'tenure', 'tenure_unit', 'interest_rate', 'effective_from', 'effective_to', 'status']);

        $interestRate->update([
            'deposit_scheme_id' => $validated['deposit_scheme_id'],
            'tenure' => $validated['tenure'],
            'tenure_unit' => strtoupper(trim($validated['tenure_unit'])),
            'interest_rate' => $validated['interest_rate'],
            'senior_citizen_extra_rate' => $validated['senior_citizen_extra_rate'] ?? 0.00,
            'min_amount' => $validated['min_amount'] ?? $interestRate->min_amount,
            'max_amount' => $validated['max_amount'] ?? $interestRate->max_amount,
            'effective_from' => Carbon::parse($validated['effective_from'])->toDateString(),
            'effective_to' => ! empty($validated['effective_to']) ? Carbon::parse($validated['effective_to'])->toDateString() : null,
            'status' => $validated['status'],
            'calculation_method' => $validated['calculation_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        // Synchronize base scheme rate if this tier matches the scheme's standard duration
        $isMatchingDuration = (
            ((int) $interestRate->tenure === (int) $scheme->tenure && strtoupper(trim((string) $interestRate->tenure_unit)) === strtoupper(trim((string) $scheme->tenure_unit)))
            || abs($interestRate->getDurationInMonths() - $scheme->getDurationInMonths()) < 0.2
        );

        if ($interestRate->isActive() && $isMatchingDuration) {
            $scheme->update([
                'interest_rate' => $interestRate->interest_rate,
                'senior_citizen_extra_rate' => $interestRate->senior_citizen_extra_rate ?? 0.00,
            ]);
        }

        AuditLog::record(
            event: 'deposit_interest_rate_updated',
            description: "Updated interest rate configuration #{$interestRate->id} for scheme [{$scheme->name}]: {$interestRate->formatted_tenure} @ {$interestRate->interest_rate}% p.a.",
            auditable: $interestRate,
            oldValues: $oldValues,
            newValues: $interestRate->only(['deposit_scheme_id', 'tenure', 'tenure_unit', 'interest_rate', 'effective_from', 'effective_to', 'status'])
        );

        return redirect()->route('admin.deposit-schemes.index')
            ->with('success', "Interest rate configuration for {$interestRate->formatted_tenure} updated successfully.");
    }

    /**
     * Toggle configuration active/inactive status.
     */
    public function toggleStatus(DepositSchemeInterestRate $interestRate): RedirectResponse|JsonResponse
    {
        $newStatus = $interestRate->isActive()
            ? DepositSchemeInterestRate::STATUS_INACTIVE
            : DepositSchemeInterestRate::STATUS_ACTIVE;

        // If activating, verify no active overlap
        if ($newStatus === DepositSchemeInterestRate::STATUS_ACTIVE) {
            $hasOverlap = DepositSchemeInterestRate::hasOverlap(
                $interestRate->deposit_scheme_id,
                $interestRate->tenure,
                $interestRate->tenure_unit,
                $interestRate->effective_from->toDateString(),
                $interestRate->effective_to?->toDateString(),
                $interestRate->id
            );

            if ($hasOverlap) {
                if (request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot activate: Another active rate configuration already covers this tenure and date range.',
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', 'Cannot activate: Another active rate configuration already covers this tenure and date range.');
            }
        }

        $oldStatus = $interestRate->status;
        $interestRate->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        AuditLog::record(
            event: 'deposit_interest_rate_status_toggled',
            description: "Interest rate configuration #{$interestRate->id} ({$interestRate->formatted_tenure} @ {$interestRate->interest_rate}%) status changed from {$oldStatus} to {$newStatus}.",
            auditable: $interestRate,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus]
        );

        $scheme = $interestRate->scheme;
        if ($scheme) {
            $isMatchingDuration = (
                ((int) $interestRate->tenure === (int) $scheme->tenure && strtoupper(trim((string) $interestRate->tenure_unit)) === strtoupper(trim((string) $scheme->tenure_unit)))
                || abs($interestRate->getDurationInMonths() - $scheme->getDurationInMonths()) < 0.2
            );

            if ($isMatchingDuration && $newStatus === DepositSchemeInterestRate::STATUS_ACTIVE) {
                $scheme->update([
                    'interest_rate' => $interestRate->interest_rate,
                    'senior_citizen_extra_rate' => $interestRate->senior_citizen_extra_rate ?? 0.00,
                ]);
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "Configuration is now {$newStatus}.",
            ]);
        }

        return redirect()->back()
            ->with('success', "Configuration for {$interestRate->formatted_tenure} is now {$newStatus}.");
    }

    /**
     * Remove the specified configuration from storage.
     */
    public function destroy(DepositSchemeInterestRate $interestRate): RedirectResponse
    {
        $scheme = $interestRate->scheme;
        $tenure = $interestRate->formatted_tenure;

        $interestRate->delete();

        AuditLog::record(
            event: 'deposit_interest_rate_deleted',
            description: "Deleted interest rate configuration for scheme [{$scheme?->name}]: {$tenure} @ {$interestRate->interest_rate}%.",
            auditable: $interestRate
        );

        return redirect()->route('admin.deposit-schemes.index')
            ->with('success', "Interest rate configuration for {$tenure} has been removed.");
    }

    /**
     * AJAX/API endpoint: Return available tenures and interest rates for a deposit scheme.
     */
    public function getRatesForScheme(Request $request, DepositScheme $depositScheme): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $rates = $depositScheme->getAvailableTenures($date);

        $rateData = $rates->map(function (DepositSchemeInterestRate $rate) {
            return [
                'id' => $rate->id,
                'tenure' => $rate->tenure,
                'tenure_unit' => $rate->tenure_unit,
                'formatted_tenure' => $rate->getFormattedTenure(),
                'interest_rate' => (float) $rate->interest_rate,
                'senior_citizen_extra_rate' => (float) ($rate->senior_citizen_extra_rate ?? 0.00),
                'min_amount' => $rate->min_amount !== null ? (float) $rate->min_amount : null,
                'max_amount' => $rate->max_amount !== null ? (float) $rate->max_amount : null,
                'effective_from' => $rate->effective_from ? Carbon::parse($rate->effective_from)->format('Y-m-d') : null,
                'effective_to' => $rate->effective_to ? Carbon::parse($rate->effective_to)->format('Y-m-d') : null,
                'calculation_method' => $rate->calculation_method,
            ];
        });

        return response()->json([
            'success' => true,
            'scheme' => [
                'id' => $depositScheme->id,
                'name' => $depositScheme->name,
                'code' => $depositScheme->code,
                'scheme_type' => $depositScheme->scheme_type,
                'calculation_type' => $depositScheme->schemeType?->calculation_type ?? 'LUMP_SUM',
                'collection_frequency' => $depositScheme->collection_frequency,
                'min_amount' => (float) $depositScheme->min_amount,
                'max_amount' => $depositScheme->max_amount !== null ? (float) $depositScheme->max_amount : null,
                'default_tenure' => (int) $depositScheme->tenure,
                'default_tenure_unit' => $depositScheme->tenure_unit,
                'default_interest_rate' => (float) $depositScheme->interest_rate,
            ],
            'rates' => $rateData,
            'has_multi_year' => $depositScheme->hasConfiguredTenureRates(),
        ]);
    }

    /**
     * Synchronize interest rates between deposit schemes and the interest rates table.
     */
    public function syncAll(Request $request): RedirectResponse|JsonResponse
    {
        $schemes = DepositScheme::with('interestRates')->get();
        $syncedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($schemes, &$syncedCount, &$createdCount, &$updatedCount) {
            foreach ($schemes as $scheme) {
                $syncedCount++;
                $tenure = (int) ($scheme->tenure ?? 1);
                $unit = strtoupper(trim((string) ($scheme->tenure_unit ?? DepositScheme::TENURE_YEARS)));
                $schemeRate = (float) ($scheme->interest_rate ?? 0.00);
                $seniorExtra = (float) ($scheme->senior_citizen_extra_rate ?? 0.00);
                $status = $scheme->isActive() ? DepositSchemeInterestRate::STATUS_ACTIVE : DepositSchemeInterestRate::STATUS_INACTIVE;
                $effectiveDate = $scheme->created_at ? $scheme->created_at->toDateString() : '2020-01-01';

                // Find matching primary rate record for this scheme duration
                $matchingRate = $scheme->interestRates()
                    ->where('tenure', $tenure)
                    ->where('tenure_unit', $unit)
                    ->first();

                if (! $matchingRate) {
                    $targetMonths = $scheme->getDurationInMonths();
                    $matchingRate = $scheme->interestRates->first(function (DepositSchemeInterestRate $r) use ($targetMonths) {
                        return abs($r->getDurationInMonths() - $targetMonths) < 0.2;
                    });
                }

                if (! $matchingRate) {
                    // Create missing record for this scheme's duration
                    $scheme->interestRates()->create([
                        'tenure' => $tenure,
                        'tenure_unit' => $unit,
                        'interest_rate' => $schemeRate,
                        'senior_citizen_extra_rate' => $seniorExtra,
                        'min_amount' => $scheme->min_amount,
                        'max_amount' => $scheme->max_amount,
                        'status' => $status,
                        'effective_from' => $effectiveDate,
                        'created_by' => auth()->id(),
                    ]);
                    $createdCount++;
                } else {
                    // If rates, senior extra, amounts, or status are out of sync, align them
                    $changed = false;
                    $updates = [];

                    if ((float) $matchingRate->interest_rate !== $schemeRate) {
                        $updates['interest_rate'] = $schemeRate;
                        $changed = true;
                    }
                    if ((float) ($matchingRate->senior_citizen_extra_rate ?? 0.00) !== $seniorExtra) {
                        $updates['senior_citizen_extra_rate'] = $seniorExtra;
                        $changed = true;
                    }
                    if ($matchingRate->status !== $status) {
                        $updates['status'] = $status;
                        $changed = true;
                    }
                    if ((float) $matchingRate->min_amount !== (float) $scheme->min_amount) {
                        $updates['min_amount'] = $scheme->min_amount;
                        $changed = true;
                    }
                    if ((float) ($matchingRate->max_amount ?? 0) !== (float) ($scheme->max_amount ?? 0)) {
                        $updates['max_amount'] = $scheme->max_amount;
                        $changed = true;
                    }

                    if ($changed) {
                        $matchingRate->update($updates);
                        $updatedCount++;
                    }
                }
            }

            AuditLog::record(
                event: 'deposit_interest_rates_synced',
                description: "Synchronized interest rates across {$syncedCount} deposit schemes ({$createdCount} created, {$updatedCount} updated).",
                auditable: null,
                newValues: [
                    'schemes_count' => $syncedCount,
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                ]
            );
        });

        $message = "Interest rates synchronized successfully across {$syncedCount} deposit schemes ({$createdCount} new rates registered, {$updatedCount} rates aligned).";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'schemes_count' => $syncedCount,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
