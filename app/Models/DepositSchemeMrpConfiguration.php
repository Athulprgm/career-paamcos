<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepositSchemeMrpConfiguration extends Model
{
    use HasFactory;

    protected $table = 'deposit_scheme_mrp_configurations';

    public const METHOD_STANDARD = 'STANDARD';

    public const METHOD_BROCHURE_830 = 'BROCHURE_FLAT_10';

    public const METHOD_ROUND_INTEGER = 'ROUND_INTEGER';

    protected $fillable = [
        'deposit_scheme_id',
        'is_enabled',
        'contribution_amount',
        'min_tenure',
        'max_tenure',
        'tenure_unit',
        'general_rate',
        'senior_rate',
        'calculation_method',
        'notes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'contribution_amount' => 'decimal:2',
        'min_tenure' => 'integer',
        'max_tenure' => 'integer',
        'general_rate' => 'decimal:2',
        'senior_rate' => 'decimal:2',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(DepositScheme::class, 'deposit_scheme_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(DepositSchemeMrpRate::class, 'mrp_configuration_id')->orderBy('year');
    }

    /**
     * Compute monthly return decimal-safely.
     * Default: (Contribution * Rate) / 1200
     *
     * Example at ₹1,00,000 contribution:
     * - 10.00% General Rate -> Standard: ₹833.33 / Brochure: ₹830.00
     * - 10.50% Senior Rate  -> Standard: ₹875.00 / Brochure: ₹870.00
     */
    public static function calculateMonthlyReturn(float $contribution, float $rate, string $method = self::METHOD_STANDARD): float
    {
        if ($contribution <= 0 || $rate <= 0) {
            return 0.00;
        }

        $exact = ($contribution * $rate) / 1200.0;

        return match ($method) {
            self::METHOD_BROCHURE_830 => floor($exact / 10.0) * 10.0,
            self::METHOD_ROUND_INTEGER => round($exact),
            default => round($exact, 2),
        };
    }
}
