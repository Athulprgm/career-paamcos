<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commission_rules';

    public const TYPE_PERCENTAGE = 'PERCENTAGE';

    public const TYPE_FIXED = 'FIXED';

    public const BASIS_COLLECTION_AMOUNT = 'COLLECTION_AMOUNT';

    public const BASIS_DEPOSIT_AMOUNT = 'DEPOSIT_AMOUNT';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'name',
        'code',
        'commission_type',
        'calculation_basis',
        'rate',
        'deposit_scheme_id',
        'rank_id',
        'effective_from',
        'effective_to',
        'status',
        'description',
        'created_by',
        'deposit_type',
        'commission_percentage',
        'fixed_amount',
        'minimum_amount',
        'maximum_amount',
        'calculation_type',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'commission_percentage' => 'decimal:3',
        'fixed_amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CommissionRule $rule) {
            if (empty($rule->code) || static::withTrashed()->where('code', $rule->code)->exists()) {
                $rule->code = static::generateRuleCode();
            }
            if (empty($rule->commission_type)) {
                $rule->commission_type = self::TYPE_PERCENTAGE;
            }
            if (empty($rule->calculation_basis)) {
                $rule->calculation_basis = self::BASIS_COLLECTION_AMOUNT;
            }
            if ($rule->commission_type === self::TYPE_FIXED) {
                $rule->fixed_amount = $rule->rate;
            } else {
                $rule->commission_percentage = $rule->rate;
            }
            if (empty($rule->calculation_type)) {
                $rule->calculation_type = strtolower((string) $rule->calculation_basis);
            }
        });

        static::updating(function (CommissionRule $rule) {
            if ($rule->isDirty('rate') || $rule->isDirty('commission_type')) {
                if ($rule->commission_type === self::TYPE_FIXED) {
                    $rule->fixed_amount = $rule->rate;
                } else {
                    $rule->commission_percentage = $rule->rate;
                }
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function scheme()
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    public function rank()
    {
        return $this->belongsTo(AgentRank::class, 'rank_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'rule_id');
    }

    // ─── Status & Effectiveness Helpers ───────────────────────────────────────

    public function isActive(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_ACTIVE;
    }

    public function isEffective(?Carbon $date = null): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $checkDate = $date ?? Carbon::today();

        if ($this->effective_from && $checkDate->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $checkDate->gt($this->effective_to)) {
            return false;
        }

        return true;
    }

    public function getStatusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-active' : 'badge-inactive';
    }

    // ─── Calculation Logic ───────────────────────────────────────────────────

    public function calculateCommission(float $baseAmount): float
    {
        if ($this->commission_type === self::TYPE_FIXED) {
            return max(0.00, round((float) $this->rate, 2));
        }

        // Percentage calculation: Base Amount × Rate / 100
        $calculated = round(($baseAmount * (float) $this->rate) / 100, 2);

        return max(0.00, $calculated);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeByScheme($query, $schemeId)
    {
        return $query->where('deposit_scheme_id', $schemeId);
    }

    public function scopeByRank($query, $rankId)
    {
        return $query->where('rank_id', $rankId);
    }

    // ─── Unique Code Generator ────────────────────────────────────────────────

    public static function generateRuleCode(): string
    {
        $lastId = static::withTrashed()->max('id') ?? 0;
        $candidate = 'CR'.str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

        $counter = 1;
        while (static::withTrashed()->where('code', $candidate)->exists()) {
            $candidate = 'CR'.str_pad($lastId + 1 + $counter, 4, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $candidate;
    }
}
