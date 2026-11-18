<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTargetAssignment extends Model
{
    use HasFactory;

    protected $table = 'agent_target_assignments';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'agent_id',
        'financial_year_id',
        'rank_id',
        'target_configuration_id',
        'total_deposit_target',
        'fd_percentage',
        'rd_percentage',
        'fd_target_amount',
        'rd_target_amount',
        'recruitment_target',
        'effective_from',
        'effective_to',
        'is_custom',
        'revision_reason',
        'assigned_by',
        'status',
    ];

    protected $casts = [
        'total_deposit_target' => 'decimal:2',
        'fd_percentage' => 'decimal:2',
        'rd_percentage' => 'decimal:2',
        'fd_target_amount' => 'decimal:2',
        'rd_target_amount' => 'decimal:2',
        'recruitment_target' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_custom' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (AgentTargetAssignment $assignment) {
            $total = (float) $assignment->total_deposit_target;
            $fdPct = (float) ($assignment->fd_percentage ?? 90.00);
            $rdPct = (float) ($assignment->rd_percentage ?? 10.00);

            $assignment->fd_target_amount = round($total * ($fdPct / 100.0), 2);
            $assignment->rd_target_amount = round($total * ($rdPct / 100.0), 2);
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

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

    public function targetConfiguration(): BelongsTo
    {
        return $this->belongsTo(AgentRankTarget::class, 'target_configuration_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
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

    public function scopeEffectiveAt($query, string $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
