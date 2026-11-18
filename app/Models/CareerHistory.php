<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerHistory extends Model
{
    use HasFactory;

    protected $table = 'career_histories';

    public const ACTION_PROMOTION = 'PROMOTION';

    public const ACTION_DEMOTION = 'DEMOTION';

    public const ACTION_INITIAL_PLACEMENT = 'INITIAL_PLACEMENT';

    public const ACTION_MANUAL_ADJUSTMENT = 'MANUAL_ADJUSTMENT';

    protected $fillable = [
        'agent_id',
        'promotion_request_id',
        'previous_level_id',
        'new_level_id',
        'performance_period',
        'promotion_date',
        'approved_by',
        'action',
        'remarks',
    ];

    protected $casts = [
        'promotion_date' => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function previousLevel()
    {
        return $this->belongsTo(CareerLevel::class, 'previous_level_id');
    }

    public function newLevel()
    {
        return $this->belongsTo(CareerLevel::class, 'new_level_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function promotionRequest()
    {
        return $this->belongsTo(PromotionRequest::class, 'promotion_request_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->whereHas('agent', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('remarks', 'like', "%{$term}%")
                ->orWhereHas('agent', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('agent_id', 'like', "%{$term}%")
                        ->orWhere('member_code', 'like', "%{$term}%");
                });
        });
    }
}
