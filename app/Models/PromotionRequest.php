<?php

namespace App\Models;

use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotion_requests';

    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_PROMOTED = 'PROMOTED';

    protected $fillable = [
        'request_number',
        'agent_id',
        'branch_id',
        'current_level_id',
        'proposed_level_id',
        'performance_period',
        'eligibility_result',
        'status',
        'remarks',
        'promotion_rule_id',
        'rule_snapshot',
        'requested_by',
        'request_date',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
        'rejection_reason',
        'promoted_at',
    ];

    protected $casts = [
        'eligibility_result' => 'array',
        'rule_snapshot' => 'array',
        'request_date' => 'date',
        'reviewed_at' => 'datetime',
        'promoted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PromotionRequest $request) {
            if (empty($request->request_number)) {
                $request->request_number = self::generateRequestNumber();
            }
            if (empty($request->request_date)) {
                $request->request_date = now()->toDateString();
            }
            if (empty($request->requested_by) && auth()->check()) {
                $request->requested_by = auth()->id();
            }
        });
    }

    /**
     * Generate unique sequential request number using NumberingService.
     */
    public static function generateRequestNumber(): string
    {
        return app(NumberingService::class)->generateNext('promotion');
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPromoted(): bool
    {
        return $this->status === self::STATUS_PROMOTED;
    }

    public function canBeReviewed(): bool
    {
        return $this->isPending();
    }

    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    public function canBePromoted(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_REVIEW, self::STATUS_APPROVED], true);
    }

    public function getStatusBadgeClass(): string
    {
        return match (strtoupper((string) $this->status)) {
            self::STATUS_PENDING_REVIEW => 'bg-warning text-dark border',
            self::STATUS_APPROVED => 'bg-info text-white',
            self::STATUS_PROMOTED => 'bg-success text-white',
            self::STATUS_REJECTED => 'bg-danger text-white',
            default => 'bg-secondary text-white',
        };
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function currentLevel()
    {
        return $this->belongsTo(CareerLevel::class, 'current_level_id');
    }

    public function proposedLevel()
    {
        return $this->belongsTo(CareerLevel::class, 'proposed_level_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function careerHistory()
    {
        return $this->hasOne(CareerHistory::class, 'promotion_request_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PromotionRule::class, 'promotion_rule_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_PROMOTED]);
    }

    public function scopePromoted($query)
    {
        return $query->where('status', self::STATUS_PROMOTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('request_number', 'like', "%{$term}%")
                ->orWhereHas('agent', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('agent_id', 'like', "%{$term}%")
                        ->orWhere('member_code', 'like', "%{$term}%");
                });
        });
    }
}
