<?php

namespace App\Models;

use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    // Customer Status constants
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    protected $fillable = [
        'customer_number',
        'name',
        'customer_type',
        'email',
        'mobile',
        'date_of_birth',
        'address',
        'pin_code',
        'branch_id',
        'agent_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function kyc()
    {
        return $this->hasOne(CustomerKyc::class, 'customer_id');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'customer_id');
    }

    public function collections()
    {
        return $this->hasMany(DepositCollection::class, 'customer_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'converted_customer_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'auditable_id')
            ->where('auditable_type', self::class)
            ->latest();
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'badge-active',
            self::STATUS_REJECTED => 'badge-inactive',
            self::STATUS_INACTIVE => 'badge bg-secondary text-white',
            default => 'badge bg-warning text-dark border',
        };
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('customer_number', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
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

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ─── Number Generator ────────────────────────────────────────────────────

    public static function generateCustomerNumber(): string
    {
        return app(NumberingService::class)->generateNext('customer');
    }
}
