<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'branch_id',
        'user_name',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'financial_amount',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'financial_amount' => 'decimal:2',
    ];

    /**
     * Audit records are strictly append-only and immutable.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Sanitize values to ensure sensitive secrets are never logged.
     */
    public static function sanitizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $redactedKeys = [
            'password',
            'password_confirmation',
            'secret',
            'secret_key',
            'token',
            'remember_token',
            'api_key',
            'api_secret',
            'api_token',
            'credentials',
            'access_token',
            'refresh_token',
            'auth_token',
            'auth_key',
            'private_key',
            'webhook_secret',
            'signing_key',
        ];

        $sanitized = [];
        foreach ($values as $key => $val) {
            $lowerKey = strtolower((string) $key);
            $isRedacted = false;
            foreach ($redactedKeys as $redactedKey) {
                if (str_contains($lowerKey, $redactedKey)) {
                    $isRedacted = true;
                    break;
                }
            }

            if ($isRedacted) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($val)) {
                $sanitized[$key] = static::sanitizeValues($val);
            } else {
                $sanitized[$key] = $val;
            }
        }

        return $sanitized;
    }

    /**
     * Record an audit log event.
     */
    public static function record(
        string $event,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?float $amount = null,
        ?int $branchId = null,
        ?User $user = null
    ): self {
        $actor = $user ?? auth()->user();
        $request = request();

        // Automatically deduce branch_id from auditable entity, actor, or profiles
        $resolvedBranchId = $branchId
            ?? $auditable?->branch_id
            ?? $actor?->branch_id
            ?? $actor?->agentProfile?->branch_id
            ?? $actor?->staffProfile?->branch_id;

        $cleanOld = static::sanitizeValues($oldValues);
        $cleanNew = static::sanitizeValues($newValues);

        return static::create([
            'user_id' => $actor?->id,
            'branch_id' => $resolvedBranchId,
            'user_name' => $actor?->name ?? 'System',
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'event' => $event,
            'old_values' => $cleanOld,
            'new_values' => $cleanNew,
            'description' => $description,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'url' => $request ? substr((string) $request->fullUrl(), 0, 500) : null,
            'method' => $request ? $request->method() : 'CLI',
            'financial_amount' => $amount,
        ]);
    }
}
