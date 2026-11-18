<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositSchemeCommissionHistory extends Model
{
    use HasFactory;

    protected $table = 'deposit_scheme_commission_histories';

    protected $fillable = [
        'deposit_scheme_id',
        'user_id',
        'action',
        'total_commission_percentage',
        'old_values',
        'new_values',
        'rank_rules_snapshot',
        'notes',
    ];

    protected $casts = [
        'total_commission_percentage' => 'decimal:2',
        'old_values' => 'array',
        'new_values' => 'array',
        'rank_rules_snapshot' => 'array',
    ];

    /**
     * Associated Deposit Scheme.
     */
    public function scheme()
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    /**
     * User who made the change.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
