<?php

namespace App\Services;

use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\CollectionPayment;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\DepositSchemeCommissionHistory;
use App\Models\DepositSchemeInterestRate;
use App\Models\DepositSchemeRankCommission;
use App\Models\SchemeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FdCommissionSchemeService
{
    /**
     * Calculate total commission percentage as the sum of all eligible rank commission percentages.
     * Uses decimal-safe precision.
     */
    public function calculateTotalCommission(array $ranksData): float
    {
        $total = '0.00';

        foreach ($ranksData as $item) {
            $isEligible = filter_var($item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isEligible && isset($item['commission_percentage'])) {
                $pct = number_format((float) $item['commission_percentage'], 2, '.', '');
                if ((float) $pct < 0) {
                    throw ValidationException::withMessages([
                        'ranks' => 'Commission percentage cannot be negative.',
                    ]);
                }
                $total = bcadd($total, $pct, 2);
            }
        }

        return (float) $total;
    }

    /**
     * Create a new FD Scheme along with rank commission rules and audit history.
     * Supports per-year commission configuration (payout_year 1-50).
     *
     * @throws ValidationException
     */
    public function createScheme(array $schemeData, array $ranksData, ?int $userId = null): DepositScheme
    {
        $payoutYear = max(1, min(50, (int) ($schemeData['payout_year'] ?? 1)));
        $userId = $userId ?? auth()->id();

        // Calculate and validate total commission percentage
        $totalCommission = $this->calculateTotalCommission($ranksData);

        $yearsData = [];
        if (! empty($schemeData['years_data'])) {
            $yearsData = is_array($schemeData['years_data'])
                ? $schemeData['years_data']
                : json_decode($schemeData['years_data'], true);
        }

        if (! empty($yearsData) && is_array($yearsData)) {
            $cumulativeTotal = 0.0;
            foreach ($yearsData as $yr => $rows) {
                if (! is_array($rows)) {
                    continue;
                }
                $yearTotal = 0.0;
                foreach ($rows as $item) {
                    $isEligible = filter_var($item['eligible'] ?? $item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($isEligible) {
                        $pct = (float) ($item['pct'] ?? $item['commission_percentage'] ?? 0.0);
                        if ($pct < 0) {
                            throw ValidationException::withMessages([
                                'ranks' => 'Commission percentage cannot be negative.',
                            ]);
                        }
                        $yearTotal += $pct;
                        $cumulativeTotal += $pct;
                    }
                }
                if ($yearTotal > 100.00) {
                    throw ValidationException::withMessages([
                        'ranks' => "Year {$yr} total commission percentage cannot exceed 100.00%.",
                    ]);
                }
            }
            $totalCommission = round($cumulativeTotal, 2);
        }

        if ($totalCommission > 100.00) {
            throw ValidationException::withMessages([
                'ranks' => 'The combined total commission percentage cannot exceed 100.00%.',
            ]);
        }

        return DB::transaction(function () use ($schemeData, $ranksData, $totalCommission, $userId, $yearsData) {
            $schemeType = strtoupper(trim($schemeData['scheme_type'] ?? DepositScheme::TYPE_FD));
            $schemeTypeModel = SchemeType::where('code', $schemeType)->first();

            $collectionFreq = $schemeData['collection_frequency'] ?? match ($schemeType) {
                DepositScheme::TYPE_RD => DepositScheme::FREQ_MONTHLY,
                DepositScheme::TYPE_DD => DepositScheme::FREQ_DAILY,
                default => DepositScheme::FREQ_ONE_TIME,
            };

            $payoutFreq = $schemeData['interest_payout_frequency'] ?? match ($schemeType) {
                'MIS' => DepositScheme::PAYOUT_MONTHLY,
                default => DepositScheme::PAYOUT_AT_MATURITY,
            };

            $code = ! empty($schemeData['code'])
                ? trim($schemeData['code'])
                : DepositScheme::generateSchemeCode(
                    $schemeType,
                    $schemeData['name'],
                    (int) $schemeData['tenure'],
                    (string) ($schemeData['tenure_unit'] ?? DepositScheme::TENURE_MONTHS)
                );

            $scheme = DepositScheme::create([
                'name' => trim($schemeData['name']),
                'code' => $code,
                'scheme_type_id' => $schemeTypeModel?->id,
                'scheme_type' => $schemeType,
                'description' => $schemeData['description'] ?? null,
                'min_amount' => $schemeData['min_amount'] ?? 1000.00,
                'max_amount' => $schemeData['max_amount'] ?? null,
                'tenure' => (int) $schemeData['tenure'],
                'tenure_unit' => strtoupper(trim($schemeData['tenure_unit'] ?? DepositScheme::TENURE_MONTHS)),
                'interest_rate' => $schemeData['interest_rate'],
                'total_commission_percentage' => $totalCommission,
                'promotion_target_percentage' => isset($schemeData['promotion_target_percentage']) && $schemeData['promotion_target_percentage'] !== '' ? (float) $schemeData['promotion_target_percentage'] : null,
                'collection_frequency' => $collectionFreq,
                'interest_payout_frequency' => $payoutFreq,
                'senior_citizen_extra_rate' => isset($schemeData['senior_citizen_extra_rate']) && $schemeData['senior_citizen_extra_rate'] !== '' ? (float) $schemeData['senior_citizen_extra_rate'] : 0.00,
                'lock_in_months' => $schemeData['lock_in_months'] ?? 0,
                'allow_premature_withdrawal' => $schemeData['allow_premature_withdrawal'] ?? true,
                'premature_penalty_percent' => $schemeData['premature_penalty_percent'] ?? 1.00,
                'allow_loan_against_deposit' => $schemeData['allow_loan_against_deposit'] ?? true,
                'max_loan_percentage' => $schemeData['max_loan_percentage'] ?? 80.00,
                'status' => strtoupper(trim($schemeData['status'] ?? DepositScheme::STATUS_ACTIVE)),
                'created_by' => $userId,
            ]);

            if (! empty($schemeData['tenure_rates']) && is_array($schemeData['tenure_rates'])) {
                $scheme->syncInterestRates($schemeData['tenure_rates'], $userId);
            }

            // Save rank commission rules (year-specific or all submitted years)
            if (! empty($yearsData) && is_array($yearsData)) {
                $rankSnapshot = [];
                foreach ($yearsData as $yr => $rows) {
                    $yr = (int) $yr;
                    if ($yr < 1 || $yr > 50) {
                        continue;
                    }
                    foreach ($rows as $item) {
                        $rankId = (int) $item['rank_id'];
                        $isEligible = filter_var($item['eligible'] ?? $item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $pct = $isEligible ? (float) ($item['pct'] ?? $item['commission_percentage'] ?? 0.00) : 0.00;

                        DepositSchemeRankCommission::create([
                            'deposit_scheme_id' => $scheme->id,
                            'rank_id' => $rankId,
                            'commission_percentage' => $pct,
                            'is_eligible' => $isEligible,
                            'payout_year' => $yr,
                        ]);

                        $rank = AgentRank::find($rankId);
                        $rankSnapshot[] = [
                            'rank_id' => $rankId,
                            'rank_name' => $rank?->name,
                            'rank_code' => $rank?->code,
                            'sort_order' => $rank?->sort_order,
                            'is_eligible' => $isEligible,
                            'commission_percentage' => $pct,
                            'payout_year' => $yr,
                        ];
                    }
                }
                $this->syncCommissionRules($scheme, $rankSnapshot, $userId);
            } else {
                $effectivePayoutYear = ! empty($schemeData['payout_year'])
                    ? (int) $schemeData['payout_year']
                    : max(1, $scheme->getTenureInYears());

                $rankSnapshot = [];
                foreach ($ranksData as $item) {
                    $rankId = (int) $item['rank_id'];
                    $isEligible = filter_var($item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $pct = $isEligible ? (float) ($item['commission_percentage'] ?? 0.00) : 0.00;

                    DepositSchemeRankCommission::create([
                        'deposit_scheme_id' => $scheme->id,
                        'rank_id' => $rankId,
                        'commission_percentage' => $pct,
                        'is_eligible' => $isEligible,
                        'payout_year' => $effectivePayoutYear,
                    ]);

                    $rank = AgentRank::find($rankId);
                    $rankSnapshot[] = [
                        'rank_id' => $rankId,
                        'rank_name' => $rank?->name,
                        'rank_code' => $rank?->code,
                        'sort_order' => $rank?->sort_order,
                        'is_eligible' => $isEligible,
                        'commission_percentage' => $pct,
                        'payout_year' => $effectivePayoutYear,
                    ];
                }

                // Sync with CommissionRules for calculation integration
                $this->syncCommissionRules($scheme, $rankSnapshot, $userId);
            }

            // Record configuration history
            DepositSchemeCommissionHistory::create([
                'deposit_scheme_id' => $scheme->id,
                'user_id' => $userId,
                'action' => 'created',
                'total_commission_percentage' => $totalCommission,
                'old_values' => null,
                'new_values' => [
                    'name' => $scheme->name,
                    'code' => $scheme->code,
                    'interest_rate' => $scheme->interest_rate,
                    'senior_citizen_extra_rate' => $scheme->senior_citizen_extra_rate,
                    'tenure' => $scheme->tenure,
                    'tenure_unit' => $scheme->tenure_unit,
                    'status' => $scheme->status,
                    'total_commission_percentage' => $totalCommission,
                    'promotion_target_percentage' => $scheme->promotion_target_percentage,
                ],
                'rank_rules_snapshot' => $rankSnapshot,
                'notes' => $schemeData['notes'] ?? 'Initial creation of FD scheme and rank commissions.',
            ]);

            // System Audit Log
            AuditLog::record(
                event: 'fd_scheme_created',
                description: "Created FD Scheme [{$scheme->name}] ({$scheme->code}) with {$scheme->interest_rate}% interest, {$scheme->tenure} {$scheme->tenure_unit} tenure, and {$totalCommission}% total commission.",
                auditable: $scheme,
                newValues: [
                    'name' => $scheme->name,
                    'code' => $scheme->code,
                    'interest_rate' => $scheme->interest_rate,
                    'total_commission_percentage' => $totalCommission,
                    'ranks_count' => count($rankSnapshot),
                ]
            );

            return $scheme;
        });
    }

    /**
     * Update an existing FD Scheme and its rank commissions safely without altering historical data.
     *
     * @throws ValidationException
     */
    public function updateScheme(
        DepositScheme $scheme,
        array $schemeData,
        array $ranksData,
        ?string $notes = null,
        ?int $userId = null
    ): DepositScheme {
        $userId = $userId ?? auth()->id();
        $payoutYear = max(1, min(50, (int) ($schemeData['payout_year'] ?? 1)));

        $totalCommission = $this->calculateTotalCommission($ranksData);

        $yearsData = [];
        if (! empty($schemeData['years_data'])) {
            $yearsData = is_array($schemeData['years_data'])
                ? $schemeData['years_data']
                : json_decode($schemeData['years_data'], true);
        }

        if (! empty($yearsData) && is_array($yearsData)) {
            $cumulativeTotal = 0.0;
            foreach ($yearsData as $yr => $rows) {
                if (! is_array($rows)) {
                    continue;
                }
                $yearTotal = 0.0;
                foreach ($rows as $item) {
                    $isEligible = filter_var($item['eligible'] ?? $item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($isEligible) {
                        $pct = (float) ($item['pct'] ?? $item['commission_percentage'] ?? 0.0);
                        if ($pct < 0) {
                            throw ValidationException::withMessages([
                                'ranks' => 'Commission percentage cannot be negative.',
                            ]);
                        }
                        $yearTotal += $pct;
                        $cumulativeTotal += $pct;
                    }
                }
                if ($yearTotal > 100.00) {
                    throw ValidationException::withMessages([
                        'ranks' => "Year {$yr} total commission percentage cannot exceed 100.00%.",
                    ]);
                }
            }
            $totalCommission = round($cumulativeTotal, 2);
        }

        if ($totalCommission > 100.00) {
            throw ValidationException::withMessages([
                'ranks' => 'The combined total commission percentage cannot exceed 100.00%.',
            ]);
        }

        return DB::transaction(function () use ($scheme, $schemeData, $ranksData, $totalCommission, $notes, $userId, $yearsData) {
            $oldValues = [
                'name' => $scheme->name,
                'code' => $scheme->code,
                'interest_rate' => $scheme->interest_rate,
                'senior_citizen_extra_rate' => $scheme->senior_citizen_extra_rate,
                'tenure' => $scheme->tenure,
                'tenure_unit' => $scheme->tenure_unit,
                'min_amount' => $scheme->min_amount,
                'max_amount' => $scheme->max_amount,
                'status' => $scheme->status,
                'total_commission_percentage' => $scheme->total_commission_percentage,
                'promotion_target_percentage' => $scheme->promotion_target_percentage,
                'description' => $scheme->description,
            ];

            $schemeType = strtoupper(trim($schemeData['scheme_type'] ?? $scheme->scheme_type ?? DepositScheme::TYPE_FD));
            $schemeTypeModel = SchemeType::where('code', $schemeType)->first();

            $collectionFreq = $schemeData['collection_frequency'] ?? $scheme->collection_frequency ?? match ($schemeType) {
                DepositScheme::TYPE_RD => DepositScheme::FREQ_MONTHLY,
                DepositScheme::TYPE_DD => DepositScheme::FREQ_DAILY,
                default => DepositScheme::FREQ_ONE_TIME,
            };

            $payoutFreq = $schemeData['interest_payout_frequency'] ?? $scheme->interest_payout_frequency ?? DepositScheme::PAYOUT_AT_MATURITY;

            $scheme->update([
                'name' => trim($schemeData['name']),
                'code' => ! empty($schemeData['code']) ? trim($schemeData['code']) : $scheme->code,
                'scheme_type_id' => $schemeTypeModel?->id ?? $scheme->scheme_type_id,
                'scheme_type' => $schemeType,
                'description' => $schemeData['description'] ?? $scheme->description,
                'min_amount' => $schemeData['min_amount'] ?? $scheme->min_amount,
                'max_amount' => $schemeData['max_amount'] ?? $scheme->max_amount,
                'tenure' => (int) $schemeData['tenure'],
                'tenure_unit' => strtoupper(trim($schemeData['tenure_unit'] ?? $scheme->tenure_unit)),
                'interest_rate' => $schemeData['interest_rate'],
                'senior_citizen_extra_rate' => array_key_exists('senior_citizen_extra_rate', $schemeData)
                    ? ($schemeData['senior_citizen_extra_rate'] !== '' && $schemeData['senior_citizen_extra_rate'] !== null ? (float) $schemeData['senior_citizen_extra_rate'] : 0.00)
                    : $scheme->senior_citizen_extra_rate,
                'collection_frequency' => $collectionFreq,
                'interest_payout_frequency' => $payoutFreq,
                'total_commission_percentage' => $totalCommission,
                'promotion_target_percentage' => array_key_exists('promotion_target_percentage', $schemeData)
                    ? ($schemeData['promotion_target_percentage'] !== '' && $schemeData['promotion_target_percentage'] !== null ? (float) $schemeData['promotion_target_percentage'] : null)
                    : $scheme->promotion_target_percentage,
                'status' => strtoupper(trim($schemeData['status'] ?? $scheme->status)),
            ]);

            if (! empty($schemeData['tenure_rates']) && is_array($schemeData['tenure_rates'])) {
                $scheme->syncInterestRates($schemeData['tenure_rates'], $userId);
            } else {
                // Keep any pre-existing matching DepositSchemeInterestRate tier synchronized without creating artificial rows
                $matchingRate = $scheme->findInterestRateRecord((int) $scheme->tenure, (string) $scheme->tenure_unit);
                if ($matchingRate) {
                    $matchingRate->update([
                        'interest_rate' => $scheme->interest_rate,
                        'senior_citizen_extra_rate' => $scheme->senior_citizen_extra_rate ?? 0.00,
                        'updated_by' => $userId,
                    ]);
                }
            }

            // Update or create rank commissions (year-specific or all submitted years)
            if (! empty($yearsData) && is_array($yearsData)) {
                $rankSnapshot = [];
                $submittedYears = [];
                foreach ($yearsData as $yr => $rows) {
                    $yr = (int) $yr;
                    if ($yr < 1 || $yr > 50) {
                        continue;
                    }
                    $submittedYears[] = $yr;

                    foreach ($rows as $item) {
                        $rankId = (int) $item['rank_id'];
                        $isEligible = filter_var($item['eligible'] ?? $item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $pct = $isEligible ? (float) ($item['pct'] ?? $item['commission_percentage'] ?? 0.00) : 0.00;

                        DepositSchemeRankCommission::updateOrCreate(
                            [
                                'deposit_scheme_id' => $scheme->id,
                                'rank_id' => $rankId,
                                'payout_year' => $yr,
                            ],
                            [
                                'commission_percentage' => $pct,
                                'is_eligible' => $isEligible,
                            ]
                        );

                        $rank = AgentRank::find($rankId);
                        $rankSnapshot[] = [
                            'rank_id' => $rankId,
                            'rank_name' => $rank?->name,
                            'rank_code' => $rank?->code,
                            'sort_order' => $rank?->sort_order,
                            'is_eligible' => $isEligible,
                            'commission_percentage' => $pct,
                            'payout_year' => $yr,
                        ];
                    }
                }

                if (! empty($submittedYears)) {
                    DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                        ->whereNotIn('payout_year', $submittedYears)
                        ->delete();
                }

                // Sync with CommissionRules
                $this->syncCommissionRules($scheme, $rankSnapshot, $userId);
            } else {
                $effectivePayoutYear = ! empty($schemeData['payout_year'])
                    ? (int) $schemeData['payout_year']
                    : max(1, $scheme->getTenureInYears());

                // Remove rank rules from other payout years for this scheme
                DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                    ->where('payout_year', '!=', $effectivePayoutYear)
                    ->delete();

                $rankSnapshot = [];
                foreach ($ranksData as $item) {
                    $rankId = (int) $item['rank_id'];
                    $isEligible = filter_var($item['is_eligible'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $pct = $isEligible ? (float) ($item['commission_percentage'] ?? 0.00) : 0.00;

                    DepositSchemeRankCommission::updateOrCreate(
                        [
                            'deposit_scheme_id' => $scheme->id,
                            'rank_id' => $rankId,
                            'payout_year' => $effectivePayoutYear,
                        ],
                        [
                            'commission_percentage' => $pct,
                            'is_eligible' => $isEligible,
                        ]
                    );

                    $rank = AgentRank::find($rankId);
                    $rankSnapshot[] = [
                        'rank_id' => $rankId,
                        'rank_name' => $rank?->name,
                        'rank_code' => $rank?->code,
                        'sort_order' => $rank?->sort_order,
                        'is_eligible' => $isEligible,
                        'commission_percentage' => $pct,
                        'payout_year' => $effectivePayoutYear,
                    ];
                }

                // Sync with CommissionRules
                $this->syncCommissionRules($scheme, $rankSnapshot, $userId);
            }

            $newValues = [
                'name' => $scheme->name,
                'code' => $scheme->code,
                'interest_rate' => $scheme->interest_rate,
                'senior_citizen_extra_rate' => $scheme->senior_citizen_extra_rate,
                'tenure' => $scheme->tenure,
                'tenure_unit' => $scheme->tenure_unit,
                'min_amount' => $scheme->min_amount,
                'max_amount' => $scheme->max_amount,
                'status' => $scheme->status,
                'total_commission_percentage' => $totalCommission,
                'promotion_target_percentage' => $scheme->promotion_target_percentage,
                'description' => $scheme->description,
            ];

            // Record configuration audit history
            DepositSchemeCommissionHistory::create([
                'deposit_scheme_id' => $scheme->id,
                'user_id' => $userId,
                'action' => 'updated',
                'total_commission_percentage' => $totalCommission,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'rank_rules_snapshot' => $rankSnapshot,
                'notes' => $notes ?? 'Updated FD scheme configuration and rank commission percentages.',
            ]);

            // System Audit Log
            AuditLog::record(
                event: 'fd_scheme_updated',
                description: "Updated FD Scheme [{$scheme->name}] ({$scheme->code}). Total commission updated to {$totalCommission}%.",
                auditable: $scheme,
                oldValues: $oldValues,
                newValues: $newValues
            );

            return $scheme;
        });
    }

    /**
     * Toggle the status of an FD Scheme.
     */
    public function toggleStatus(DepositScheme $scheme, ?int $userId = null): DepositScheme
    {
        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use ($scheme, $userId) {
            $oldStatus = strtoupper((string) $scheme->status);
            $newStatus = ($oldStatus === DepositScheme::STATUS_ACTIVE)
                ? DepositScheme::STATUS_INACTIVE
                : DepositScheme::STATUS_ACTIVE;

            $scheme->update(['status' => $newStatus]);

            // Also toggle corresponding CommissionRules
            CommissionRule::where('deposit_scheme_id', $scheme->id)->update([
                'status' => $newStatus === DepositScheme::STATUS_ACTIVE ? CommissionRule::STATUS_ACTIVE : CommissionRule::STATUS_INACTIVE,
            ]);

            // Record configuration history
            DepositSchemeCommissionHistory::create([
                'deposit_scheme_id' => $scheme->id,
                'user_id' => $userId,
                'action' => 'status_toggled',
                'total_commission_percentage' => (float) $scheme->total_commission_percentage,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $newStatus],
                'rank_rules_snapshot' => null,
                'notes' => "Scheme status toggled from {$oldStatus} to {$newStatus}.",
            ]);

            AuditLog::record(
                event: 'fd_scheme_status_toggled',
                description: "Toggled status of FD Scheme [{$scheme->name}] from {$oldStatus} to {$newStatus}.",
                auditable: $scheme,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus]
            );

            return $scheme;
        });
    }

    /**
     * Determine if an FD scheme can be safely deleted.
     * Prevents deletion when financial records (deposits, collections, commissions) depend on it.
     */
    public function canDeleteScheme(DepositScheme $scheme): array
    {
        $depositCount = Deposit::where('deposit_scheme_id', $scheme->id)->count();
        if ($depositCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Cannot delete scheme '{$scheme->name}' because {$depositCount} deposit account(s) are linked to it. Please deactivate the scheme instead to preserve historical records.",
            ];
        }

        $commissionCount = Commission::where('deposit_scheme_id', $scheme->id)->count();
        if ($commissionCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Cannot delete scheme '{$scheme->name}' because {$commissionCount} commission ledger record(s) depend on it. Please deactivate the scheme instead to preserve financial records.",
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null,
        ];
    }

    /**
     * Safely delete an FD scheme if no dependent financial records exist, or force delete if requested.
     *
     * @throws ValidationException
     */
    public function deleteScheme(DepositScheme $scheme, ?int $userId = null, bool $force = false): bool
    {
        if (! $force) {
            $check = $this->canDeleteScheme($scheme);
            if (! $check['can_delete']) {
                throw ValidationException::withMessages([
                    'scheme' => $check['reason'],
                ]);
            }
        }

        $userId = $userId ?? auth()->id();

        return DB::transaction(function () use ($scheme, $userId, $force) {
            $schemeName = $scheme->name;
            $schemeCode = $scheme->code;

            // Record history before deletion
            DepositSchemeCommissionHistory::create([
                'deposit_scheme_id' => $scheme->id,
                'user_id' => $userId,
                'action' => 'deleted',
                'total_commission_percentage' => (float) $scheme->total_commission_percentage,
                'old_values' => $scheme->toArray(),
                'new_values' => null,
                'rank_rules_snapshot' => null,
                'notes' => "FD Scheme [{$schemeName}] ({$schemeCode}) deleted".($force ? ' (forced cleanup).' : '.'),
            ]);

            if ($force) {
                $depositIds = Deposit::where('deposit_scheme_id', $scheme->id)->pluck('id')->all();
                if (! empty($depositIds)) {
                    CollectionPayment::whereIn('deposit_id', $depositIds)->delete();
                    DepositCollection::whereIn('deposit_id', $depositIds)->delete();
                    Commission::whereIn('deposit_id', $depositIds)->delete();
                    Deposit::whereIn('id', $depositIds)->delete();
                }
                Commission::where('deposit_scheme_id', $scheme->id)->delete();
                DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)->delete();
                DepositSchemeInterestRate::where('deposit_scheme_id', $scheme->id)->delete();
            }

            // Soft-delete or remove associated commission rules
            CommissionRule::where('deposit_scheme_id', $scheme->id)->delete();

            // Delete scheme
            $scheme->delete();

            AuditLog::record(
                event: 'fd_scheme_deleted',
                description: "Deleted FD Scheme [{$schemeName}] ({$schemeCode})".($force ? ' with cascade cleanup.' : '.'),
                auditable: $scheme,
                oldValues: ['name' => $schemeName, 'code' => $schemeCode]
            );

            return true;
        });
    }

    /**
     * Synchronize scheme rank rules into commission_rules table for seamless CommissionService integration.
     */
    public function syncCommissionRules(DepositScheme $scheme, ?array $rankRules = null, ?int $userId = null): void
    {
        $today = now()->toDateString();
        $schemeActive = ($scheme->status === DepositScheme::STATUS_ACTIVE);

        if ($rankRules === null) {
            $rankRules = $scheme->rankCommissions()->get()->map(function ($rc) {
                return [
                    'rank_id' => $rc->rank_id,
                    'is_eligible' => (bool) $rc->is_eligible,
                    'commission_percentage' => (float) $rc->commission_percentage,
                    'payout_year' => (int) $rc->payout_year,
                ];
            })->all();
        }

        $isRecurringOrDaily = in_array(strtoupper((string) $scheme->scheme_type), [DepositScheme::TYPE_RD, DepositScheme::TYPE_DD]);
        $calculationBasis = $isRecurringOrDaily ? CommissionRule::BASIS_COLLECTION_AMOUNT : CommissionRule::BASIS_DEPOSIT_AMOUNT;
        $syncedRuleIds = [];

        foreach ($rankRules as $item) {
            $rankId = $item['rank_id'];
            $isEligible = $item['is_eligible'] ?? false;
            $rate = (float) ($item['commission_percentage'] ?? 0.00);
            $payoutYear = (int) ($item['payout_year'] ?? 1);

            $rank = AgentRank::find($rankId);
            $ruleName = ($payoutYear > 1)
                ? "{$scheme->name} - {$rank?->name} (Year {$payoutYear})"
                : "{$scheme->name} - {$rank?->name}";

            $ruleQuery = CommissionRule::where('deposit_scheme_id', $scheme->id)
                ->where('rank_id', $rankId);

            if ($payoutYear > 1) {
                $rule = (clone $ruleQuery)->where(function ($q) use ($payoutYear, $ruleName) {
                    $q->where('name', $ruleName)
                        ->orWhere('name', 'like', "%(Year {$payoutYear})%");
                })->first();
            } else {
                $rule = (clone $ruleQuery)->where(function ($q) use ($ruleName) {
                    $q->where('name', $ruleName)
                        ->orWhere('name', 'not like', '%(Year %');
                })->first();
            }

            if ($isEligible && $rate > 0) {
                $ruleStatus = $schemeActive ? CommissionRule::STATUS_ACTIVE : CommissionRule::STATUS_INACTIVE;

                if ($rule) {
                    $rule->update([
                        'rate' => $rate,
                        'status' => $ruleStatus,
                        'name' => $ruleName,
                        'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                        'calculation_basis' => $calculationBasis,
                        'effective_from' => $today,
                    ]);
                } else {
                    $rule = CommissionRule::create([
                        'name' => $ruleName,
                        'code' => CommissionRule::generateRuleCode(),
                        'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                        'calculation_basis' => $calculationBasis,
                        'rate' => $rate,
                        'deposit_scheme_id' => $scheme->id,
                        'rank_id' => $rankId,
                        'effective_from' => $today,
                        'status' => $ruleStatus,
                        'created_by' => $userId,
                    ]);
                }
                $syncedRuleIds[] = $rule->id;
            } elseif ($rule) {
                // If previously created but now not eligible or 0 rate, mark inactive
                $rule->update(['status' => CommissionRule::STATUS_INACTIVE]);
            }
        }

        if (! empty($syncedRuleIds)) {
            CommissionRule::where('deposit_scheme_id', $scheme->id)
                ->whereNotIn('id', $syncedRuleIds)
                ->update(['status' => CommissionRule::STATUS_INACTIVE]);
        }
    }

    /**
     * Aggregate rank rules across all payout years so each rank receives its cumulative commission percentage.
     */
    public function aggregateRankRulesAcrossYears(array $rankRules): array
    {
        $aggregated = [];
        foreach ($rankRules as $row) {
            $rankId = $row['rank_id'];
            if (! isset($aggregated[$rankId])) {
                $aggregated[$rankId] = [
                    'rank_id' => $rankId,
                    'rank_name' => $row['rank_name'] ?? null,
                    'rank_code' => $row['rank_code'] ?? null,
                    'sort_order' => $row['sort_order'] ?? null,
                    'is_eligible' => false,
                    'commission_percentage' => 0.00,
                ];
            }
            if (! empty($row['is_eligible'])) {
                $aggregated[$rankId]['is_eligible'] = true;
                $aggregated[$rankId]['commission_percentage'] = round(
                    $aggregated[$rankId]['commission_percentage'] + (float) ($row['commission_percentage'] ?? 0.00),
                    2
                );
            }
        }

        return array_values($aggregated);
    }
}
