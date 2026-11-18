<?php

namespace App\Models;

use App\Services\NumberingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepositCollection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'deposit_collections';

    // Status Constants
    public const STATUS_DUE = 'DUE';

    public const STATUS_PARTIAL = 'PARTIAL';

    public const STATUS_PAID = 'PAID';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_CANCELLED = 'CANCELLED';

    // Payment Methods
    public const METHOD_CASH = 'CASH';

    public const METHOD_UPI = 'UPI';

    public const METHOD_CHEQUE = 'CHEQUE';

    public const METHOD_BANK_TRANSFER = 'BANK_TRANSFER';

    public const METHOD_NET_BANKING = 'NET_BANKING';

    public const METHOD_OTHER = 'OTHER';

    // Verification Status Constants
    public const VERIFY_UNVERIFIED = 'UNVERIFIED';

    public const VERIFY_VERIFIED = 'VERIFIED';

    public const VERIFY_FLAGGED = 'FLAGGED';

    protected $fillable = [
        'collection_number',
        'deposit_id',
        'customer_id',
        'branch_id',
        'agent_id',
        'installment_number',
        'due_date',
        'due_amount',
        'paid_amount',
        'collection_date',
        'payment_method',
        'reference_number',
        'collected_by',
        'status',
        'notes',
        'verified_by',
        'verified_at',
        'verification_status',
        'verification_remarks',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'due_date' => 'date',
        'collection_date' => 'date',
        'due_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'installment_number' => 'integer',
        'verified_at' => 'datetime',
        'is_flagged' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DepositCollection $collection) {
            if (empty($collection->collection_number)) {
                $collection->collection_number = static::generateCollectionNumber();
            }
        });
    }

    // ─── Accessors & Helpers ──────────────────────────────────────────────────

    public function getOutstandingAmountAttribute(): float
    {
        $due = (float) $this->due_amount;
        $paid = (float) $this->paid_amount;

        return max(0.00, round($due - $paid, 2));
    }

    public function isPaid(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_PAID;
    }

    public function isDue(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_DUE;
    }

    public function isPartial(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_PARTIAL;
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->isCancelled()) {
            return false;
        }

        if (strtoupper((string) $this->status) === self::STATUS_OVERDUE) {
            return true;
        }

        return $this->due_date && $this->due_date->lt(Carbon::today()) && $this->outstanding_amount > 0;
    }

    public function isCancelled(): bool
    {
        return strtoupper((string) $this->status) === self::STATUS_CANCELLED;
    }

    /**
     * Compute current valid status based on financial and date criteria.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->isCancelled()) {
            return self::STATUS_CANCELLED;
        }

        if ($this->isPaid() || $this->paid_amount >= $this->due_amount) {
            return self::STATUS_PAID;
        }

        if ($this->due_date && $this->due_date->lt(Carbon::today()) && $this->outstanding_amount > 0) {
            return self::STATUS_OVERDUE;
        }

        if ($this->paid_amount > 0 && $this->paid_amount < $this->due_amount) {
            return self::STATUS_PARTIAL;
        }

        return self::STATUS_DUE;
    }

    public function getStatusBadgeClass(): string
    {
        $status = $this->effective_status;

        return match ($status) {
            self::STATUS_PAID => 'badge bg-success text-white',
            self::STATUS_DUE => 'badge bg-warning text-dark',
            self::STATUS_PARTIAL => 'badge bg-info text-white',
            self::STATUS_OVERDUE => 'badge bg-danger text-white',
            self::STATUS_CANCELLED => 'badge bg-secondary text-white',
            default => 'badge bg-light text-dark border',
        };
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return strtoupper((string) $this->verification_status) === self::VERIFY_VERIFIED;
    }

    public function isFlagged(): bool
    {
        return (bool) $this->is_flagged || strtoupper((string) $this->verification_status) === self::VERIFY_FLAGGED;
    }

    public function canBeVerified(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_PARTIAL]) && ! $this->isVerified();
    }

    public function getVerificationBadgeClass(): string
    {
        return match (strtoupper((string) $this->verification_status)) {
            self::VERIFY_VERIFIED => 'badge bg-success text-white',
            self::VERIFY_FLAGGED => 'badge bg-danger text-white',
            default => 'badge bg-secondary text-white',
        };
    }

    public function payments()
    {
        return $this->hasMany(CollectionPayment::class, 'deposit_collection_id')->latest();
    }

    public function commission()
    {
        return $this->hasOne(Commission::class, 'deposit_collection_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'deposit_collection_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class)
            ->latest();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('collection_number', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%")
                ->orWhereHas('deposit', function ($dq) use ($term) {
                    $dq->where('deposit_number', 'like', "%{$term}%");
                })
                ->orWhereHas('customer', function ($cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%")
                        ->orWhere('customer_number', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%");
                })
                ->orWhereHas('agent', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('agent_id', 'like', "%{$term}%");
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

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDeposit($query, $depositId)
    {
        return $query->where('deposit_id', $depositId);
    }

    public function scopeDueOnly($query)
    {
        return $query->whereIn('status', [self::STATUS_DUE, self::STATUS_PARTIAL])
            ->where('due_date', '>=', Carbon::today()->toDateString())
            ->whereRaw('paid_amount < due_amount');
    }

    public function scopeTodayDue($query)
    {
        return $query->whereDate('due_date', Carbon::today()->toDateString());
    }

    public function scopeOverdueOnly($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_OVERDUE)
                ->orWhere(function ($sub) {
                    $sub->whereIn('status', [self::STATUS_DUE, self::STATUS_PARTIAL])
                        ->where('due_date', '<', Carbon::today()->toDateString())
                        ->whereRaw('paid_amount < due_amount');
                });
        })->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    // ─── Number Generator ────────────────────────────────────────────────────

    public static function generateCollectionNumber(): string
    {
        return app(NumberingService::class)->generateNext('collection');
    }

    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_UPI => 'UPI / QR',
            self::METHOD_CHEQUE => 'Cheque / DD',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer / NEFT / IMPS',
            self::METHOD_NET_BANKING => 'Net Banking',
            self::METHOD_OTHER => 'Other',
        ];
    }
}
