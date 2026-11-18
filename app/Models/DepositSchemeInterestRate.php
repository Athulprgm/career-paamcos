<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepositSchemeInterestRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'deposit_scheme_interest_rates';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'deposit_scheme_id',
        'tenure',
        'tenure_unit',
        'interest_rate',
        'senior_citizen_extra_rate',
        'min_amount',
        'max_amount',
        'effective_from',
        'effective_to',
        'status',
        'calculation_method',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tenure' => 'integer',
        'interest_rate' => 'decimal:2',
        'senior_citizen_extra_rate' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Parent deposit scheme.
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    /**
     * Alias for scheme relationship.
     */
    public function depositScheme(): BelongsTo
    {
        return $this->scheme();
    }

    /**
     * Creator user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater user.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Deposits linked to this interest rate configuration.
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'deposit_interest_rate_id');
    }

    // ─── Status & Effective Date Helpers ─────────────────────────────────────

    public function isActive(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    public function isEffectiveOn(CarbonInterface|string|null $date = null): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
        $from = $this->effective_from ? Carbon::parse($this->effective_from)->toDateString() : '1970-01-01';
        $to = $this->effective_to ? Carbon::parse($this->effective_to)->toDateString() : null;

        if ($targetDate < $from) {
            return false;
        }

        if ($to !== null && $targetDate > $to) {
            return false;
        }

        return true;
    }

    public function getStatusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-active' : 'badge-inactive';
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Scope to active interest rates that are currently effective.
     */
    public function scopeActive($query, CarbonInterface|string|null $date = null)
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $targetDate);
            });
    }

    /**
     * Scope to interest rates effective on a specific date.
     */
    public function scopeEffectiveOn($query, CarbonInterface|string $date)
    {
        $targetDate = Carbon::parse($date)->toDateString();

        return $query->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $targetDate);
            });
    }

    public function scopeForScheme($query, int $schemeId)
    {
        return $query->where('deposit_scheme_id', $schemeId);
    }

    public function scopeForTenure($query, int $tenure, string $unit = 'YEARS')
    {
        return $query->where('tenure', $tenure)
            ->where('tenure_unit', strtoupper(trim($unit)));
    }

    // ─── Tenure Duration Helpers ─────────────────────────────────────────────

    /**
     * Convert duration to whole months for duration matching.
     */
    public function getDurationInMonths(): float
    {
        $unit = strtoupper(trim((string) $this->tenure_unit));
        $tenure = (float) $this->tenure;

        return match ($unit) {
            'DAYS', 'DAY' => $tenure / 30.4167,
            'WEEKS', 'WEEK' => ($tenure * 7) / 30.4167,
            'YEARS', 'YEAR' => $tenure * 12,
            default => $tenure,
        };
    }

    /**
     * Convert duration to whole years.
     */
    public function getDurationInYears(): float
    {
        return $this->getDurationInMonths() / 12;
    }

    /**
     * Human-readable label for this tenure duration.
     * e.g. "2 Years", "12 Months".
     */
    public function getFormattedTenure(): string
    {
        $unit = strtoupper(trim((string) $this->tenure_unit));
        $tenure = (int) $this->tenure;

        $unitLabel = match ($unit) {
            'YEARS', 'YEAR' => $tenure === 1 ? 'Year' : 'Years',
            'MONTHS', 'MONTH' => $tenure === 1 ? 'Month' : 'Months',
            'DAYS', 'DAY' => $tenure === 1 ? 'Day' : 'Days',
            'WEEKS', 'WEEK' => $tenure === 1 ? 'Week' : 'Weeks',
            default => $unit,
        };

        return "{$tenure} {$unitLabel}";
    }

    public function getFormattedTenureAttribute(): string
    {
        return $this->getFormattedTenure();
    }

    // ─── Matching & Overlap Validation Engine ────────────────────────────────

    /**
     * Find the exact matching, active, and effective rate configuration for a scheme, tenure, and date.
     */
    public static function findMatchingRate(
        int $schemeId,
        int $tenure,
        string $tenureUnit = 'YEARS',
        CarbonInterface|string|null $date = null
    ): ?self {
        $unit = strtoupper(trim($tenureUnit));
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        // 1. Exact match on scheme, tenure, unit, and date window
        $rate = static::where('deposit_scheme_id', $schemeId)
            ->where('tenure', $tenure)
            ->where('tenure_unit', $unit)
            ->where('status', self::STATUS_ACTIVE)
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($q) use ($targetDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $targetDate);
            })
            ->latest('effective_from')
            ->first();

        if ($rate) {
            return $rate;
        }

        // 2. Unit normalization check: e.g. requested 12 MONTHS, configuration stored as 1 YEARS (or vice versa)
        $tenureInMonths = match ($unit) {
            'YEARS', 'YEAR' => $tenure * 12,
            'MONTHS', 'MONTH' => $tenure,
            default => null,
        };

        if ($tenureInMonths !== null && $tenureInMonths % 12 === 0) {
            $tenureInYears = (int) ($tenureInMonths / 12);
            $altUnit = ($unit === 'YEARS' || $unit === 'YEAR') ? 'MONTHS' : 'YEARS';
            $altTenure = ($unit === 'YEARS' || $unit === 'YEAR') ? $tenureInMonths : $tenureInYears;

            return static::where('deposit_scheme_id', $schemeId)
                ->where('tenure', $altTenure)
                ->where('tenure_unit', $altUnit)
                ->where('status', self::STATUS_ACTIVE)
                ->where('effective_from', '<=', $targetDate)
                ->where(function ($q) use ($targetDate) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $targetDate);
                })
                ->latest('effective_from')
                ->first();
        }

        return null;
    }

    /**
     * Check if another active configuration for the same scheme and tenure overlaps with the given date range.
     * Overlap rule between [A_start, A_end] and [B_start, B_end]:
     * A_start <= B_end && A_end >= B_start
     */
    public static function hasOverlap(
        int $schemeId,
        int $tenure,
        string $tenureUnit,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?int $ignoreId = null
    ): bool {
        $unit = strtoupper(trim($tenureUnit));
        $from = Carbon::parse($effectiveFrom)->toDateString();
        $to = $effectiveTo ? Carbon::parse($effectiveTo)->toDateString() : '9999-12-31';

        $query = static::where('deposit_scheme_id', $schemeId)
            ->where('tenure', $tenure)
            ->where('tenure_unit', $unit)
            ->where('status', self::STATUS_ACTIVE)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($sub) use ($from, $to) {
                    $sub->where('effective_from', '<=', $to)
                        ->where(function ($inner) use ($from) {
                            $inner->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $from);
                        });
                });
            });

        return $query->exists();
    }
}
