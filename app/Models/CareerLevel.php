<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerLevel extends Model
{
    use HasFactory;

    protected $table = 'ranks';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'code',
        'level',
        'sort_order',
        'description',
        'status',
        'minimum_deposit_amount',
        'commission_rate',
        'min_service_days',
        'min_active_customers',
        'min_deposit_count',
        'min_deposit_amount',
        'min_collection_amount',
        'min_commission_amount',
        'min_performance_score',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'level' => 'integer',
        'min_service_days' => 'integer',
        'min_active_customers' => 'integer',
        'min_deposit_count' => 'integer',
        'minimum_deposit_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'min_deposit_amount' => 'decimal:2',
        'min_collection_amount' => 'decimal:2',
        'min_commission_amount' => 'decimal:2',
        'min_performance_score' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CareerLevel $rank) {
            if (empty($rank->level)) {
                $maxLevel = (int) (static::max('level') ?? 0);
                $rank->level = ! empty($rank->sort_order) && ! static::where('level', $rank->sort_order)->exists()
                    ? (int) $rank->sort_order
                    : ($maxLevel + 1);
            }
            if (empty($rank->sort_order) && ! empty($rank->level)) {
                $rank->sort_order = (int) $rank->level;
            }
        });

        static::saving(function (CareerLevel $rank) {
            // Keep duplicate columns minimum_deposit_amount & min_deposit_amount synchronized
            if ($rank->isDirty('minimum_deposit_amount') && ! $rank->isDirty('min_deposit_amount')) {
                $rank->min_deposit_amount = $rank->minimum_deposit_amount;
            } elseif ($rank->isDirty('min_deposit_amount') && ! $rank->isDirty('minimum_deposit_amount')) {
                $rank->minimum_deposit_amount = $rank->min_deposit_amount;
            } elseif ($rank->minimum_deposit_amount !== null && $rank->min_deposit_amount === null) {
                $rank->min_deposit_amount = $rank->minimum_deposit_amount;
            } elseif ($rank->min_deposit_amount !== null && $rank->minimum_deposit_amount === null) {
                $rank->minimum_deposit_amount = $rank->min_deposit_amount;
            }

            if (empty($rank->sort_order) && ! empty($rank->level)) {
                $rank->sort_order = (int) $rank->level;
            }
            if (empty($rank->level) && ! empty($rank->sort_order)) {
                $rank->level = (int) $rank->sort_order;
            }
        });
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_ACTIVE;
    }

    /**
     * Get the next career progression level.
     */
    public function nextLevel(): ?self
    {
        return self::where('sort_order', '>', $this->sort_order)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('sort_order', 'asc')
            ->first();
    }

    /**
     * Get the previous career level.
     */
    public function previousLevel(): ?self
    {
        return self::where('sort_order', '<', $this->sort_order)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Check if this level is the entry/initial level.
     */
    public function isInitialLevel(): bool
    {
        $minSort = self::where('status', self::STATUS_ACTIVE)->min('sort_order');

        return (int) $this->sort_order === (int) $minSort;
    }

    /**
     * Check if this level is the maximum rank/level.
     */
    public function isMaxLevel(): bool
    {
        $maxSort = self::where('status', self::STATUS_ACTIVE)->max('sort_order');

        return (int) $this->sort_order === (int) $maxSort;
    }

    /**
     * Checks if this level has any measurable qualification criteria configured.
     */
    public function hasConfiguredCriteria(): bool
    {
        return $this->min_service_days > 0
            || $this->min_active_customers > 0
            || $this->min_deposit_count > 0
            || (float) $this->min_deposit_amount > 0
            || (float) $this->min_collection_amount > 0
            || (float) $this->min_commission_amount > 0
            || (float) $this->min_performance_score > 0;
    }

    public function getTargetDepositAttribute(): float
    {
        return (float) ($this->minimum_deposit_amount ?? $this->min_deposit_amount ?? 0.0);
    }

    public function getCommissionPercentageAttribute(): float
    {
        return (float) ($this->commission_rate ?? 0.0);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function agents()
    {
        return $this->hasMany(Agent::class, 'rank_id');
    }

    public function promotionRequestsCurrent()
    {
        return $this->hasMany(PromotionRequest::class, 'current_level_id');
    }

    public function promotionRequestsProposed()
    {
        return $this->hasMany(PromotionRequest::class, 'proposed_level_id');
    }

    public function careerHistories()
    {
        return $this->hasMany(CareerHistory::class, 'new_level_id');
    }

    public function promotionRulesFrom()
    {
        return $this->hasMany(PromotionRule::class, 'from_rank_id');
    }

    public function promotionRulesTo()
    {
        return $this->hasMany(PromotionRule::class, 'to_rank_id');
    }

    public function financialYearTargets()
    {
        return $this->hasMany(AgentRankTarget::class, 'rank_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('level', 'asc');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
