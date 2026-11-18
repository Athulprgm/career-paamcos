<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class DepositScheme extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'deposit_schemes';

    // Scheme Status constants
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    // Scheme Types
    public const TYPE_FD = 'FD';

    public const TYPE_RD = 'RD';

    public const TYPE_DD = 'DD';

    public const TYPE_DOUBLE = 'DOUBLE';

    public const TYPE_OTHER = 'OTHER';

    // Tenure Units
    public const TENURE_DAYS = 'DAYS';

    public const TENURE_MONTHS = 'MONTHS';

    public const TENURE_YEARS = 'YEARS';

    // Collection Frequencies
    public const FREQ_DAILY = 'DAILY';

    public const FREQ_WEEKLY = 'WEEKLY';

    public const FREQ_MONTHLY = 'MONTHLY';

    public const FREQ_QUARTERLY = 'QUARTERLY';

    public const FREQ_ONE_TIME = 'ONE_TIME';

    // Interest Payout Frequencies
    public const PAYOUT_AT_MATURITY = 'AT_MATURITY';

    public const PAYOUT_MONTHLY = 'MONTHLY';

    public const PAYOUT_QUARTERLY = 'QUARTERLY';

    public const PAYOUT_ANNUALLY = 'ANNUALLY';

    // Special Scheme Codes & Defaults
    public const CODE_KSP = 'KSP';

    public const KSP_DEFAULT_CONFIG = [
        'annual_deposit_amount' => 15000.00,
        'deposit_period' => 10,
        'insurance_coverage' => 300000.00,
        'return_start_year' => 11,
        'return_end_year' => 20,
        'annual_return_amount' => 45000.00,
        'return_frequency' => 'Yearly',
        'premature_withdrawal' => 'Not Allowed',
    ];

    protected $fillable = [
        'name',
        'code',
        'scheme_type_id',
        'scheme_type',
        'description',
        'min_amount',
        'max_amount',
        'tenure',
        'tenure_unit',
        'interest_rate',
        'is_double',
        'total_commission_percentage',
        'interest_payout_frequency',
        'senior_citizen_extra_rate',
        'lock_in_months',
        'allow_premature_withdrawal',
        'premature_penalty_percent',
        'allow_loan_against_deposit',
        'max_loan_percentage',
        'grace_period_days',
        'collection_frequency',
        'status',
        'promotion_target_percentage',
        'ksp_configuration',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (DepositScheme $scheme) {
            if (empty($scheme->scheme_type) && ! empty($scheme->name)) {
                $scheme->scheme_type = self::detectSchemeType($scheme->name);
            } elseif (empty($scheme->name) && ! empty($scheme->scheme_type)) {
                $types = self::getSchemeTypes();
                $scheme->name = $types[$scheme->scheme_type] ?? $scheme->scheme_type;
            }

            if (empty($scheme->scheme_type_id) && ! empty($scheme->scheme_type)) {
                $type = SchemeType::where('code', strtoupper((string) $scheme->scheme_type))->first();
                if ($type) {
                    $scheme->scheme_type_id = $type->id;
                }
            } elseif (! empty($scheme->scheme_type_id) && empty($scheme->scheme_type)) {
                $type = SchemeType::find($scheme->scheme_type_id);
                if ($type) {
                    $scheme->scheme_type = $type->code;
                }
            }

            if (empty($scheme->code) && ! empty($scheme->name)) {
                $scheme->code = self::generateSchemeCode(
                    (string) ($scheme->scheme_type ?? self::TYPE_FD),
                    (string) $scheme->name,
                    (int) ($scheme->tenure ?? 1),
                    (string) ($scheme->tenure_unit ?? self::TENURE_YEARS),
                    $scheme->id
                );
            } elseif (! empty($scheme->code)) {
                $scheme->code = strtoupper(trim((string) $scheme->code));
            }
        });
    }

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'is_double' => 'boolean',
        'total_commission_percentage' => 'decimal:2',
        'promotion_target_percentage' => 'decimal:2',
        'senior_citizen_extra_rate' => 'decimal:2',
        'premature_penalty_percent' => 'decimal:2',
        'max_loan_percentage' => 'decimal:2',
        'allow_premature_withdrawal' => 'boolean',
        'allow_loan_against_deposit' => 'boolean',
        'lock_in_months' => 'integer',
        'grace_period_days' => 'integer',
        'tenure' => 'integer',
        'ksp_configuration' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function schemeType()
    {
        return $this->belongsTo(SchemeType::class, 'scheme_type_id');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'deposit_scheme_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rankCommissions()
    {
        return $this->hasMany(DepositSchemeRankCommission::class, 'deposit_scheme_id');
    }

    public function eligibleRankCommissions()
    {
        return $this->hasMany(DepositSchemeRankCommission::class, 'deposit_scheme_id')->where('is_eligible', true);
    }

    public function commissionHistories()
    {
        return $this->hasMany(DepositSchemeCommissionHistory::class, 'deposit_scheme_id')->latest();
    }

    public function commissionRules()
    {
        return $this->hasMany(CommissionRule::class, 'deposit_scheme_id');
    }

    public function commissionLedgers()
    {
        return $this->hasMany(Commission::class, 'deposit_scheme_id');
    }

    public function interestRates()
    {
        return $this->hasMany(DepositSchemeInterestRate::class, 'deposit_scheme_id')
            ->orderBy('tenure');
    }

    public function activeInterestRates()
    {
        return $this->hasMany(DepositSchemeInterestRate::class, 'deposit_scheme_id')
            ->where('status', DepositSchemeInterestRate::STATUS_ACTIVE)
            ->orderBy('tenure');
    }

    public function mrpConfiguration()
    {
        return $this->hasOne(DepositSchemeMrpConfiguration::class, 'deposit_scheme_id');
    }

    public function mrpRates()
    {
        return $this->hasMany(DepositSchemeMrpRate::class, 'deposit_scheme_id')->orderBy('year');
    }

    public function isMrpEnabled(): bool
    {
        return (bool) ($this->mrpConfiguration?->is_enabled);
    }

    public function isKsp(): bool
    {
        return self::isKspCode($this->code, $this->name) || is_array($this->ksp_configuration);
    }

    /**
     * Determine whether a scheme code or name represents a Kissan Samrudhi Plan (KSP) scheme
     * (including year-wise variants such as KSP, KSP1Y, KSP2Y ... KSP10Y).
     */
    public static function isKspCode(?string $code, ?string $name = null): bool
    {
        $cleanCode = strtoupper(trim((string) $code));
        if ($cleanCode !== '' && preg_match('/^(?:FD|RD|MIS)?KSP(?:\d+[YMD]?|[-_][A-Z0-9]+)?$/', $cleanCode)) {
            return true;
        }

        if ($name !== null && $name !== '') {
            $cleanName = strtoupper(trim($name));
            if ((preg_match('/\bKSP\b/', $cleanName) || str_contains($cleanName, 'KISSAN SAMRUDHI')) && ! str_contains($cleanName, 'DHAN')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the KSP deposit period in years (1..10) from a KSP scheme code or name.
     */
    public static function extractKspDepositPeriod(?string $code, ?string $name = null, ?int $fallbackPeriod = null): int
    {
        $cleanCode = strtoupper(trim((string) $code));
        if ($cleanCode !== '' && preg_match('/KSP[-_]?(\d+)[YMD]?$/', $cleanCode, $m)) {
            $yr = (int) $m[1];
            if ($yr >= 1 && $yr <= 100) {
                return $yr;
            }
        }

        if ($name !== null && $name !== '' && preg_match('/(\d+)\s*(?:years?|yrs?|y)\b/i', $name, $m)) {
            $yr = (int) $m[1];
            if ($yr >= 1 && $yr <= 100) {
                return $yr;
            }
        }

        return ($fallbackPeriod !== null && $fallbackPeriod >= 1) ? $fallbackPeriod : 10;
    }

    /**
     * Build default KSP configuration for a specific deposit period in years.
     * Example:
     * - 1 Year deposit -> Return in 11th Year (return_start_year = 11, return_end_year = 11)
     * - 2 Year deposit -> Return in 11th & 12th Year (return_start_year = 11, return_end_year = 12)
     * - 10 Year deposit -> Return in 11th to 20th Year (return_start_year = 11, return_end_year = 20)
     *
     * @return array{
     *     annual_deposit_amount: float,
     *     deposit_period: int,
     *     insurance_coverage: float,
     *     return_start_year: int,
     *     return_end_year: int,
     *     annual_return_amount: float,
     *     return_frequency: string,
     *     premature_withdrawal: string
     * }
     */
    public static function getKspConfigForPeriod(int $periodYears = 10): array
    {
        $period = max(1, $periodYears);
        $startYear = 11;
        $endYear = 10 + $period;

        return [
            'annual_deposit_amount' => 15000.00,
            'deposit_period' => $period,
            'insurance_coverage' => 300000.00,
            'return_start_year' => $startYear,
            'return_end_year' => $endYear,
            'annual_return_amount' => 45000.00,
            'return_frequency' => 'Yearly',
            'premature_withdrawal' => 'Not Allowed',
        ];
    }

    /**
     * Get the resolved KSP configuration with defaults applied.
     *
     * @return array{
     *     annual_deposit_amount: float,
     *     deposit_period: int,
     *     insurance_coverage: float,
     *     return_start_year: int,
     *     return_end_year: int,
     *     annual_return_amount: float,
     *     return_frequency: string,
     *     premature_withdrawal: string
     * }
     */
    public function getKspConfig(): array
    {
        $stored = is_array($this->ksp_configuration) ? $this->ksp_configuration : [];
        $defaultPeriod = self::extractKspDepositPeriod($this->code, $this->name, $this->tenure ?: 10);
        $period = isset($stored['deposit_period']) ? (int) $stored['deposit_period'] : $defaultPeriod;
        $startYear = isset($stored['return_start_year']) ? (int) $stored['return_start_year'] : 11;
        $endYear = isset($stored['return_end_year']) ? (int) $stored['return_end_year'] : (10 + $period);

        return [
            'annual_deposit_amount' => isset($stored['annual_deposit_amount']) ? (float) $stored['annual_deposit_amount'] : 15000.00,
            'deposit_period' => $period,
            'insurance_coverage' => isset($stored['insurance_coverage']) ? (float) $stored['insurance_coverage'] : 300000.00,
            'return_start_year' => $startYear,
            'return_end_year' => $endYear,
            'annual_return_amount' => isset($stored['annual_return_amount']) ? (float) $stored['annual_return_amount'] : 45000.00,
            'return_frequency' => isset($stored['return_frequency']) && $stored['return_frequency'] !== '' ? (string) $stored['return_frequency'] : 'Yearly',
            'premature_withdrawal' => isset($stored['premature_withdrawal']) && $stored['premature_withdrawal'] !== '' ? (string) $stored['premature_withdrawal'] : 'Not Allowed',
        ];
    }

    /**
     * Format a positive year number with an English ordinal suffix (e.g. 11 -> "11th Year").
     */
    public static function formatOrdinalYear(int $year): string
    {
        $mod100 = $year % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            return $year.'th Year';
        }

        return match ($year % 10) {
            1 => $year.'st Year',
            2 => $year.'nd Year',
            3 => $year.'rd Year',
            default => $year.'th Year',
        };
    }

    /**
     * Format the KSP return payout window into a readable label.
     */
    public static function formatKspReturnWindow(int $startYear, int $endYear): string
    {
        if ($endYear <= $startYear) {
            return self::formatOrdinalYear($startYear);
        }

        if ($endYear === $startYear + 1) {
            return self::formatOrdinalYear($startYear).' & '.self::formatOrdinalYear($endYear);
        }

        return self::formatOrdinalYear($startYear).' to '.self::formatOrdinalYear($endYear);
    }

    /**
     * Calculate KSP deposit and return projections for a given annual deposit amount.
     *
     * @return array{
     *     annual_deposit_amount: float,
     *     configured_annual_deposit: float,
     *     deposit_period: int,
     *     total_deposited: float,
     *     insurance_coverage: float,
     *     return_start_year: int,
     *     return_end_year: int,
     *     return_years_count: int,
     *     annual_return_amount: float,
     *     total_return: float,
     *     net_gain: float,
     *     return_frequency: string,
     *     premature_withdrawal: string,
     *     window_label: string
     * }
     */
    public function calculateKspProjection(float $annualDepositAmount): array
    {
        $config = $this->getKspConfig();
        $baseAnnualDeposit = max(0.0, (float) $config['annual_deposit_amount']);
        $depositPeriod = max(1, (int) $config['deposit_period']);
        $insuranceCoverage = max(0.0, (float) $config['insurance_coverage']);
        $returnStartYear = max(1, (int) $config['return_start_year']);
        $returnEndYear = max($returnStartYear, (int) $config['return_end_year']);
        $returnYearsCount = max(1, $returnEndYear - $returnStartYear + 1);
        $baseAnnualReturn = max(0.0, (float) $config['annual_return_amount']);

        $effectiveAnnualDeposit = max(0.0, $annualDepositAmount);
        $scaleFactor = $baseAnnualDeposit > 0 ? ($effectiveAnnualDeposit / $baseAnnualDeposit) : 1.0;

        $scaledAnnualReturn = $effectiveAnnualDeposit > 0
            ? round($baseAnnualReturn * $scaleFactor, 2)
            : 0.00;
        $totalDeposited = round($effectiveAnnualDeposit * $depositPeriod, 2);
        $totalReturn = round($scaledAnnualReturn * $returnYearsCount, 2);
        $netGain = max(0.00, round($totalReturn - $totalDeposited, 2));

        return [
            'annual_deposit_amount' => round($effectiveAnnualDeposit, 2),
            'configured_annual_deposit' => round($baseAnnualDeposit, 2),
            'deposit_period' => $depositPeriod,
            'total_deposited' => $totalDeposited,
            'insurance_coverage' => round($insuranceCoverage, 2),
            'return_start_year' => $returnStartYear,
            'return_end_year' => $returnEndYear,
            'return_years_count' => $returnYearsCount,
            'annual_return_amount' => $scaledAnnualReturn,
            'total_return' => $totalReturn,
            'net_gain' => $netGain,
            'return_frequency' => (string) $config['return_frequency'],
            'premature_withdrawal' => (string) $config['premature_withdrawal'],
            'window_label' => self::formatKspReturnWindow($returnStartYear, $returnEndYear),
        ];
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class)
            ->latest();
    }

    public function isFixedDeposit(): bool
    {
        return strtoupper((string) $this->scheme_type) === self::TYPE_FD;
    }

    public function getTenureUnitDisplayAttribute(): string
    {
        return self::getTenureUnits()[$this->tenure_unit] ?? ucfirst(strtolower((string) $this->tenure_unit));
    }

    /**
     * Get scheme tenure duration converted to completed whole years.
     * Sub-1-year durations return 0 years.
     */
    public function getTenureInYears(): int
    {
        $unit = strtoupper(trim((string) ($this->tenure_unit ?? 'YEARS')));
        $tenure = (int) ($this->tenure ?? 0);

        return match ($unit) {
            'DAYS', 'DAY' => (int) floor($tenure / 365),
            'WEEKS', 'WEEK' => (int) floor(($tenure * 7) / 365),
            'MONTHS', 'MONTH' => (int) floor($tenure / 12),
            default => max(0, $tenure), // YEARS
        };
    }

    /**
     * Get the default or rule-based promotion target percentage credit for this scheme.
     *
     * Initial Configurable Rules:
     * - FD & RD: 1Y = 20%, 2Y = 30%, 3Y = 40%, 5Y = 80%, 6Y = 80%
     * - RD: 7Y = 90%
     * - DD: 1Y = 20%, 2Y = 40%, 3Y = 50%
     */
    public function getPromotionTargetPercentage(?PromotionRule $rule = null): float
    {
        if ($rule && ! $rule->apply_tenure_targets) {
            return 100.00;
        }

        if ($this->promotion_target_percentage !== null) {
            return (float) $this->promotion_target_percentage;
        }

        $tenureYears = $this->getTenureInYears();
        $schemeType = strtoupper((string) ($this->scheme_type ?: 'FD'));

        if ($rule) {
            return $rule->getDepositPromotionPercentage($schemeType, $tenureYears);
        }

        if ($tenureYears < 1) {
            return 0.00;
        }

        $schemeKey = in_array($schemeType, ['DD', 'DAILY', 'DAILY DEPOSIT'], true)
            ? 'dd'
            : (in_array($schemeType, ['RD', 'RECURRING', 'RECURRING DEPOSIT'], true) ? 'rd' : 'fd');

        $map = PromotionRule::DEFAULT_TENURE_TARGETS[$schemeKey] ?? [];

        return isset($map[(string) $tenureYears]) ? (float) $map[(string) $tenureYears] : 0.00;
    }

    /**
     * Convert scheme duration to whole months for duration matching.
     */
    public function getDurationInMonths(): float
    {
        $unit = strtoupper(trim((string) ($this->tenure_unit ?? self::TENURE_MONTHS)));
        $tenure = (float) ($this->tenure ?? 1);

        return match ($unit) {
            'DAYS', 'DAY' => $tenure / 30.4167,
            'WEEKS', 'WEEK' => ($tenure * 7) / 30.4167,
            'YEARS', 'YEAR' => $tenure * 12,
            default => $tenure,
        };
    }

    public function recalculateTotalCommission(): float
    {
        $total = (float) $this->rankCommissions()->where('is_eligible', true)->sum('commission_percentage');
        $this->update(['total_commission_percentage' => $total]);

        return $total;
    }

    public function hasConfiguredTenureRates(): bool
    {
        return $this->activeInterestRates()->exists();
    }

    /**
     * Find duration-based interest rate record for a given tenure and unit on a specific date.
     */
    public function findInterestRateRecord(int $tenure, ?string $tenureUnit = null, CarbonInterface|string|null $date = null): ?DepositSchemeInterestRate
    {
        $unit = strtoupper(trim((string) ($tenureUnit ?: ($this->tenure_unit ?? self::TENURE_YEARS))));
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $rates = DepositSchemeInterestRate::where('deposit_scheme_id', $this->id)
            ->where('status', DepositSchemeInterestRate::STATUS_ACTIVE)
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $targetDate);
            })
            ->get();

        if ($rates->isEmpty()) {
            return null;
        }

        // 1. Direct match on tenure and unit
        $exact = $rates->first(function (DepositSchemeInterestRate $r) use ($tenure, $unit) {
            return (int) $r->tenure === $tenure && strtoupper(trim((string) $r->tenure_unit)) === $unit;
        });

        if ($exact) {
            return $exact;
        }

        // 2. Normalized duration in months match
        $targetMonths = match ($unit) {
            'DAYS', 'DAY' => (float) $tenure / 30.4167,
            'WEEKS', 'WEEK' => ((float) $tenure * 7) / 30.4167,
            'YEARS', 'YEAR', 'Y' => (float) $tenure * 12,
            default => (float) $tenure,
        };

        return $rates->first(function (DepositSchemeInterestRate $r) use ($targetMonths) {
            return abs($r->getDurationInMonths() - $targetMonths) < 0.2;
        });
    }

    /**
     * Get available active interest rate configurations for this scheme on a given date.
     */
    public function getAvailableTenures(CarbonInterface|string|null $date = null): Collection
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $rates = DepositSchemeInterestRate::where('deposit_scheme_id', $this->id)
            ->where('status', DepositSchemeInterestRate::STATUS_ACTIVE)
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $targetDate);
            })
            ->orderBy('tenure')
            ->get();

        if ($rates->isNotEmpty()) {
            return $rates;
        }

        // If no multi-year tiers are configured, return single default tier based on scheme
        return collect([
            new DepositSchemeInterestRate([
                'deposit_scheme_id' => $this->id,
                'tenure' => (int) ($this->tenure ?? 12),
                'tenure_unit' => strtoupper(trim((string) ($this->tenure_unit ?? self::TENURE_MONTHS))),
                'interest_rate' => (float) ($this->interest_rate ?? 0.00),
                'senior_citizen_extra_rate' => (float) ($this->senior_citizen_extra_rate ?? 0.00),
                'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
                'effective_from' => '2020-01-01',
            ]),
        ]);
    }

    /**
     * Get annual interest rate (% p.a.) applicable for a given tenure duration.
     * Automatically applies the duration-specific rate if configured, or falls back to standard scheme interest rate.
     */
    public function getInterestRateForTenure(int $tenure, ?string $tenureUnit = null): float
    {
        $record = $this->findInterestRateRecord($tenure, $tenureUnit);

        if ($record && $record->interest_rate !== null) {
            return (float) $record->interest_rate;
        }

        return (float) ($this->interest_rate ?? 0.00);
    }

    /**
     * Get senior citizen extra interest rate (% p.a.) applicable for a given tenure duration.
     */
    public function getSeniorCitizenExtraRateForTenure(int $tenure, ?string $tenureUnit = null): float
    {
        $record = $this->findInterestRateRecord($tenure, $tenureUnit);

        if ($record && (float) ($record->senior_citizen_extra_rate ?? 0.00) > 0) {
            return (float) $record->senior_citizen_extra_rate;
        }

        return (float) ($this->senior_citizen_extra_rate ?? 0.00);
    }

    /**
     * Get effective annual interest rate, optionally with senior citizen extra percentage.
     */
    public function getApplicableInterestRate(int $tenure, ?string $tenureUnit = null, bool $isSeniorCitizen = false): float
    {
        if ($this->isDouble()) {
            return 0.00;
        }

        $rate = $this->getInterestRateForTenure($tenure, $tenureUnit);

        if ($isSeniorCitizen) {
            $rate += $this->getSeniorCitizenExtraRateForTenure($tenure, $tenureUnit);
        }

        return round($rate, 2);
    }

    /**
     * Sync duration-based interest rates from an array of rules.
     *
     * @param  array<int, array{tenure: int|string, tenure_unit?: string, interest_rate: float|string, senior_citizen_extra_rate?: float|string|null}>  $ratesData
     */
    public function syncInterestRates(array $ratesData, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();
        if ($userId && ! User::where('id', $userId)->exists()) {
            $userId = null;
        }
        $processedIds = [];

        foreach ($ratesData as $item) {
            if (! isset($item['tenure']) || ! isset($item['interest_rate'])) {
                continue;
            }

            $tenure = (int) $item['tenure'];
            if ($tenure <= 0) {
                continue;
            }

            $unit = strtoupper(trim((string) ($item['tenure_unit'] ?? $this->tenure_unit ?? self::TENURE_YEARS)));
            $rate = (float) $item['interest_rate'];
            $seniorExtra = isset($item['senior_citizen_extra_rate']) && $item['senior_citizen_extra_rate'] !== ''
                ? (float) $item['senior_citizen_extra_rate']
                : 0.00;

            $record = $this->interestRates()
                ->where('tenure', $tenure)
                ->where('tenure_unit', $unit)
                ->first();

            if ($record) {
                $record->update([
                    'interest_rate' => $rate,
                    'senior_citizen_extra_rate' => $seniorExtra,
                    'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
                ]);
            } else {
                $record = $this->interestRates()->create([
                    'tenure' => $tenure,
                    'tenure_unit' => $unit,
                    'interest_rate' => $rate,
                    'senior_citizen_extra_rate' => $seniorExtra,
                    'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
                    'created_by' => $userId,
                ]);
            }

            $processedIds[] = $record->id;
        }

        // Deactivate or delete records that were removed from the configuration
        $this->interestRates()
            ->whereNotIn('id', $processedIds)
            ->delete();
    }

    /**
     * Synchronize the primary interest rate configuration with the deposit scheme's base rate.
     */
    public function syncPrimaryInterestRate(?int $userId = null): ?DepositSchemeInterestRate
    {
        $userId = $userId ?? auth()->id();
        if ($userId && ! User::where('id', $userId)->exists()) {
            $userId = null;
        }
        $tenure = (int) ($this->tenure ?? 1);
        $unit = strtoupper(trim((string) ($this->tenure_unit ?? self::TENURE_YEARS)));
        $rate = (float) ($this->interest_rate ?? 0.00);
        $seniorExtra = (float) ($this->senior_citizen_extra_rate ?? 0.00);
        $status = $this->isActive() ? DepositSchemeInterestRate::STATUS_ACTIVE : DepositSchemeInterestRate::STATUS_INACTIVE;
        $effectiveDate = $this->created_at ? $this->created_at->toDateString() : '2020-01-01';

        // 1. Try finding record matching exact tenure and unit
        $matching = $this->interestRates()
            ->where('tenure', $tenure)
            ->where('tenure_unit', $unit)
            ->first();

        // 2. If no exact match, try finding record matching equivalent duration in months
        if (! $matching) {
            $targetMonths = $this->getDurationInMonths();
            $matching = $this->interestRates->first(function (DepositSchemeInterestRate $r) use ($targetMonths) {
                return abs($r->getDurationInMonths() - $targetMonths) < 0.2;
            });
        }

        if ($matching) {
            $matching->update([
                'tenure' => $tenure,
                'tenure_unit' => $unit,
                'interest_rate' => $rate,
                'senior_citizen_extra_rate' => $seniorExtra,
                'min_amount' => $this->min_amount,
                'max_amount' => $this->max_amount,
                'status' => $status,
                'updated_by' => $userId,
            ]);

            return $matching;
        }

        return $this->interestRates()->create([
            'tenure' => $tenure,
            'tenure_unit' => $unit,
            'interest_rate' => $rate,
            'senior_citizen_extra_rate' => $seniorExtra,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'status' => $status,
            'effective_from' => $effectiveDate,
            'created_by' => $userId,
        ]);
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_INACTIVE;
    }

    public function getStatusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-active' : 'badge-inactive';
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_ACTIVE)
                ->orWhere('status', 'active');
        });
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('scheme_type', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('scheme_type', $type);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public static function detectSchemeType(?string $name): string
    {
        if (empty($name)) {
            return self::TYPE_FD;
        }

        $upper = strtoupper(trim($name));

        if (str_contains($upper, 'DOUBLE') || str_contains($upper, 'DOUBL') || preg_match('/\bDDS\b/', $upper)) {
            return self::TYPE_DOUBLE;
        }

        if (str_contains($upper, 'RECUR') || preg_match('/\bRD\b/', $upper)) {
            return self::TYPE_RD;
        }

        if (str_contains($upper, 'DAIL') || str_contains($upper, 'PIGMY') || preg_match('/\bDD\b/', $upper)) {
            return self::TYPE_DD;
        }

        if (str_contains($upper, 'MONTHLY INCOME') || preg_match('/\bMIS\b/', $upper)) {
            return 'MIS';
        }

        return self::TYPE_FD;
    }

    public static function getSchemeTypes(): array
    {
        return [
            self::TYPE_FD => 'Fixed Deposit (FD)',
            self::TYPE_DOUBLE => 'Double / Doubling Scheme',
            self::TYPE_RD => 'Recurring Deposit (RD)',
            self::TYPE_DD => 'Daily Deposit (DD)',
            'MIS' => 'Monthly Income Scheme (MIS)',
            self::TYPE_OTHER => 'Other Deposit Scheme',
        ];
    }

    public function isDouble(): bool
    {
        return (bool) ($this->is_double || $this->scheme_type === self::TYPE_DOUBLE);
    }

    /**
     * Determine whether this scheme is a Monthly Return Plan (MRP / MIS).
     */
    public function isMrp(): bool
    {
        if ($this->isMrpEnabled()) {
            return true;
        }

        if (strtoupper((string) $this->scheme_type) === 'MIS') {
            return true;
        }

        $upperName = strtoupper((string) $this->name);

        return str_contains($upperName, 'MRP') || str_contains($upperName, 'MONTHLY RETURN');
    }

    /**
     * Get the effective collection frequency for this scheme.
     */
    public function getEffectiveCollectionFrequency(): string
    {
        if (! empty($this->collection_frequency)) {
            return strtoupper((string) $this->collection_frequency);
        }

        return match (strtoupper((string) $this->scheme_type)) {
            self::TYPE_RD => self::FREQ_MONTHLY,
            self::TYPE_DD => self::FREQ_DAILY,
            default => self::FREQ_ONE_TIME,
        };
    }

    /**
     * Get the effective interest payout frequency for this scheme.
     */
    public function getEffectivePayoutFrequency(): string
    {
        if (! empty($this->interest_payout_frequency)) {
            return strtoupper((string) $this->interest_payout_frequency);
        }

        return $this->isMrp() ? self::PAYOUT_MONTHLY : self::PAYOUT_AT_MATURITY;
    }

    /**
     * Get the human-readable label for the collection frequency.
     */
    public function getCollectionFrequencyLabel(): string
    {
        $freq = $this->getEffectiveCollectionFrequency();

        return self::getCollectionFrequencies()[$freq] ?? str_replace('_', ' ', $freq);
    }

    /**
     * Get the human-readable label for the interest payout frequency.
     */
    public function getInterestPayoutFrequencyLabel(): string
    {
        $payout = $this->getEffectivePayoutFrequency();

        return self::getInterestPayoutFrequencies()[$payout] ?? str_replace('_', ' ', $payout);
    }

    public static function getTenureUnits(): array
    {
        return [
            self::TENURE_DAYS => 'Days',
            self::TENURE_MONTHS => 'Months',
            self::TENURE_YEARS => 'Years',
        ];
    }

    public static function getCollectionFrequencies(): array
    {
        return [
            self::FREQ_ONE_TIME => 'One-Time / Lump Sum',
            self::FREQ_DAILY => 'Daily',
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_MONTHLY => 'Monthly',
            self::FREQ_QUARTERLY => 'Quarterly',
        ];
    }

    public static function getInterestPayoutFrequencies(): array
    {
        return [
            self::PAYOUT_AT_MATURITY => 'At Maturity (Cumulative / Compounded)',
            self::PAYOUT_MONTHLY => 'Monthly (Monthly Income Scheme - MIS)',
            self::PAYOUT_QUARTERLY => 'Quarterly (Quarterly Interest Payout)',
            self::PAYOUT_ANNUALLY => 'Annually (Annual Payout)',
        ];
    }

    /**
     * Extract a standardized uppercase scheme abbreviation from scheme name.
     * Examples:
     * - "Kissan Dhan Samrudhi Plan" -> "KDSP"
     * - "Savings" -> "SV"
     * - "Monthly Income" -> "MI"
     */
    public static function extractAbbreviation(string $name, string $depositType = ''): string
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            return '';
        }

        // 1. Remove parenthesized tenure mentions, e.g. (12 Months), (5 Years), (60 Mos)
        $cleanName = (string) preg_replace('/\(\s*\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\s*\)/i', '', $cleanName);

        // 2. Remove standalone tenure mentions, e.g. 12 Months, 5 Years, 60M
        $cleanName = (string) preg_replace('/\b\d+\s*(?:years?|yrs?|months?|mos?|days?|y|m|d)\b/i', '', $cleanName);

        // 3. Remove leading deposit type if matching current type
        $depositTypeUpper = strtoupper(trim($depositType));
        if ($depositTypeUpper === self::TYPE_FD) {
            $cleanName = (string) preg_replace('/^(fixed\s*deposit|fd)\b\s*/i', '', $cleanName);
        } elseif ($depositTypeUpper === self::TYPE_RD) {
            $cleanName = (string) preg_replace('/^(recurring\s*deposit|rd)\b\s*/i', '', $cleanName);
        } elseif ($depositTypeUpper === self::TYPE_DD) {
            $cleanName = (string) preg_replace('/^(daily\s*deposit|dd|pigmy)\b\s*/i', '', $cleanName);
        } elseif ($depositTypeUpper === 'MIS') {
            $cleanName = (string) preg_replace('/^(monthly\s*income\s*scheme|mis)\b\s*/i', '', $cleanName);
        } else {
            $cleanName = (string) preg_replace('/^(fixed\s*deposit|recurring\s*deposit|daily\s*deposit|monthly\s*income\s*scheme)\b\s*/i', '', $cleanName);
        }

        // Keep alphanumeric characters and spaces
        $cleanName = trim((string) preg_replace('/[^a-zA-Z0-9\s]/', ' ', $cleanName));
        if ($cleanName === '') {
            return '';
        }

        $words = array_values(array_filter(preg_split('/\s+/', $cleanName)));
        if (empty($words)) {
            return '';
        }

        // If multiple words (> 2), remove common filler stop words if other words remain
        if (count($words) > 2) {
            $stopWords = ['AND', 'OF', 'FOR', 'THE', 'IN', 'ON', 'AT', 'TO', 'A', 'AN', 'WITH'];
            $filtered = array_values(array_filter($words, fn ($w) => ! in_array(strtoupper($w), $stopWords, true)));
            if (! empty($filtered)) {
                $words = $filtered;
            }
        }

        // Multi-word scheme name: take first letter of each word
        if (count($words) >= 2) {
            $abbr = '';
            foreach ($words as $word) {
                $abbr .= strtoupper(substr($word, 0, 1));
            }

            return $abbr;
        }

        // Single-word scheme name (e.g. "Savings" -> "SV")
        $single = $words[0];
        if (strlen($single) <= 2) {
            return strtoupper($single);
        }

        // If already uppercase acronym (e.g. "KDSP", "VIP")
        if (ctype_upper($single) && strlen($single) <= 4) {
            return $single;
        }

        $first = strtoupper(substr($single, 0, 1));
        $rest = substr($single, 1);

        // Take next consonant in the word (e.g. "Savings" -> "S" + "V" = "SV")
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]/i', $rest, $matches)) {
            return $first.strtoupper($matches[0]);
        }

        // Fallback: second character
        return strtoupper(substr($single, 0, 2));
    }

    /**
     * Auto-generate a clean, unique scheme code in the format:
     * DepositType + SchemeAbbreviation + Tenure (e.g. FDKDSP5Y, RDSV12M, FDMI6M)
     */
    public static function generateSchemeCode(
        string $type,
        string $name = '',
        int|string $tenure = 1,
        string $tenureUnit = 'YEARS',
        ?int $ignoreId = null,
        int $maxLength = 30
    ): string {
        $typeUpper = strtoupper(trim($type ?: self::TYPE_FD));
        $tenureInt = max(1, (int) $tenure);
        $unitUpper = strtoupper(trim($tenureUnit ?: self::TENURE_YEARS));

        $unitSuffix = match ($unitUpper) {
            self::TENURE_YEARS, 'YEAR', 'YEARS', 'Y' => 'Y',
            self::TENURE_DAYS, 'DAY', 'DAYS', 'D' => 'D',
            default => 'M',
        };

        $tenurePart = $tenureInt.$unitSuffix;
        $abbr = self::extractAbbreviation($name, $typeUpper);

        $base = strtoupper($typeUpper.$abbr.$tenurePart);
        if (strlen($base) > $maxLength) {
            $base = substr($base, 0, $maxLength);
        }

        $candidate = $base;
        $counter = 1;

        while (static::where('code', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $suffix = '-'.$counter;
            $prefixLen = max(1, $maxLength - strlen($suffix));
            $candidate = substr($base, 0, $prefixLen).$suffix;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Synchronize Monthly Return Plan (MRP) configuration and dynamic year-wise rates.
     *
     * @param  array{is_enabled?: bool, contribution_amount?: float|string, min_tenure?: int, max_tenure?: int, tenure_unit?: string, general_rate?: float|string|null, senior_rate?: float|string|null, calculation_method?: string, notes?: string|null}  $mrpData
     * @param  array<int, array{year: int|string, general_rate: float|string, senior_rate: float|string}>  $ratesData
     */
    public function syncMrpConfiguration(array $mrpData, array $ratesData = []): ?DepositSchemeMrpConfiguration
    {
        $isEnabled = ! empty($mrpData['is_enabled']);
        $contribution = (float) ($mrpData['contribution_amount'] ?? 100000.00);
        $minTenure = (int) ($mrpData['min_tenure'] ?? 1);
        $maxTenure = (int) ($mrpData['max_tenure'] ?? 5);
        $tenureUnit = strtoupper((string) ($mrpData['tenure_unit'] ?? 'YEARS'));
        $calcMethod = (string) ($mrpData['calculation_method'] ?? DepositSchemeMrpConfiguration::METHOD_STANDARD);
        $generalRate = isset($mrpData['general_rate']) && $mrpData['general_rate'] !== '' ? (float) $mrpData['general_rate'] : null;
        $seniorRate = isset($mrpData['senior_rate']) && $mrpData['senior_rate'] !== '' ? (float) $mrpData['senior_rate'] : null;
        $notes = $mrpData['notes'] ?? null;

        $config = $this->mrpConfiguration()->updateOrCreate(
            ['deposit_scheme_id' => $this->id],
            [
                'is_enabled' => $isEnabled,
                'contribution_amount' => $contribution,
                'min_tenure' => $minTenure,
                'max_tenure' => $maxTenure,
                'tenure_unit' => $tenureUnit,
                'general_rate' => $generalRate,
                'senior_rate' => $seniorRate,
                'calculation_method' => $calcMethod,
                'notes' => $notes,
            ]
        );

        $processedYears = [];

        foreach ($ratesData as $rateItem) {
            if (! isset($rateItem['year']) || $rateItem['year'] === '') {
                continue;
            }

            $year = (int) $rateItem['year'];
            if ($year <= 0 || in_array($year, $processedYears, true)) {
                continue;
            }

            $gRate = (float) ($rateItem['general_rate'] ?? 0.00);
            $sRate = (float) ($rateItem['senior_rate'] ?? 0.00);

            $gReturn = DepositSchemeMrpConfiguration::calculateMonthlyReturn($contribution, $gRate, $calcMethod);
            $sReturn = DepositSchemeMrpConfiguration::calculateMonthlyReturn($contribution, $sRate, $calcMethod);

            $this->mrpRates()->updateOrCreate(
                [
                    'deposit_scheme_id' => $this->id,
                    'year' => $year,
                ],
                [
                    'mrp_configuration_id' => $config->id,
                    'general_rate' => $gRate,
                    'general_monthly_return' => $gReturn,
                    'senior_rate' => $sRate,
                    'senior_monthly_return' => $sReturn,
                ]
            );

            $processedYears[] = $year;
        }

        // Delete any years that were removed from the configuration
        if (! empty($processedYears)) {
            $this->mrpRates()->whereNotIn('year', $processedYears)->delete();
        } elseif (empty($ratesData) && $isEnabled) {
            $this->mrpRates()->delete();
        }

        return $config;
    }
}
