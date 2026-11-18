<?php

namespace App\Models;

use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agents';

    protected static function booted(): void
    {
        static::creating(function (Agent $agent) {
            if (empty($agent->agent_id) && empty($agent->member_code)) {
                $code = static::generateAgentId($agent->name, $agent->joining_date);
                $agent->agent_id = $code;
                $agent->member_code = $code;
            } elseif (empty($agent->agent_id)) {
                $agent->agent_id = $agent->member_code;
            } elseif (empty($agent->member_code)) {
                $agent->member_code = $agent->agent_id;
            }
        });

        static::created(function (Agent $agent) {
            if (! empty($agent->agent_id)) {
                app(NumberingService::class)->recordUsage('agent', (string) $agent->agent_id);
            }
        });
    }

    public static function generateAgentId(?string $name = null, $joiningDate = null): string
    {
        return app(NumberingService::class)->generateAgentId($name, $joiningDate);
    }

    protected $fillable = [
        'user_id',
        'member_code',
        'agent_id',
        'name',
        'email',
        'password',
        'phone',
        'address',
        'place',
        'branch_id',
        'rank_id',
        'reporting_staff_id',
        'position_id',
        'parent_agent_id',
        'joining_date',
        'status',
        'profile_image',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'password' => 'hashed',
    ];

    // ─── Accessors & Mutators ─────────────────────────────────────────────────

    public function getProfileImageUrlAttribute(): ?string
    {
        $image = $this->profile_image;

        if (empty($image) && $this->user_id) {
            $user = $this->relationLoaded('user') ? $this->user : $this->user;
            if ($user && $user->profile_image_url) {
                return $user->profile_image_url;
            }
        }

        if (! empty($image)) {
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                return $image;
            }

            if (file_exists(public_path($image))) {
                return asset($image);
            }

            return asset('storage/'.ltrim($image, '/'));
        }

        if ($this->isSuperAdmin()) {
            if (file_exists(public_path('logo.png'))) {
                return asset('logo.png');
            }

            if (file_exists(public_path('logo-transparent.png'))) {
                return asset('logo-transparent.png');
            }
        }

        return null;
    }

    public function isSuperAdmin(): bool
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->isSuperAdmin();
        }

        if ($this->user_id) {
            return (bool) $this->user?->isSuperAdmin();
        }

        return strtolower((string) ($this->email ?? '')) === 'superadmin@multicred.com'
            || strtolower((string) ($this->name ?? '')) === 'super administrator';
    }

    public function getAgentIdAttribute(): ?string
    {
        return $this->attributes['agent_id'] ?? $this->attributes['member_code'] ?? null;
    }

    public function getAgentCodeAttribute(): ?string
    {
        return $this->attributes['agent_id'] ?? $this->attributes['member_code'] ?? null;
    }

    public function setAgentIdAttribute($value): void
    {
        $this->attributes['agent_id'] = $value;
        if (empty($this->attributes['member_code'])) {
            $this->attributes['member_code'] = $value;
        }
    }

    public function setMemberCodeAttribute($value): void
    {
        $this->attributes['member_code'] = $value;
        if (empty($this->attributes['agent_id'])) {
            $this->attributes['agent_id'] = $value;
        }
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function rank()
    {
        return $this->belongsTo(AgentRank::class, 'rank_id');
    }

    public function reportingStaff()
    {
        return $this->belongsTo(Staff::class, 'reporting_staff_id');
    }

    public function parentAgent()
    {
        return $this->belongsTo(Agent::class, 'parent_agent_id');
    }

    public function downlineAgents()
    {
        return $this->hasMany(Agent::class, 'parent_agent_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function collections()
    {
        return $this->hasMany(DepositCollection::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function careerLevel()
    {
        return $this->belongsTo(CareerLevel::class, 'rank_id');
    }

    public function promotionRequests()
    {
        return $this->hasMany(PromotionRequest::class, 'agent_id');
    }

    public function careerHistories()
    {
        return $this->hasMany(CareerHistory::class, 'agent_id')->latest('promotion_date');
    }

    public function activePromotionRequest()
    {
        return $this->hasOne(PromotionRequest::class, 'agent_id')->where('status', PromotionRequest::STATUS_PENDING_REVIEW);
    }

    public function targetAssignments()
    {
        return $this->hasMany(AgentTargetAssignment::class, 'agent_id')->latest('effective_from');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_ACTIVE)
                ->orWhere('status', 'ACTIVE');
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('agent_id', 'like', "%{$term}%")
                ->orWhere('member_code', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_REJECTED;
    }

    public function getStatusBadgeClass(): string
    {
        return match (strtolower((string) $this->status)) {
            self::STATUS_ACTIVE => 'badge-active',
            self::STATUS_PENDING => 'badge-pending',
            self::STATUS_REJECTED => 'badge-inactive',
            default => 'badge-inactive',
        };
    }
}
