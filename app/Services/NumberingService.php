<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CollectionPayment;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\PromotionRequest;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NumberingService
{
    /**
     * Map of entity types to their models and identification columns.
     */
    protected const ENTITY_MAP = [
        'agent' => [
            'model' => Agent::class,
            'columns' => ['agent_id', 'member_code'],
            'default_prefix' => 'PAG',
            'default_digits' => 6,
        ],
        'customer' => [
            'model' => Customer::class,
            'columns' => ['customer_number'],
            'default_prefix' => 'CUST',
            'default_digits' => 6,
        ],
        'deposit' => [
            'model' => Deposit::class,
            'columns' => ['deposit_number', 'deposit_id'],
            'default_prefix' => 'DEP',
            'default_digits' => 6,
        ],
        'collection' => [
            'model' => DepositCollection::class,
            'columns' => ['collection_number'],
            'default_prefix' => 'COL',
            'default_digits' => 6,
        ],
        'commission' => [
            'model' => Commission::class,
            'columns' => ['commission_number', 'commission_id'],
            'default_prefix' => 'COM',
            'default_digits' => 6,
        ],
        'promotion' => [
            'model' => PromotionRequest::class,
            'columns' => ['request_number'],
            'default_prefix' => 'PR',
            'default_digits' => 6,
        ],
        'payment' => [
            'model' => CollectionPayment::class,
            'columns' => ['payment_number'],
            'default_prefix' => 'PAY',
            'default_digits' => 6,
        ],
    ];

    /**
     * Get list of supported entity types.
     *
     * @return array<string, string>
     */
    public function getSupportedTypes(): array
    {
        return [
            'agent' => 'Agent Member Code',
            'customer' => 'Customer Number',
            'deposit' => 'Deposit Account Number',
            'collection' => 'Collection Receipt Number',
            'commission' => 'Commission Voucher Number',
            'promotion' => 'Promotion Request Number',
            'payment' => 'Collection Payment Number',
        ];
    }

    /**
     * Generate the next unique sequential number for an entity type.
     * Thread-safe, transaction-locked, duplicate-preventing.
     */
    public function generateNext(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type === 'agent') {
            return $this->generateAgentId();
        }

        if (! array_key_exists($type, self::ENTITY_MAP)) {
            throw new InvalidArgumentException("Unsupported numbering entity type: {$type}");
        }

        $config = self::ENTITY_MAP[$type];

        return DB::transaction(function () use ($type, $config) {
            // 1. Lock the sequence setting row for update
            $nextSetting = Setting::where('key', "numbering.{$type}_next")->lockForUpdate()->first();
            $prefixSetting = Setting::where('key', "numbering.{$type}_prefix")->first();
            $digitsSetting = Setting::where('key', "numbering.{$type}_digits")->first();

            $prefix = strtoupper(trim((string) ($prefixSetting?->value ?? $config['default_prefix'])));
            $digits = max(3, min(12, (int) ($digitsSetting?->value ?? $config['default_digits'])));
            $currentSequence = max(1, (int) ($nextSetting?->value ?? 1));

            // 2. Loop until finding an unused unique candidate in the target table
            $candidate = '';
            $found = false;
            $sequence = $currentSequence;

            while (! $found) {
                $candidate = $prefix.str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT);

                // Check collision against database table
                $exists = $this->candidateExistsInDatabase($config['model'], $config['columns'], $candidate);

                if (! $exists) {
                    $found = true;
                } else {
                    $sequence++;
                }
            }

            // 3. Increment sequence for future calls
            $nextSequenceValue = $sequence + 1;
            if ($nextSetting) {
                $nextSetting->update(['value' => (string) $nextSequenceValue]);
            } else {
                Setting::create([
                    'key' => "numbering.{$type}_next",
                    'value' => (string) $nextSequenceValue,
                    'type' => 'integer',
                    'group' => 'numbering',
                    'description' => "Next sequence counter for {$type}",
                ]);
            }

            Setting::clearRuntimeCache();

            return $candidate;
        });
    }

    /**
     * Alias for generateNext to support legacy and convenience calls.
     */
    public function getNext(string $type): string
    {
        return $this->generateNext($type);
    }

    /**
     * Preview what the next generated number will look like without incrementing sequence.
     * Checks database for collisions to ensure the previewed candidate is available.
     */
    public function previewNext(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type === 'agent') {
            return $this->previewAgentId();
        }

        if (! array_key_exists($type, self::ENTITY_MAP)) {
            throw new InvalidArgumentException("Unsupported numbering entity type: {$type}");
        }

        $config = self::ENTITY_MAP[$type];
        $prefix = strtoupper(trim((string) Setting::get("numbering.{$type}_prefix", $config['default_prefix'])));
        $digits = max(3, min(12, (int) Setting::get("numbering.{$type}_digits", $config['default_digits'])));
        $sequence = max(1, (int) Setting::get("numbering.{$type}_next", 1));

        while ($this->candidateExistsInDatabase($config['model'], $config['columns'], $prefix.str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT))) {
            $sequence++;
        }

        return $prefix.str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Generate formatted unique Agent ID: PAG + MM + YY + sequence
     *
     * Structure:
     * PA  - PAAMCOS Short PA
     * AG  - Agent Short AG
     * 2 A - Convert To Single A -> PAG
     * MM  - join month (2 digits, e.g. 11)
     * YY  - join year (2 digits, e.g. 26)
     * Seq - normal number count (1, 2, 3...)
     *
     * Example: PAG11261
     */
    public function generateAgentId(?string $name = null, CarbonInterface|string|null $joiningDate = null): string
    {
        $prefix = $this->buildAgentPrefix($name, $joiningDate);

        return DB::transaction(function () use ($prefix) {
            $seq = 1;
            $found = false;
            $candidate = '';

            while (! $found) {
                $candidate = $prefix.$seq;
                $exists = $this->candidateExistsInDatabase(Agent::class, ['agent_id', 'member_code'], $candidate);
                if (! $exists) {
                    $found = true;
                } else {
                    $seq++;
                }
            }

            return $candidate;
        });
    }

    /**
     * Preview formatted unique Agent ID: PAG + MM + YY + sequence
     */
    public function previewAgentId(?string $name = null, CarbonInterface|string|null $joiningDate = null): string
    {
        $prefix = $this->buildAgentPrefix($name, $joiningDate);
        $seq = 1;

        while ($this->candidateExistsInDatabase(Agent::class, ['agent_id', 'member_code'], $prefix.$seq)) {
            $seq++;
        }

        return $prefix.$seq;
    }

    /**
     * Build prefix for Agent ID: PAG + MM + YY
     *
     * Structure:
     * PA  - PAAMCOS Short PA
     * AG  - Agent Short AG
     * 2 A - Convert To Single A -> PAG
     * MM  - join month (2 digits, e.g. 11)
     * YY  - join year (2 digits, e.g. 26)
     */
    public function buildAgentPrefix(?string $name = null, CarbonInterface|string|null $joiningDate = null): string
    {
        try {
            $date = $joiningDate ? Carbon::parse($joiningDate) : now();
        } catch (\Throwable) {
            $date = now();
        }

        $month = $date->format('m');
        $year = $date->format('y');

        return 'PAG'.$month.$year;
    }

    /**
     * Record usage of a manual or assigned number to ensure sequence settings remain ahead.
     */
    public function recordUsage(string $type, string $number): void
    {
        $type = strtolower(trim($type));
        if (! array_key_exists($type, self::ENTITY_MAP)) {
            return;
        }

        $config = self::ENTITY_MAP[$type];
        $prefix = strtoupper(trim((string) Setting::get("numbering.{$type}_prefix", $config['default_prefix'])));

        $cleanNumber = strtoupper(trim($number));
        if (str_starts_with($cleanNumber, $prefix)) {
            $numPart = substr($cleanNumber, strlen($prefix));
            if (ctype_digit($numPart)) {
                $usedSeq = (int) $numPart;
                $currentNext = max(1, (int) Setting::get("numbering.{$type}_next", 1));
                if ($usedSeq >= $currentNext) {
                    Setting::set("numbering.{$type}_next", $usedSeq + 1, 'integer', 'numbering');
                }
            }
        }
    }

    /**
     * Update configuration for an entity type.
     */
    public function configure(string $type, string $prefix, int $digits, int $startingSequence): void
    {
        $type = strtolower(trim($type));
        if (! array_key_exists($type, self::ENTITY_MAP)) {
            throw new InvalidArgumentException("Unsupported numbering entity type: {$type}");
        }

        $cleanPrefix = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $prefix));
        $cleanDigits = max(3, min(12, $digits));
        $cleanSequence = max(1, $startingSequence);

        Setting::set("numbering.{$type}_prefix", $cleanPrefix, 'string', 'numbering');
        Setting::set("numbering.{$type}_digits", $cleanDigits, 'integer', 'numbering');
        Setting::set("numbering.{$type}_next", $cleanSequence, 'integer', 'numbering');
    }

    /**
     * Helper to check if candidate number exists in target model.
     *
     * @param  class-string  $modelClass
     * @param  string[]  $columns
     */
    protected function candidateExistsInDatabase(string $modelClass, array $columns, string $candidate): bool
    {
        $query = $modelClass::query();

        // Include soft-deleted records if supported by model
        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }

        $query->where(function ($sub) use ($columns, $candidate) {
            foreach ($columns as $index => $col) {
                if ($index === 0) {
                    $sub->where($col, $candidate);
                } else {
                    $sub->orWhere($col, $candidate);
                }
            }
        });

        return $query->exists();
    }
}
