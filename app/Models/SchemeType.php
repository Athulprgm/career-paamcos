<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchemeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'scheme_types';

    // Calculation Method Constants
    public const CALC_LUMP_SUM = 'LUMP_SUM';

    public const CALC_RECURRING = 'RECURRING';

    public const CALC_DAILY = 'DAILY';

    public const CALC_SIMPLE = 'SIMPLE';

    public const CALC_DOUBLE = 'DOUBLE';

    protected $fillable = [
        'code',
        'name',
        'description',
        'calculation_type',
        'default_frequency',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function depositSchemes()
    {
        return $this->hasMany(DepositScheme::class, 'scheme_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('calculation_type', 'like', "%{$term}%");
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function getStatusBadgeClass(): string
    {
        return $this->isActive() ? 'badge-active' : 'badge-inactive';
    }

    public static function getCalculationTypes(): array
    {
        return [
            self::CALC_LUMP_SUM => 'Lump-Sum (Fixed / Term Deposit)',
            self::CALC_DOUBLE => 'Double / Doubling (No Interest, Maturity = 2x Principal)',
            self::CALC_RECURRING => 'Recurring Installments (RD)',
            self::CALC_DAILY => 'Daily Pigmy Collection (DD)',
            self::CALC_SIMPLE => 'Simple Term Return',
        ];
    }

    public static function getDefaultFrequencies(): array
    {
        return [
            DepositScheme::FREQ_ONE_TIME => 'One-Time / Lump Sum',
            DepositScheme::FREQ_DAILY => 'Daily',
            DepositScheme::FREQ_WEEKLY => 'Weekly',
            DepositScheme::FREQ_MONTHLY => 'Monthly',
            DepositScheme::FREQ_QUARTERLY => 'Quarterly',
        ];
    }
}
