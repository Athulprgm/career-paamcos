<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Branch
 */
class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function admins()
    {
        return $this->hasMany(User::class)->whereHas('roles', fn ($q) => $q->where('slug', 'admin'));
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
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

    public function promotionRequests()
    {
        return $this->hasMany(PromotionRequest::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
