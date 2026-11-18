<?php

namespace App\Models;

use App\Services\NumberingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionPayment extends Model
{
    use HasFactory;

    protected $table = 'collection_payments';

    protected $fillable = [
        'payment_number',
        'deposit_collection_id',
        'deposit_id',
        'customer_id',
        'branch_id',
        'agent_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'collected_by',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CollectionPayment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function collection()
    {
        return $this->belongsTo(DepositCollection::class, 'deposit_collection_id');
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'deposit_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // ─── Generator ────────────────────────────────────────────────────────────

    public static function generatePaymentNumber(): string
    {
        return app(NumberingService::class)->generateNext('payment');
    }
}
