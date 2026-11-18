<?php

namespace App\Models;

use App\Services\CurrencyFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotion_rules';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const PERIOD_ALL_TIME = 'all_time';

    public const PERIOD_FINANCIAL_YEAR = 'financial_year';

    public const PERIOD_CURRENT_MONTH = 'current_month';

    public const PERIOD_LAST_30_DAYS = 'last_30_days';

    public const PERIOD_LAST_90_DAYS = 'last_90_days';

    public const PERIOD_LAST_365_DAYS = 'last_365_days';

    public const PERIOD_CALENDAR_YEAR = 'calendar_year';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ANY_ADMIN = 'any_admin';

    public const DEFAULT_TENURE_TARGETS = [
        'fd' => [
            '1' => 20.0,
            '2' => 30.0,
            '3' => 40.0,
            '5' => 80.0,
            '6' => 80.0,
        ],
        'rd' => [
            '1' => 20.0,
            '2' => 30.0,
            '3' => 40.0,
            '5' => 80.0,
            '6' => 80.0,
            '7' => 90.0,
        ],
        'dd' => [
            '1' => 20.0,
            '2' => 40.0,
            '3' => 50.0,
        ],
    ];

    protected $fillable = [
        'name',
        'from_rank_id',
        'to_rank_id',
        'financial_year_id',
        'min_individual_deposit',
        'min_team_deposit',
        'rd_composition_percentage',
        'fd_composition_percentage',
        'apply_tenure_targets',
        'tenure_target_percentages',
        'min_qualifying_direct_facilitators',
        'qualifying_rank_id',
        'qualifying_agent_tiers',
        'calculation_period',
        'qualifying_deposit_statuses',
        'required_agent_status',
        'effective_from',
        'effective_to',
        'requires_approval',
        'authorized_approval_role',
        'description',
        'min_business_amount',
        'min_downline_agents',
        'min_tenure_days',
        'required_documents',
        'custom_criteria',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'financial_year_id' => 'integer',
        'min_individual_deposit' => 'decimal:2',
        'min_team_deposit' => 'decimal:2',
        'rd_composition_percentage' => 'decimal:2',
        'fd_composition_percentage' => 'decimal:2',
        'apply_tenure_targets' => 'boolean',
        'tenure_target_percentages' => 'array',
        'min_qualifying_direct_facilitators' => 'integer',
        'min_business_amount' => 'decimal:2',
        'min_downline_agents' => 'integer',
        'min_tenure_days' => 'integer',
        'required_documents' => 'boolean',
        'qualifying_deposit_statuses' => 'array',
        'qualifying_agent_tiers' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'requires_approval' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (PromotionRule $rule): void {
            if (! $rule->isDirty('min_business_amount')
                && ($rule->isDirty('min_team_deposit') || $rule->min_business_amount === null || (float) $rule->min_business_amount == 0.0)) {
                $rule->min_business_amount = $rule->min_team_deposit ?? 0.00;
            }
            if (! $rule->isDirty('min_downline_agents')
                && ($rule->isDirty('min_qualifying_direct_facilitators') || $rule->min_downline_agents === null || (int) $rule->min_downline_agents == 0)) {
                $rule->min_downline_agents = $rule->min_qualifying_direct_facilitators ?? 0;
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function fromRank(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class, 'from_rank_id');
    }

    public function toRank(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class, 'to_rank_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function qualifyingRank(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class, 'qualifying_rank_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function promotionRequests(): HasMany
    {
        return $this->hasMany(PromotionRequest::class, 'promotion_rule_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeEffective($query, ?string $date = null)
    {
        $checkDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return $query->where(function ($q) use ($checkDate) {
            $q->whereNull('effective_from')
                ->orWhere('effective_from', '<=', $checkDate);
        })->where(function ($q) use ($checkDate) {
            $q->whereNull('effective_to')
                ->orWhere('effective_to', '>=', $checkDate);
        });
    }

    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('authorized_approval_role', 'like', "%{$term}%")
                ->orWhere('calculation_period', 'like', "%{$term}%")
                ->orWhere('status', 'like', "%{$term}%")
                ->orWhereHas('fromRank', function ($rq) use ($term) {
                    $rq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('position_name', 'like', "%{$term}%");

                    $cleanLevel = preg_replace('/^(level|lvl|l)\s*/i', '', $term);
                    if (is_numeric($cleanLevel)) {
                        $rq->orWhere('level', (int) $cleanLevel)->orWhere('sort_order', (int) $cleanLevel);
                    }
                })
                ->orWhereHas('toRank', function ($rq) use ($term) {
                    $rq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('position_name', 'like', "%{$term}%");

                    $cleanLevel = preg_replace('/^(level|lvl|l)\s*/i', '', $term);
                    if (is_numeric($cleanLevel)) {
                        $rq->orWhere('level', (int) $cleanLevel)->orWhere('sort_order', (int) $cleanLevel);
                    }
                })
                ->orWhereHas('qualifyingRank', function ($rq) use ($term) {
                    $rq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('position_name', 'like', "%{$term}%");
                })
                ->orWhereHas('financialYear', function ($fyq) use ($term) {
                    $fyq->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });

            $cleanNumber = str_replace([',', ' '], '', $term);
            if (is_numeric($cleanNumber) && (float) $cleanNumber > 0) {
                $q->orWhere('min_team_deposit', (float) $cleanNumber)
                    ->orWhere('min_individual_deposit', (float) $cleanNumber);
            }
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getTargetDepositAmountAttribute(): float
    {
        return (float) ($this->min_team_deposit ?? 0.0);
    }

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_ACTIVE;
    }

    public function isEffective(?string $date = null): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $checkDate = $date ? Carbon::parse($date)->startOfDay() : Carbon::today()->startOfDay();

        if ($this->effective_from && $checkDate->lt(Carbon::parse($this->effective_from)->startOfDay())) {
            return false;
        }

        if ($this->effective_to && $checkDate->gt(Carbon::parse($this->effective_to)->endOfDay())) {
            return false;
        }

        return true;
    }

    public function getQualifyingStatuses(): array
    {
        $statuses = $this->qualifying_deposit_statuses;
        if (empty($statuses) || ! is_array($statuses)) {
            return [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED, Deposit::STATUS_MATURED];
        }

        return array_values(array_unique(array_map('strtoupper', $statuses)));
    }

    public function getCalculationPeriodLabel(): string
    {
        return match ($this->calculation_period) {
            self::PERIOD_FINANCIAL_YEAR => $this->financialYear ? $this->financialYear->name : 'Financial Year',
            self::PERIOD_CURRENT_MONTH => 'Current Month',
            self::PERIOD_LAST_30_DAYS => 'Last 30 Days',
            self::PERIOD_LAST_90_DAYS => 'Last 90 Days',
            self::PERIOD_LAST_365_DAYS => 'Last 365 Days',
            self::PERIOD_CALENDAR_YEAR => 'Calendar Year',
            default => 'All Time',
        };
    }

    public function getTargetQualifyingRank(): ?CareerLevel
    {
        return $this->qualifyingRank ?: $this->fromRank;
    }

    /**
     * Return normalised qualifying-agent tiers.
     *
     * Each tier is: ['count' => int, 'rank_id' => int|null]
     * Falls back to the legacy single-tier columns when the JSON column is empty.
     *
     * @return array<int, array{count: int, rank_id: int|null}>
     */
    public function getQualifyingAgentTiers(): array
    {
        $tiers = $this->qualifying_agent_tiers;

        if (! empty($tiers) && is_array($tiers)) {
            return array_values($tiers);
        }

        // Legacy fallback: synthesise a single tier from old columns.
        $count = (int) $this->min_qualifying_direct_facilitators;
        if ($count === 0) {
            return [];
        }

        return [[
            'count' => $count,
            'rank_id' => $this->qualifying_rank_id,
        ]];
    }

    public function getRdCompositionPercentage(): float
    {
        return (float) ($this->rd_composition_percentage ?? 10.00);
    }

    public function getFdCompositionPercentage(): float
    {
        return (float) ($this->fd_composition_percentage ?? 90.00);
    }

    /**
     * Get configured tenure-based target credit percentages.
     *
     * @return array{fd: array<string, float>, rd: array<string, float>, dd: array<string, float>}
     */
    public function getTenureTargetPercentages(): array
    {
        $percentages = $this->tenure_target_percentages;
        if (is_string($percentages)) {
            $percentages = json_decode($percentages, true) ?: [];
        }

        if (! empty($percentages) && is_array($percentages)) {
            $fdLegacy = $percentages['fd_rd'] ?? [];
            $fdInput = $percentages['fd'] ?? $fdLegacy;
            $rdInput = $percentages['rd'] ?? $fdLegacy;
            $ddInput = $percentages['dd'] ?? [];

            $fd = [];
            foreach (self::DEFAULT_TENURE_TARGETS['fd'] as $k => $defaultVal) {
                $fd[(string) $k] = isset($fdInput[(string) $k])
                    ? (float) $fdInput[(string) $k]
                    : (float) $defaultVal;
            }

            $rd = [];
            foreach (self::DEFAULT_TENURE_TARGETS['rd'] as $k => $defaultVal) {
                $rd[(string) $k] = isset($rdInput[(string) $k])
                    ? (float) $rdInput[(string) $k]
                    : (float) $defaultVal;
            }

            $dd = [];
            foreach (self::DEFAULT_TENURE_TARGETS['dd'] as $k => $defaultVal) {
                $dd[(string) $k] = isset($ddInput[(string) $k])
                    ? (float) $ddInput[(string) $k]
                    : (float) $defaultVal;
            }

            return [
                'fd' => $fd,
                'rd' => $rd,
                'dd' => $dd,
            ];
        }

        return self::DEFAULT_TENURE_TARGETS;
    }

    /**
     * Determine what percentage of the deposit face value qualifies towards promotion targets
     * based on scheme type and tenure in whole years.
     *
     * Initial Configurable Rules:
     * - FD & RD: 1 Year = 20%, 2 Years = 30%, 3 Years = 40%, 5 Years = 80%, 6 Years = 80%
     * - RD: 7 Years = 90%
     * - DD: 1 Year = 20%, 2 Years = 40%, 3 Years = 50%
     *
     * Note: Missing durations do not receive arbitrary percentages. Unconfigured durations contribute 0%.
     */
    public function getDepositPromotionPercentage(string $depositType, int $tenureYears): float
    {
        if (! $this->apply_tenure_targets) {
            return 100.00;
        }

        if ($tenureYears < 1) {
            return 0.00;
        }

        $targets = $this->getTenureTargetPercentages();
        $type = strtoupper(trim($depositType));

        if (in_array($type, ['DD', 'DAILY', 'DAILY DEPOSIT'], true)) {
            $schemeKey = 'dd';
        } elseif (in_array($type, ['RD', 'RECURRING', 'RECURRING DEPOSIT'], true)) {
            $schemeKey = 'rd';
        } else {
            $schemeKey = 'fd';
        }

        $schemeMap = $targets[$schemeKey] ?? [];

        return isset($schemeMap[(string) $tenureYears]) ? (float) $schemeMap[(string) $tenureYears] : 0.00;
    }

    public function getStatusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-active' : 'badge-inactive';
    }

    /**
     * Snapshot representation of the rule for audit and request freezing.
     */
    public function toCriteriaSnapshot(): array
    {
        return [
            'rule_id' => $this->id,
            'name' => $this->name,
            'from_rank' => [
                'id' => $this->from_rank_id,
                'name' => $this->fromRank?->name,
                'level' => $this->fromRank?->level,
            ],
            'to_rank' => [
                'id' => $this->to_rank_id,
                'name' => $this->toRank?->name,
                'level' => $this->toRank?->level,
            ],
            'financial_year' => $this->financialYear ? [
                'id' => $this->financialYear->id,
                'name' => $this->financialYear->name,
                'code' => $this->financialYear->code,
            ] : null,
            'min_individual_deposit' => (float) $this->min_individual_deposit,
            'min_team_deposit' => (float) $this->min_team_deposit,
            'rd_composition_percentage' => $this->getRdCompositionPercentage(),
            'fd_composition_percentage' => $this->getFdCompositionPercentage(),
            'apply_tenure_targets' => (bool) $this->apply_tenure_targets,
            'tenure_target_percentages' => $this->getTenureTargetPercentages(),
            'min_qualifying_direct_facilitators' => (int) $this->min_qualifying_direct_facilitators,
            'qualifying_agent_tiers' => $this->getQualifyingAgentTiers(),
            'qualifying_rank' => [
                'id' => $this->getTargetQualifyingRank()?->id,
                'name' => $this->getTargetQualifyingRank()?->name,
            ],
            'calculation_period' => $this->calculation_period,
            'qualifying_deposit_statuses' => $this->getQualifyingStatuses(),
            'required_agent_status' => $this->required_agent_status,
            'requires_approval' => (bool) $this->requires_approval,
            'authorized_approval_role' => $this->authorized_approval_role,
        ];
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace('â‚¹', '₹', $value);
    }

    public function getMinIndividualDepositTextAttribute(): string
    {
        return CurrencyFormatter::formatText($this->min_individual_deposit);
    }

    public function getMinIndividualDepositWordsAttribute(): string
    {
        return CurrencyFormatter::toWords($this->min_individual_deposit);
    }

    public function getMinTeamDepositTextAttribute(): string
    {
        return CurrencyFormatter::formatText($this->min_team_deposit);
    }

    public function getMinTeamDepositWordsAttribute(): string
    {
        return CurrencyFormatter::toWords($this->min_team_deposit);
    }

    public function getFormattedMinIndividualDepositAttribute(): string
    {
        return CurrencyFormatter::formatIndianNumber($this->min_individual_deposit);
    }

    public function getFormattedMinTeamDepositAttribute(): string
    {
        return CurrencyFormatter::formatIndianNumber($this->min_team_deposit);
    }
}
