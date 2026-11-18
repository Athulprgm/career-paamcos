<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPosition extends Model
{
    protected $table = 'positions';

    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'status',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function staff()
    {
        return $this->hasMany(Staff::class, 'position_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }
}
