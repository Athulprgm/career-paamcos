<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentRankTarget extends Model
{
    use HasFactory;

    protected $table = 'agent_rank_targets';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'financial_year_id',
        'rank_id',
        'total_deposit_target',
        'fd_percentage',
        'rd_percentage',
        'fd_target_amount',
        'rd_target_amount',
        'recruitment_target',
        'status',
        'description',
        'created_by',
    ];

    protected $casts = [
        'total_deposit_target' => 'decimal:2',
        'fd_percentage' => 'decimal:2',
        'rd_percentage' => 'decimal:2',
        'fd_target_amount' => 'decimal:2',
        'rd_target_amount' => 'decimal:2',
        'recruitment_target' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (AgentRankTarget $target) {
            $total = (float) $target->total_deposit_target;
            $fdPct = (float) ($target->fd_percentage ?? 90.00);
            $rdPct = (float) ($target->rd_percentage ?? 10.00);

            // Recompute automatic split amounts
            $target->fd_target_amount = round($total * ($fdPct / 100.0), 2);
            $target->rd_target_amount = round($total * ($rdPct / 100.0), 2);
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(AgentRank::class, 'rank_id');
    }

    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class, 'rank_id');
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

    public function scopeForYear($query, int $financialYearId)
    {
        return $query->where('financial_year_id', $financialYearId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
