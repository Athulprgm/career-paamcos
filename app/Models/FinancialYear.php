<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialYear extends Model
{
    use HasFactory;

    protected $table = 'financial_years';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'is_current',
        'status',
        'description',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function rankTargets(): HasMany
    {
        return $this->hasMany(AgentRankTarget::class, 'financial_year_id');
    }

    public function agentAssignments(): HasMany
    {
        return $this->hasMany(AgentTargetAssignment::class, 'financial_year_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('start_date', 'desc');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCurrent(): bool
    {
        return (bool) $this->is_current;
    }

    public function getNameAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(["\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C", 'â€“', '–'], '-', $value);
    }

    public function getFormattedPeriodAttribute(): string
    {
        $start = $this->start_date ? $this->start_date->format('d M Y') : '';
        $end = $this->end_date ? $this->end_date->format('d M Y') : '';

        return "{$start} – {$end}";
    }

    public function getShortNameAttribute(): string
    {
        return $this->code ?: $this->name;
    }

    /**
     * Get the active/current financial year.
     */
    public static function current(): ?self
    {
        $current = static::where('is_current', true)->first();

        if ($current) {
            return $current;
        }

        return static::forDate(now()) ?? static::orderBy('start_date', 'desc')->first();
    }

    /**
     * Find or determine the financial year encompassing a given date (1 April - 31 March).
     */
    public static function forDate(CarbonInterface|string|null $date = null): ?self
    {
        $dateObj = $date ? Carbon::parse($date) : Carbon::now();
        $dateStr = $dateObj->toDateString();

        $fy = static::where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();

        if ($fy) {
            return $fy;
        }

        // Calculate expected financial year code
        $month = (int) $dateObj->format('n');
        $year = (int) $dateObj->format('Y');

        $startYear = $month >= 4 ? $year : ($year - 1);
        $endYear = $startYear + 1;
        $endShort = substr((string) $endYear, -2);
        $code = "FY{$startYear}-{$endShort}";

        return static::where('code', $code)->first();
    }

    /**
     * Helper to compute start and end dates for a financial year starting in $startYear.
     * E.g. 2026 => ['start' => '2026-04-01', 'end' => '2027-03-31']
     */
    public static function calculatePeriodForStartYear(int $startYear): array
    {
        $endYear = $startYear + 1;

        return [
            'start_date' => "{$startYear}-04-01",
            'end_date' => "{$endYear}-03-31",
            'name' => "FY {$startYear}–".substr((string) $endYear, -2),
            'code' => "FY{$startYear}-".substr((string) $endYear, -2),
        ];
    }
}
