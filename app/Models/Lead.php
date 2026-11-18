<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'leads';

    public const STATUS_NEW = 'NEW';

    public const STATUS_CONTACTED = 'CONTACTED';

    public const STATUS_INTERESTED = 'INTERESTED';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUS_NOT_INTERESTED = 'NOT INTERESTED';

    protected $fillable = [
        'branch_id',
        'agent_id',
        'name',
        'mobile',
        'email',
        'address',
        'interested_scheme_id',
        'expected_amount',
        'status',
        'follow_up_date',
        'notes',
        'converted_customer_id',
        'converted_at',
        'created_by',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'converted_at' => 'datetime',
        'expected_amount' => 'decimal:2',
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

    public function interestedScheme()
    {
        return $this->belongsTo(DepositScheme::class, 'interested_scheme_id');
    }

    public function convertedCustomer()
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'lead_id');
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class, 'lead_id')->latestOfMany();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'badge bg-primary text-white',
            self::STATUS_CONTACTED => 'badge bg-info text-dark',
            self::STATUS_INTERESTED => 'badge bg-warning text-dark',
            self::STATUS_CONVERTED => 'badge bg-success text-white',
            self::STATUS_NOT_INTERESTED => 'badge bg-secondary text-white',
            default => 'badge bg-light text-dark border',
        };
    }

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_INTERESTED => 'Interested',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_NOT_INTERESTED => 'Not Interested',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%")
                ->orWhereHas('branch', function ($b) use ($term) {
                    $b->where('name', 'like', "%{$term}%");
                })
                ->orWhereHas('interestedScheme', function ($s) use ($term) {
                    $s->where('name', 'like', "%{$term}%");
                });
        });
    }

    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
