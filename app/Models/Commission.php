<?php

namespace App\Models;

use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commission_ledgers';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_PAYABLE = 'PAYABLE';

    public const STATUS_PAID = 'PAID';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'commission_number',
        'commission_id',
        'agent_id',
        'branch_id',
        'customer_id',
        'deposit_id',
        'deposit_scheme_id',
        'deposit_collection_id',
        'collection_payment_id',
        'rule_id',
        'base_amount',
        'rate',
        'commission_rate',
        'calculated_amount',
        'commission_amount',
        'status',
        'period',
        'created_by',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'payment_date',
        'payment_method',
        'payment_reference',
        'reference_number',
        'payment_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'notes',
        'member_code',
        'rank_id',
        'payout_year',
        'payout_remarks',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payout_year' => 'integer',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Commission $commission) {
            if (empty($commission->commission_number)) {
                $commission->commission_number = static::generateCommissionNumber();
            }
            if (empty($commission->commission_id)) {
                $commission->commission_id = $commission->commission_number;
            }
            if (empty($commission->period)) {
                $commission->period = now()->format('Y-m');
            }
            if (empty($commission->status)) {
                $commission->status = self::STATUS_PENDING;
            }
            if (empty($commission->commission_amount) && ! empty($commission->calculated_amount)) {
                $commission->commission_amount = $commission->calculated_amount;
            }
            if (empty($commission->commission_rate) && ! empty($commission->rate)) {
                $commission->commission_rate = $commission->rate;
            }
            if (empty($commission->rate) && ! empty($commission->commission_rate)) {
                $commission->rate = $commission->commission_rate;
            }
            if (empty($commission->calculated_amount) && ! empty($commission->commission_amount)) {
                $commission->calculated_amount = $commission->commission_amount;
            }
            if (empty($commission->created_by) && auth()->check()) {
                $commission->created_by = auth()->id();
            }
            if (empty($commission->member_code) && $commission->agent) {
                $commission->member_code = $commission->agent->member_code ?? $commission->agent->agent_id;
            }
            if (empty($commission->rank_id) && $commission->agent) {
                $commission->rank_id = $commission->agent->rank_id;
            }
        });
    }

    // ─── Dual-column compatibility accessors ──────────────────────────────────

    public function getCommissionNumberAttribute(): ?string
    {
        return $this->attributes['commission_number'] ?? $this->attributes['commission_id'] ?? null;
    }

    public function setCommissionNumberAttribute($value): void
    {
        $this->attributes['commission_number'] = $value;
        if (empty($this->attributes['commission_id'])) {
            $this->attributes['commission_id'] = $value;
        }
    }

    public function getRateAttribute(): float
    {
        return (float) ($this->attributes['commission_rate'] ?? $this->attributes['rate'] ?? 0.00);
    }

    public function setRateAttribute($value): void
    {
        $this->attributes['commission_rate'] = $value;
    }

    public function getCalculatedAmountAttribute(): float
    {
        return (float) ($this->attributes['commission_amount'] ?? $this->attributes['calculated_amount'] ?? 0.00);
    }

    public function setCalculatedAmountAttribute($value): void
    {
        $this->attributes['commission_amount'] = $value;
    }

    public function getReferenceNumberAttribute(): ?string
    {
        return $this->attributes['payment_reference'] ?? null;
    }

    public function setReferenceNumberAttribute($value): void
    {
        $this->attributes['payment_reference'] = $value;
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }

    public function scheme()
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    public function collection()
    {
        return $this->belongsTo(DepositCollection::class, 'deposit_collection_id');
    }

    public function collectionPayment()
    {
        return $this->belongsTo(CollectionPayment::class, 'collection_payment_id');
    }

    public function rule()
    {
        return $this->belongsTo(CommissionRule::class, 'rule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class)
            ->latest();
    }

    // ─── Status Workflow Helpers ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_APPROVED;
    }

    public function isPayable(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_PAYABLE;
    }

    public function isPaid(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_PAID;
    }

    public function isRejected(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_REJECTED;
    }

    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    public function canBeMarkedPayable(): bool
    {
        return $this->isApproved();
    }

    public function canBePaid(): bool
    {
        return $this->isPayable();
    }

    public function getStatusBadgeClass(): string
    {
        return match (strtoupper((string) $this->status)) {
            self::STATUS_PENDING => 'badge-pending',
            self::STATUS_APPROVED => 'badge bg-primary text-white',
            self::STATUS_PAYABLE => 'badge-active',
            self::STATUS_PAID => 'badge-paid',
            self::STATUS_REJECTED => 'badge-rejected',
            default => 'badge bg-light text-dark border',
        };
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('commission_number', 'like', "%{$term}%")
                ->orWhere('commission_id', 'like', "%{$term}%")
                ->orWhere('payment_reference', 'like', "%{$term}%")
                ->orWhereHas('agent', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('agent_id', 'like', "%{$term}%");
                })
                ->orWhereHas('customer', function ($cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%")
                        ->orWhere('customer_number', 'like', "%{$term}%");
                })
                ->orWhereHas('deposit', function ($dq) use ($term) {
                    $dq->where('deposit_number', 'like', "%{$term}%");
                })
                ->orWhereHas('collection', function ($cq) use ($term) {
                    $cq->where('collection_number', 'like', "%{$term}%");
                });
        });
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', strtoupper((string) $status));
    }

    public function scopeByScheme($query, $schemeId)
    {
        return $query->where(function ($q) use ($schemeId) {
            $q->where('deposit_scheme_id', $schemeId)
                ->orWhereHas('deposit', fn ($dq) => $dq->where('deposit_scheme_id', $schemeId));
        });
    }

    public function scopeByPayoutYear($query, $year)
    {
        return $query->where('payout_year', (int) $year);
    }

    // ─── Number Generator ────────────────────────────────────────────────────

    public static function generateCommissionNumber(): string
    {
        return app(NumberingService::class)->generateNext('commission');
    }
}
