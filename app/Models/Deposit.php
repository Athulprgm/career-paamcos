<?php

namespace App\Models;

use App\Services\CareerPerformanceService;
use App\Services\NumberingService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Deposit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'deposits';

    // Deposit Status Constants
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_MATURED = 'MATURED';

    public const STATUS_CLOSED = 'CLOSED';

    public const STATUS_REJECTED = 'REJECTED';

    // Payment Mode Constants
    public const PAYMENT_MODE_CASH = 'CASH';

    public const PAYMENT_MODE_BANK_TRANSFER = 'BANK_TRANSFER';

    public const PAYMENT_MODE_CHEQUE = 'CHEQUE';

    public const PAYMENT_MODE_ONLINE_UPI = 'ONLINE_UPI';

    public const PAYMENT_MODE_DD = 'DEMAND_DRAFT';

    // Auto Renewal Constants
    public const AUTO_RENEWAL_NONE = 'NONE';

    public const AUTO_RENEWAL_PRINCIPAL = 'PRINCIPAL_ONLY';

    public const AUTO_RENEWAL_TOTAL = 'PRINCIPAL_AND_INTEREST';

    // Payout Frequency Constants
    public const PAYOUT_AT_MATURITY = 'AT_MATURITY';

    public const PAYOUT_MONTHLY = 'MONTHLY';

    public const PAYOUT_QUARTERLY = 'QUARTERLY';

    public const PAYOUT_ANNUALLY = 'ANNUALLY';

    protected $fillable = [
        'deposit_number',
        'customer_id',
        'lead_id',
        'branch_id',
        'agent_id',
        'deposit_scheme_id',
        'deposit_interest_rate_id',
        'scheme_id',
        'deposit_type',
        'amount',
        'start_date',
        'tenure',
        'tenure_unit',
        'collection_frequency',
        'interest_rate',
        'is_double',
        'is_senior_citizen',
        'maturity_date',
        'maturity_amount',
        'status',
        'rejection_reason',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'activated_at',
        'matured_at',
        'closed_at',
        'closed_by',
        'deposit_date',
        'payment_mode',
        'payment_reference',
        'nominee_name',
        'nominee_relation',
        'nominee_phone',
        'nominee_age',
        'auto_renewal',
        'payout_frequency',
        'member_code',
        'verified_by',
        'verified_at',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::creating(function (Deposit $deposit) {
            if (empty($deposit->attributes['deposit_number'])) {
                $deposit->attributes['deposit_number'] = static::generateDepositNumber();
            }
            if (empty($deposit->attributes['deposit_id'])) {
                $deposit->attributes['deposit_id'] = $deposit->attributes['deposit_number'];
            }
            if (empty($deposit->deposit_date)) {
                $deposit->deposit_date = ! empty($deposit->start_date) ? $deposit->start_date : now()->toDateString();
            }
            if (empty($deposit->deposit_type)) {
                $deposit->deposit_type = $deposit->scheme?->scheme_type
                    ?? ($deposit->deposit_scheme_id ? DepositScheme::find($deposit->deposit_scheme_id)?->scheme_type : null)
                    ?? DepositScheme::TYPE_FD;
            }
            if (empty($deposit->payment_mode)) {
                $deposit->payment_mode = self::PAYMENT_MODE_CASH;
            }
            if (empty($deposit->auto_renewal)) {
                $deposit->auto_renewal = self::AUTO_RENEWAL_NONE;
            }
            if (empty($deposit->payout_frequency)) {
                $schemeModel = $deposit->scheme
                    ?? ($deposit->deposit_scheme_id ? DepositScheme::find($deposit->deposit_scheme_id) : null);
                $deposit->payout_frequency = $schemeModel?->getEffectivePayoutFrequency() ?? self::PAYOUT_AT_MATURITY;
            }
            if (empty($deposit->maturity_amount) && ! empty($deposit->amount)) {
                $schemeModel = $deposit->scheme
                    ?? ($deposit->deposit_scheme_id ? DepositScheme::find($deposit->deposit_scheme_id) : null);

                if ($schemeModel && $schemeModel->isKsp()) {
                    $deposit->maturity_amount = $schemeModel->calculateKspProjection((float) $deposit->amount)['total_return'];
                } else {
                    $deposit->maturity_amount = static::calculateMaturityAmount(
                        (float) $deposit->amount,
                        (float) ($deposit->interest_rate ?? $schemeModel?->interest_rate ?? 0),
                        (int) ($deposit->tenure ?? $schemeModel?->tenure ?? 12),
                        (string) ($deposit->tenure_unit ?? $schemeModel?->tenure_unit ?? DepositScheme::TENURE_MONTHS),
                        (string) ($deposit->deposit_type ?? $schemeModel?->scheme_type ?? DepositScheme::TYPE_FD),
                        (string) ($deposit->collection_frequency ?? $schemeModel?->collection_frequency ?? DepositScheme::FREQ_ONE_TIME),
                        isDouble: (bool) ($deposit->is_double ?? $schemeModel?->is_double ?? false),
                        payoutFrequency: (string) ($deposit->payout_frequency ?? self::PAYOUT_AT_MATURITY)
                    );
                }
            }
            if (empty($deposit->member_code)) {
                if ($deposit->customer) {
                    $deposit->member_code = $deposit->customer->customer_number;
                } elseif ($deposit->customer_id) {
                    $cust = Customer::find($deposit->customer_id);
                    $deposit->member_code = $cust?->customer_number ?? ('CUST'.$deposit->customer_id);
                }
            }
            if (empty($deposit->created_by) && auth()->check()) {
                $deposit->created_by = auth()->id();
            }
        });

        static::created(function (Deposit $deposit): void {
            if ($deposit->lead_id) {
                Lead::where('id', $deposit->lead_id)->update([
                    'status' => Lead::STATUS_CONVERTED,
                    'converted_customer_id' => $deposit->customer_id,
                    'converted_at' => now(),
                ]);
            }
        });

        static::updated(function (Deposit $deposit): void {
            if ($deposit->lead_id && in_array($deposit->status, [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED], true)) {
                Lead::where('id', $deposit->lead_id)->update([
                    'status' => Lead::STATUS_CONVERTED,
                    'converted_customer_id' => $deposit->customer_id,
                    'converted_at' => now(),
                ]);
            }
        });

        static::saved(function (Deposit $deposit): void {
            if ($deposit->agent_id && in_array($deposit->status, [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED], true)) {
                DB::afterCommit(function () use ($deposit) {
                    try {
                        if ($deposit->agent) {
                            app(CareerPerformanceService::class)->checkAndAutoPromote($deposit->agent);
                        }
                    } catch (\Throwable $e) {
                        // Non-blocking
                    }
                });
            }
        });
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'is_double' => 'boolean',
        'is_senior_citizen' => 'boolean',
        'maturity_amount' => 'decimal:2',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'deposit_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'activated_at' => 'datetime',
        'matured_at' => 'datetime',
        'closed_at' => 'datetime',
        'verified_at' => 'datetime',
        'tenure' => 'integer',
        'nominee_age' => 'integer',
    ];

    // ─── Legacy compatibility accessors ──────────────────────────────────────

    public function getDepositIdAttribute(): ?string
    {
        return $this->attributes['deposit_number'] ?? $this->attributes['deposit_id'] ?? null;
    }

    public function setDepositIdAttribute($value): void
    {
        $this->attributes['deposit_id'] = $value;
        if (empty($this->attributes['deposit_number'])) {
            $this->attributes['deposit_number'] = $value;
        }
    }

    public function setDepositNumberAttribute($value): void
    {
        $this->attributes['deposit_number'] = $value;
        if (empty($this->attributes['deposit_id'])) {
            $this->attributes['deposit_id'] = $value;
        }
    }

    public function getSchemeIdAttribute(): ?int
    {
        return $this->attributes['deposit_scheme_id'] ?? null;
    }

    public function setSchemeIdAttribute($value): void
    {
        $this->attributes['deposit_scheme_id'] = $value;
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function scheme()
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    public function interestRateConfiguration()
    {
        return $this->belongsTo(DepositSchemeInterestRate::class, 'deposit_interest_rate_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return ! empty($this->verified_at);
    }

    public function collections()
    {
        return $this->hasMany(DepositCollection::class, 'deposit_id')->orderBy('installment_number');
    }

    public function collectionPayments()
    {
        return $this->hasMany(CollectionPayment::class, 'deposit_id')->latest();
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'deposit_id')->latest();
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class)
            ->latest();
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_APPROVED;
    }

    public function isActive(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_ACTIVE;
    }

    public function isMatured(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_MATURED;
    }

    public function isClosed(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_CLOSED;
    }

    public function isRejected(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_REJECTED;
    }

    public function isDouble(): bool
    {
        return (bool) ($this->is_double || $this->scheme?->isDouble() || $this->deposit_type === DepositScheme::TYPE_DOUBLE);
    }

    public function isKsp(): bool
    {
        return (bool) ($this->scheme?->isKsp());
    }

    /**
     * Get resolved KSP projection and schedule details for this deposit account.
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
     *     window_label: string,
     *     deposit_end_date: ?Carbon,
     *     return_start_date: ?Carbon,
     *     return_end_date: ?Carbon
     * }|null
     */
    public function getKspDetails(): ?array
    {
        if (! $this->isKsp() || ! $this->scheme) {
            return null;
        }

        $projection = $this->scheme->calculateKspProjection((float) ($this->amount ?? 0));
        $startDate = $this->start_date ? Carbon::parse($this->start_date) : null;

        return array_merge($projection, [
            'deposit_end_date' => $startDate?->copy()->addYears($projection['deposit_period']),
            'return_start_date' => $startDate?->copy()->addYears($projection['return_start_year']),
            'return_end_date' => $startDate?->copy()->addYears($projection['return_end_year']),
        ]);
    }

    public function isSeniorCitizen(): bool
    {
        return (bool) ($this->is_senior_citizen || (! empty($this->customer?->date_of_birth) && Carbon::parse($this->customer->date_of_birth)->age >= 60));
    }

    // ─── Allowed Transition Checks ───────────────────────────────────────────

    public function canBeApproved(): bool
    {
        return $this->isPendingApproval();
    }

    public function canBeRejected(): bool
    {
        return $this->isPendingApproval();
    }

    public function canBeActivated(): bool
    {
        return $this->isApproved();
    }

    public function canBeMatured(): bool
    {
        return $this->isActive();
    }

    public function canBeClosed(): bool
    {
        return $this->isActive() || $this->isMatured();
    }

    public function getStatusBadgeClass(): string
    {
        return match (strtoupper((string) $this->status)) {
            self::STATUS_ACTIVE => 'badge-active',
            self::STATUS_APPROVED => 'badge bg-primary text-white',
            self::STATUS_PENDING_APPROVAL => 'badge bg-warning text-dark border',
            self::STATUS_DRAFT => 'badge bg-secondary text-white',
            self::STATUS_MATURED => 'badge bg-info text-dark',
            self::STATUS_CLOSED => 'badge bg-dark text-white',
            self::STATUS_REJECTED => 'badge-inactive',
            default => 'badge bg-light text-dark border',
        };
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('deposit_number', 'like', "%{$term}%")
                ->orWhere('deposit_id', 'like', "%{$term}%")
                ->orWhereHas('customer', function ($cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%")
                        ->orWhere('customer_number', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%");
                })
                ->orWhereHas('agent', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('agent_id', 'like', "%{$term}%");
                });
        });
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByScheme($query, $schemeId)
    {
        return $query->where('deposit_scheme_id', $schemeId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', strtoupper((string) $status));
    }

    // ─── Unique Deposit Number Generator ─────────────────────────────────────

    public static function generateDepositNumber(): string
    {
        return app(NumberingService::class)->generateNext('deposit');
    }

    // ─── Maturity Date Calculation ───────────────────────────────────────────

    public static function calculateMaturityDate(CarbonInterface|string $startDate, int $tenure, string $tenureUnit): Carbon
    {
        $date = $startDate instanceof CarbonInterface ? $startDate->copy() : Carbon::parse($startDate);
        $unit = strtoupper(trim($tenureUnit));

        return match ($unit) {
            'DAYS', 'DAY' => $date->addDays($tenure),
            'WEEKS', 'WEEK' => $date->addWeeks($tenure),
            'YEARS', 'YEAR' => $date->addYears($tenure),
            default => $date->addMonths($tenure), // MONTHS default
        };
    }

    /**
     * Get deposit tenure duration converted to completed whole years.
     * Sub-1-year durations return 0 years.
     */
    public function getTenureInYears(): int
    {
        if (! empty($this->start_date) && ! empty($this->maturity_date)) {
            try {
                $years = Carbon::parse($this->start_date)->diffInYears(Carbon::parse($this->maturity_date));
                if ($years > 0) {
                    return (int) $years;
                }
            } catch (\Exception $e) {
                // Fall back to unit/tenure attributes
            }
        }

        $unit = strtoupper(trim((string) ($this->tenure_unit ?? $this->scheme?->tenure_unit ?? 'YEARS')));
        $tenure = (int) ($this->tenure ?? $this->scheme?->tenure ?? 0);

        return match ($unit) {
            'DAYS', 'DAY' => (int) floor($tenure / 365),
            'WEEKS', 'WEEK' => (int) floor(($tenure * 7) / 365),
            'MONTHS', 'MONTH' => (int) floor($tenure / 12),
            default => max(0, $tenure), // YEARS
        };
    }

    /**
     * Get the percentage of this deposit that credits towards promotion targets.
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

        if ($this->scheme && $this->scheme->promotion_target_percentage !== null) {
            return (float) $this->scheme->promotion_target_percentage;
        }

        $type = (string) ($this->deposit_type ?: $this->scheme?->scheme_type ?: 'FD');
        $years = $this->getTenureInYears();

        if ($rule) {
            return $rule->getDepositPromotionPercentage($type, $years);
        }

        if ($this->scheme) {
            return $this->scheme->getPromotionTargetPercentage();
        }

        if ($years < 1) {
            return 0.00;
        }

        $schemeKey = in_array(strtoupper(trim($type)), ['DD', 'DAILY', 'DAILY DEPOSIT'], true)
            ? 'dd'
            : (in_array(strtoupper(trim($type)), ['RD', 'RECURRING', 'RECURRING DEPOSIT'], true) ? 'rd' : 'fd');

        $map = PromotionRule::DEFAULT_TENURE_TARGETS[$schemeKey] ?? [];

        return isset($map[(string) $years]) ? (float) $map[(string) $years] : 0.00;
    }

    /**
     * Calculate the volume credited toward promotion targets for this deposit.
     */
    public function getPromotionTargetCreditedAmount(?PromotionRule $rule = null): float
    {
        $percent = $this->getPromotionTargetPercentage($rule);

        return round(((float) $this->amount) * ($percent / 100.0), 2);
    }

    // ─── Maturity Amount Calculation ─────────────────────────────────────────

    /**
     * Calculate Recurring Deposit (RD) maturity breakdown using the Simple Interest Product Method
     * (month-by-month simple interest).
     *
     * Formulas:
     * - monthly_interest = (P * (r / 100)) / 12 (optionally truncated to 2 decimal places)
     * - total_interest = monthly_interest * (n * (n + 1) / 2)
     * - total_principal = P * n
     * - maturity_value = total_principal + total_interest
     *
     * @return array{
     *     monthly_installment: float,
     *     tenure_months: int,
     *     annual_rate: float,
     *     monthly_interest: float,
     *     sum_of_months: int,
     *     total_principal: float,
     *     total_interest: float,
     *     maturity_value: float,
     *     schedule: array<int, array{
     *         month: int,
     *         installment: float,
     *         cumulative_principal: float,
     *         months_invested: int,
     *         monthly_interest_accrued: float,
     *         installment_interest: float,
     *         cumulative_interest: float,
     *         closing_balance: float
     *     }>
     * }
     */
    public static function calculateRdSimpleInterest(
        float $P,
        int $n,
        float $r,
        bool $truncate_monthly_interest = true
    ): array {
        if ($P <= 0 || $n <= 0) {
            return [
                'monthly_installment' => 0.00,
                'tenure_months' => max(0, $n),
                'annual_rate' => max(0.00, $r),
                'monthly_interest' => 0.00,
                'sum_of_months' => 0,
                'total_principal' => 0.00,
                'total_interest' => 0.00,
                'maturity_value' => 0.00,
                'schedule' => [],
            ];
        }

        $rawMonthlyInterest = $r > 0 ? (($P * ($r / 100.0)) / 12.0) : 0.00;

        $monthlyInterest = $truncate_monthly_interest
            ? floor(($rawMonthlyInterest * 100.0) + 1e-9) / 100.0
            : $rawMonthlyInterest;

        $sumOfMonths = intdiv($n * ($n + 1), 2);
        $totalPrincipal = round($P * $n, 2);
        $totalInterest = round($monthlyInterest * $sumOfMonths, 2);
        $maturityValue = round($totalPrincipal + $totalInterest, 2);

        $schedule = [];
        for ($month = 1; $month <= $n; $month++) {
            $cumulativePrincipal = round($P * $month, 2);
            $monthsInvested = $n - $month + 1;
            $monthlyInterestAccrued = round($monthlyInterest * $month, 2);
            $installmentInterest = round($monthlyInterest * $monthsInvested, 2);
            $cumulativeInterest = round($monthlyInterest * (($month * ($month + 1)) / 2), 2);

            $schedule[] = [
                'month' => $month,
                'installment' => round($P, 2),
                'cumulative_principal' => $cumulativePrincipal,
                'months_invested' => $monthsInvested,
                'monthly_interest_accrued' => $monthlyInterestAccrued,
                'installment_interest' => $installmentInterest,
                'cumulative_interest' => $cumulativeInterest,
                'closing_balance' => round($cumulativePrincipal + $cumulativeInterest, 2),
            ];
        }

        return [
            'monthly_installment' => round($P, 2),
            'tenure_months' => $n,
            'annual_rate' => round($r, 2),
            'monthly_interest' => $truncate_monthly_interest ? $monthlyInterest : round($monthlyInterest, 6),
            'sum_of_months' => $sumOfMonths,
            'total_principal' => $totalPrincipal,
            'total_interest' => $totalInterest,
            'maturity_value' => $maturityValue,
            'schedule' => $schedule,
        ];
    }

    /**
     * Get the RD Simple Interest Product Method breakdown and month-by-month schedule for this deposit.
     *
     * @return array<string, mixed>
     */
    public function getRdInterestBreakdown(bool $truncate_monthly_interest = true): array
    {
        $unit = strtoupper(trim((string) ($this->tenure_unit ?? $this->scheme?->tenure_unit ?? 'MONTHS')));
        $rawTenure = (int) ($this->tenure ?? $this->scheme?->tenure ?? 12);

        $tenureInMonths = match ($unit) {
            'DAYS', 'DAY' => (int) max(1, round($rawTenure / 30.4167)),
            'WEEKS', 'WEEK' => (int) max(1, round(($rawTenure * 7) / 30.4167)),
            'YEARS', 'YEAR' => $rawTenure * 12,
            default => max(1, $rawTenure),
        };

        $rate = (float) ($this->interest_rate ?? $this->scheme?->interest_rate ?? 0.00);

        return static::calculateRdSimpleInterest(
            P: (float) ($this->amount ?? 0.00),
            n: $tenureInMonths,
            r: $rate,
            truncate_monthly_interest: $truncate_monthly_interest
        );
    }

    public static function calculateMaturityAmount(
        float $amount,
        float $interestRate,
        int $tenure,
        string $tenureUnit = 'MONTHS',
        string $schemeType = 'FD',
        string $frequency = 'ONE_TIME',
        bool $roundToInteger = false,
        bool $isDouble = false,
        string $payoutFrequency = self::PAYOUT_AT_MATURITY,
        bool $truncateMonthlyInterest = true
    ): float {
        if ($amount <= 0) {
            return 0.00;
        }

        if ($isDouble || strtoupper(trim($schemeType)) === 'DOUBLE') {
            $doubled = $amount * 2;

            return $roundToInteger ? round($doubled) : round($doubled, 2);
        }

        $type = strtoupper(trim($schemeType));
        $unit = strtoupper(trim($tenureUnit));
        $freq = strtoupper(trim($frequency));
        $payout = strtoupper(trim($payoutFrequency));

        // Convert tenure duration to exact tenure in years
        $tenureInYears = match ($unit) {
            'YEARS', 'YEAR' => (float) $tenure,
            'MONTHS', 'MONTH' => (float) $tenure / 12,
            'WEEKS', 'WEEK' => ((float) $tenure * 7) / 365,
            'DAYS', 'DAY' => (float) $tenure / 365,
            default => (float) $tenure / 12,
        };

        // Convert tenure to months for recurring calculations
        $tenureInMonths = match ($unit) {
            'DAYS', 'DAY' => $tenure / 30.4167,
            'WEEKS', 'WEEK' => ($tenure * 7) / 30.4167,
            'YEARS', 'YEAR' => $tenure * 12,
            default => (float) $tenure,
        };

        // Dynamic calculation type from SchemeType model if available
        $calcType = null;
        if (! empty($type)) {
            $typeModel = SchemeType::where('code', $type)->first();
            $calcType = $typeModel?->calculation_type;
        }

        if (! $calcType) {
            $calcType = match (true) {
                $type === 'DOUBLE' => SchemeType::CALC_DOUBLE,
                $type === 'MIS' => SchemeType::CALC_LUMP_SUM,
                $type === 'RD' || $type === 'RECURRING' || $freq === DepositScheme::FREQ_MONTHLY || $freq === DepositScheme::FREQ_WEEKLY => SchemeType::CALC_RECURRING,
                $type === 'DD' || $type === 'DAILY' || $freq === DepositScheme::FREQ_DAILY => SchemeType::CALC_DAILY,
                default => SchemeType::CALC_LUMP_SUM,
            };
        }

        if ($calcType === SchemeType::CALC_DOUBLE) {
            $doubled = $amount * 2;

            return $roundToInteger ? round($doubled) : round($doubled, 2);
        }

        // Monthly Return Plan (MRP / MIS) or periodic non-cumulative payout:
        // Interest is paid periodically (not compounded annually), so total interest is simple interest.
        $isPeriodicPayout = $type === 'MIS' || in_array($payout, [
            self::PAYOUT_MONTHLY,
            self::PAYOUT_QUARTERLY,
            self::PAYOUT_ANNUALLY,
        ], true);

        if ($isPeriodicPayout && $calcType === SchemeType::CALC_LUMP_SUM) {
            if ($interestRate <= 0 || $tenureInYears <= 0) {
                return $roundToInteger ? round($amount) : round($amount, 2);
            }

            $totalInterest = $amount * ($interestRate / 100) * $tenureInYears;
            $totalPayout = $amount + $totalInterest;

            return $roundToInteger ? round($totalPayout) : round($totalPayout, 2);
        }

        if ($calcType === SchemeType::CALC_RECURRING) {
            // Recurring Deposit: Simple Interest Product Method (month-by-month simple interest)
            // monthly_interest = (P * (r / 100)) / 12
            // total_interest = monthly_interest * (n * (n + 1) / 2)
            // total_principal = P * n
            // maturity_value = total_principal + total_interest
            $n = max(1, (int) round($tenureInMonths));
            $rdBreakdown = static::calculateRdSimpleInterest(
                P: $amount,
                n: $n,
                r: (float) $interestRate,
                truncate_monthly_interest: $truncateMonthlyInterest
            );
            $maturity = $rdBreakdown['maturity_value'];

            return $roundToInteger ? round($maturity) : round($maturity, 2);
        }

        if ($calcType === SchemeType::CALC_DAILY) {
            // Daily Pigmy Collection formula
            $days = match ($unit) {
                'MONTHS', 'MONTH' => $tenure * 30,
                'YEARS', 'YEAR' => $tenure * 365,
                default => (float) $tenure,
            };
            $totalPrincipal = $amount * $days;
            $interest = $totalPrincipal * ($interestRate / 100) * ($days / 365);
            $maturity = $totalPrincipal + $interest;

            return $roundToInteger ? round($maturity) : round($maturity, 2);
        }

        // Fixed Deposit (FD) / Lump-Sum: Annual compound interest
        // Formula: maturityAmount = principal * Math.pow(1 + annualInterestRate / 100, tenureInYears)
        // totalInterest = maturityAmount - principal
        if ($interestRate <= 0 || $tenureInYears <= 0) {
            return $roundToInteger ? round($amount) : round($amount, 2);
        }

        $maturityAmount = $amount * pow(1 + ($interestRate / 100), $tenureInYears);

        return $roundToInteger ? round($maturityAmount) : round($maturityAmount, 2);
    }

    /**
     * Determine whether this deposit belongs to a Monthly Return Plan (MRP / MIS).
     */
    public function isMrp(): bool
    {
        if (strtoupper((string) $this->deposit_type) === 'MIS') {
            return true;
        }

        if (strtoupper((string) $this->payout_frequency) === self::PAYOUT_MONTHLY) {
            return true;
        }

        return (bool) ($this->scheme?->isMrp());
    }

    /**
     * Calculate the monthly interest payout amount for MRP / MIS deposits.
     */
    public function getMonthlyReturnAmount(): float
    {
        $principal = (float) ($this->amount ?? 0);
        $rate = (float) ($this->interest_rate ?? 0);

        if ($principal <= 0 || $rate <= 0) {
            return 0.00;
        }

        $method = $this->scheme?->mrpConfiguration?->calculation_method
            ?? DepositSchemeMrpConfiguration::METHOD_STANDARD;

        return DepositSchemeMrpConfiguration::calculateMonthlyReturn($principal, $rate, $method);
    }

    /**
     * Get estimated interest earned over the deposit lifetime.
     */
    public function getEstimatedInterestAttribute(): float
    {
        $maturity = (float) ($this->maturity_amount ?? 0);
        $principal = (float) ($this->amount ?? 0);

        if ($this->isKsp()) {
            $kspDetails = $this->getKspDetails();
            if ($kspDetails) {
                return max(0.00, round($maturity - $kspDetails['total_deposited'], 2));
            }
        }

        $type = strtoupper((string) ($this->deposit_type ?? $this->scheme?->scheme_type ?? 'FD'));
        $freq = strtoupper((string) ($this->collection_frequency ?? 'ONE_TIME'));

        $calcType = $this->scheme?->schemeType?->calculation_type ?? match (true) {
            $type === 'MIS' => SchemeType::CALC_LUMP_SUM,
            $type === 'RD' || $type === 'RECURRING' || $freq === DepositScheme::FREQ_MONTHLY || $freq === DepositScheme::FREQ_WEEKLY => SchemeType::CALC_RECURRING,
            $type === 'DD' || $type === 'DAILY' || $freq === DepositScheme::FREQ_DAILY => SchemeType::CALC_DAILY,
            default => SchemeType::CALC_LUMP_SUM,
        };

        if ($calcType === SchemeType::CALC_RECURRING) {
            $unit = strtoupper((string) ($this->tenure_unit ?? 'MONTHS'));
            $n = match ($unit) {
                'YEARS', 'YEAR' => (int) $this->tenure * 12,
                default => (int) $this->tenure,
            };
            $totalPrincipal = $principal * max(1, $n);

            return max(0.00, round($maturity - $totalPrincipal, 2));
        }

        if ($calcType === SchemeType::CALC_DAILY) {
            $days = $this->tenure_unit === 'MONTHS' ? $this->tenure * 30 : $this->tenure;
            $totalPrincipal = $principal * max(1, $days);

            return max(0.00, round($maturity - $totalPrincipal, 2));
        }

        return max(0.00, round($maturity - $principal, 2));
    }

    /**
     * Get exact maturity amount (rounded to 2 decimal places).
     */
    public function getExactMaturityAmountAttribute(): float
    {
        return round((float) ($this->maturity_amount ?? $this->amount ?? 0), 2);
    }

    /**
     * Get rounded maturity amount (rounded to nearest whole rupee integer).
     */
    public function getRoundedMaturityAmountAttribute(): float
    {
        return round((float) ($this->maturity_amount ?? $this->amount ?? 0));
    }

    /**
     * Get rounded estimated interest (rounded to nearest whole rupee integer).
     */
    public function getRoundedEstimatedInterestAttribute(): float
    {
        return round((float) $this->estimated_interest);
    }

    /**
     * Calculate premature closure details including lock-in checks, penalty deduction, and payout amount.
     *
     * @return array{
     *     is_premature: bool,
     *     allowed: bool,
     *     penalty_percent: float,
     *     penalty_amount: float,
     *     principal_amount: float,
     *     accrued_interest: float,
     *     payout_amount: float,
     *     effective_rate: float,
     *     reason: ?string
     * }
     */
    public function calculatePrematureClosureDetails(?CarbonInterface $closureDate = null): array
    {
        $closure = $closureDate ? Carbon::parse($closureDate) : now();
        $maturityDate = Carbon::parse($this->maturity_date);
        $scheme = $this->scheme;

        $isPremature = $this->status !== self::STATUS_MATURED && $closure->lt($maturityDate);

        if (! $isPremature) {
            return [
                'is_premature' => false,
                'allowed' => true,
                'penalty_percent' => 0.00,
                'penalty_amount' => 0.00,
                'principal_amount' => (float) $this->amount,
                'accrued_interest' => (float) $this->estimated_interest,
                'payout_amount' => (float) ($this->maturity_amount ?? $this->amount),
                'effective_rate' => (float) $this->interest_rate,
                'reason' => null,
            ];
        }

        // Check whether scheme allows premature withdrawal
        if (($scheme && $scheme->allow_premature_withdrawal === false)
            || ($this->isKsp() && strcasecmp((string) ($scheme?->getKspConfig()['premature_withdrawal'] ?? ''), 'Not Allowed') === 0)
        ) {
            return [
                'is_premature' => true,
                'allowed' => false,
                'penalty_percent' => 0.00,
                'penalty_amount' => 0.00,
                'principal_amount' => (float) $this->amount,
                'accrued_interest' => 0.00,
                'payout_amount' => 0.00,
                'effective_rate' => 0.00,
                'reason' => 'Premature withdrawal is not allowed for this scheme.',
            ];
        }

        // Check lock-in period
        $startDate = Carbon::parse($this->start_date ?? $this->deposit_date ?? $this->created_at);
        $lockInMonths = (int) ($scheme?->lock_in_months ?? 0);
        if ($lockInMonths > 0) {
            $lockInEndDate = $startDate->copy()->addMonths($lockInMonths);
            if ($closure->lt($lockInEndDate)) {
                return [
                    'is_premature' => true,
                    'allowed' => false,
                    'penalty_percent' => 0.00,
                    'penalty_amount' => 0.00,
                    'principal_amount' => (float) $this->amount,
                    'accrued_interest' => 0.00,
                    'payout_amount' => 0.00,
                    'effective_rate' => 0.00,
                    'reason' => "Deposit cannot be closed before lock-in period of {$lockInMonths} month(s) expires on {$lockInEndDate->format('Y-m-d')}.",
                ];
            }
        }

        $penaltyPercent = (float) ($scheme?->premature_penalty_percent ?? 0.00);
        $originalRate = (float) $this->interest_rate;
        $effectiveRate = max(0.00, $originalRate - $penaltyPercent);

        $daysCompleted = max(1, $startDate->diffInDays($closure));
        $monthsCompleted = max(0, $startDate->diffInMonths($closure));

        $depositType = strtoupper((string) ($this->deposit_type ?? $scheme?->scheme_type ?? 'FD'));
        $freq = strtoupper((string) ($this->collection_frequency ?? 'ONE_TIME'));

        $isRecurring = ($depositType === 'RD' || $depositType === 'RECURRING' || $freq === DepositScheme::FREQ_MONTHLY);

        if ($isRecurring) {
            $totalPrincipal = (float) $this->collections()->where('status', DepositCollection::STATUS_PAID)->sum('paid_amount');
            if ($totalPrincipal <= 0) {
                $totalPrincipal = (float) $this->collectionPayments()->sum('amount');
            }
            if ($totalPrincipal <= 0) {
                $totalPrincipal = (float) $this->amount;
            }

            $n = max(1, (int) round($monthsCompleted));
            $accruedInterest = $totalPrincipal * (($n * ($n + 1)) / 24) * ($effectiveRate / 100);
            $penaltyAmount = $totalPrincipal * (($n * ($n + 1)) / 24) * ($penaltyPercent / 100);
            $payoutAmount = round($totalPrincipal + $accruedInterest, 2);
        } else {
            $totalPrincipal = (float) $this->amount;
            $yearsCompleted = $daysCompleted / 365.25;
            $accruedInterest = $totalPrincipal * ($effectiveRate / 100) * $yearsCompleted;
            $penaltyAmount = $totalPrincipal * ($penaltyPercent / 100) * $yearsCompleted;
            $payoutAmount = round($totalPrincipal + $accruedInterest, 2);
        }

        return [
            'is_premature' => true,
            'allowed' => true,
            'penalty_percent' => $penaltyPercent,
            'penalty_amount' => round($penaltyAmount, 2),
            'principal_amount' => round($totalPrincipal, 2),
            'accrued_interest' => round($accruedInterest, 2),
            'payout_amount' => $payoutAmount,
            'effective_rate' => $effectiveRate,
            'reason' => null,
        ];
    }
}
