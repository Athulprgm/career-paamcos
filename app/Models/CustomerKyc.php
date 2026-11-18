<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerKyc extends Model
{
    use HasFactory;

    protected $table = 'customer_kyc';

    // KYC Status constants
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CORRECTION_REQUESTED = 'CORRECTION_REQUESTED';

    protected $fillable = [
        'customer_id',
        'id_type',
        'id_number',
        'id_document_path',
        'address_proof_path',
        'status',
        'rejection_reason',
        'remarks',
        'submitted_at',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCorrectionRequested(): bool
    {
        return $this->status === self::STATUS_CORRECTION_REQUESTED;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'badge-active',
            self::STATUS_REJECTED => 'badge-inactive',
            self::STATUS_UNDER_REVIEW => 'badge bg-warning text-dark border',
            self::STATUS_CORRECTION_REQUESTED => 'badge bg-info text-dark border',
            default => 'badge bg-secondary text-white',
        };
    }
}
