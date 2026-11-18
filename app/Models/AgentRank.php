<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentRank extends Model
{
    protected $table = 'ranks';

    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'status',
        'level',
        'code',
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

    protected static function booted(): void
    {
        static::creating(function (AgentRank $rank) {
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

        static::saving(function (AgentRank $rank) {
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

    // ─── Relationships ───────────────────────────────────────────────────────

    public function agents()
    {
        return $this->hasMany(Agent::class, 'rank_id');
    }

    public function commissionRules()
    {
        return $this->hasMany(CommissionRule::class, 'rank_id');
    }

    public function fdSchemeCommissions()
    {
        return $this->hasMany(DepositSchemeRankCommission::class, 'rank_id');
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
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }
}
