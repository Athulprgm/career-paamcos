<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositSchemeRankCommission extends Model
{
    use HasFactory;

    protected $table = 'deposit_scheme_rank_commissions';

    protected $fillable = [
        'deposit_scheme_id',
        'rank_id',
        'commission_percentage',
        'is_eligible',
        'payout_year',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'is_eligible' => 'boolean',
        'payout_year' => 'integer',
    ];

    /**
     * Associated Deposit Scheme.
     */
    public function scheme()
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    /**
     * Associated Agent Rank.
     */
    public function rank()
    {
        return $this->belongsTo(AgentRank::class, 'rank_id');
    }

    /**
     * Scope to only eligible rank rules.
     */
    public function scopeEligible($query)
    {
        return $query->where('is_eligible', true);
    }
}
