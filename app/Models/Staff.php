<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'staff_code',
        'employee_id',
        'name',
        'email',
        'phone',
        'branch_id',
        'position_id',
        'reporting_manager_id',
        'joining_date',
        'permissions',
        'status',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'permissions' => 'array',
    ];

    // ─── Accessors & Mutators ─────────────────────────────────────────────────

    public function getEmployeeIdAttribute(): ?string
    {
        return $this->attributes['employee_id'] ?? $this->attributes['staff_code'] ?? null;
    }

    public function setEmployeeIdAttribute($value): void
    {
        $this->attributes['employee_id'] = $value;
        if (empty($this->attributes['staff_code'])) {
            $this->attributes['staff_code'] = $value;
        }
    }

    public function setStaffCodeAttribute($value): void
    {
        $this->attributes['staff_code'] = $value;
        if (empty($this->attributes['employee_id'])) {
            $this->attributes['employee_id'] = $value;
        }
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function position()
    {
        return $this->belongsTo(StaffPosition::class, 'position_id');
    }

    public function reportingManager()
    {
        return $this->belongsTo(Staff::class, 'reporting_manager_id');
    }

    public function directReports()
    {
        return $this->hasMany(Staff::class, 'reporting_manager_id');
    }

    public function reportingAgents()
    {
        return $this->hasMany(Agent::class, 'reporting_staff_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('employee_id', 'like', "%{$term}%")
                ->orWhere('staff_code', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
