<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositSchemeMrpRate extends Model
{
    use HasFactory;

    protected $table = 'deposit_scheme_mrp_rates';

    protected $fillable = [
        'deposit_scheme_id',
        'mrp_configuration_id',
        'year',
        'general_rate',
        'general_monthly_return',
        'senior_rate',
        'senior_monthly_return',
    ];

    protected $casts = [
        'year' => 'integer',
        'general_rate' => 'decimal:2',
        'general_monthly_return' => 'decimal:2',
        'senior_rate' => 'decimal:2',
        'senior_monthly_return' => 'decimal:2',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(DepositSchemeMrpConfiguration::class, 'mrp_configuration_id');
    }
}
