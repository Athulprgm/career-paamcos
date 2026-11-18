<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Cache for settings loaded within the current process.
     *
     * @var array<string, mixed>
     */
    protected static array $runtimeCache = [];

    /**
     * Clear runtime cache.
     */
    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = [];
    }

    /**
     * Get typed value of this setting instance.
     */
    public function getTypedValueAttribute(): mixed
    {
        return self::castValue($this->value, $this->type);
    }

    /**
     * Convert raw string value to typed PHP variable.
     */
    public static function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'float', 'double', 'decimal' => (float) $value,
            'json', 'array' => is_array($value) ? $value : json_decode((string) $value, true),
            default => (string) $value,
        };
    }

    /**
     * Convert typed PHP variable to string for database storage.
     */
    public static function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Get a setting by key, with optional default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        $typed = $setting->typed_value;
        static::$runtimeCache[$key] = $typed;

        return $typed;
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Set or update a setting value.
     */
    public static function set(
        string $key,
        mixed $value,
        ?string $type = null,
        ?string $group = null,
        ?string $description = null
    ): self {
        $setting = static::where('key', $key)->first();

        $effectiveType = $type ?? $setting?->type ?? 'string';
        $serialized = self::serializeValue($value, $effectiveType);

        if ($setting) {
            $updateData = ['value' => $serialized];
            if ($type !== null) {
                $updateData['type'] = $type;
            }
            if ($group !== null) {
                $updateData['group'] = $group;
            }
            if ($description !== null) {
                $updateData['description'] = $description;
            }
            $setting->update($updateData);
        } else {
            $effectiveGroup = $group ?? (str_contains($key, '.') ? explode('.', $key)[0] : 'system');
            $setting = static::create([
                'key' => $key,
                'value' => $serialized,
                'type' => $effectiveType,
                'group' => $effectiveGroup,
                'description' => $description,
            ]);
        }

        static::$runtimeCache[$key] = self::castValue($serialized, $effectiveType);

        return $setting;
    }

    /**
     * Get all settings in a group as key-value array.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->typed_value;
            static::$runtimeCache[$setting->key] = $setting->typed_value;
        }

        return $result;
    }

    /**
     * Get collection of Setting models in a group.
     *
     * @return Collection<int, self>
     */
    public static function getGroupSettings(string $group): Collection
    {
        return static::where('group', $group)->orderBy('id')->get();
    }
}
