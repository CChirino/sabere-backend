<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            Cache::forget(self::cacheKey($setting->group, $setting->key));
        });

        static::deleted(function (self $setting) {
            Cache::forget(self::cacheKey($setting->group, $setting->key));
        });
    }

    private static function cacheKey(string $group, string $key): string
    {
        return "settings:group:{$group}:key:{$key}";
    }

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_public',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_public' => 'boolean',
        ];
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeByKey(Builder $query, string $group, string $key): Builder
    {
        return $query->where('group', $group)->where('key', $key);
    }

    public function getCastedValueAttribute(): mixed
    {
        $value = $this->value;

        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'file' => (string) $value,
            'json' => $value,
            default => (string) $value,
        };
    }

    public function fileUrl(): ?string
    {
        if ($this->type !== 'file' || ! $this->value) {
            return null;
        }

        return Storage::disk('public')->url($this->value);
    }

    public static function fileUrlByKey(string $dotKey): ?string
    {
        [$group, $key] = self::parseDotKey($dotKey);

        $setting = static::byKey($group, $key)->first();

        if (! $setting) {
            return null;
        }

        return $setting->fileUrl();
    }

    public static function get(string $dotKey, mixed $default = null): mixed
    {
        [$group, $key] = self::parseDotKey($dotKey);

        return Cache::rememberForever(self::cacheKey($group, $key), function () use ($group, $key, $default) {
            $setting = static::byKey($group, $key)->first();

            if (! $setting) {
                return $default;
            }

            return $setting->casted_value;
        });
    }

    public static function set(string $dotKey, mixed $value, ?string $type = null): self
    {
        [$group, $key] = self::parseDotKey($dotKey);

        $type ??= is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');

        $stored = match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json' => $value,
            default => $value,
        };

        return static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'type' => $type]
        );
    }

    private static function parseDotKey(string $dotKey): array
    {
        $parts = explode('.', $dotKey, 2);

        return [$parts[0], $parts[1] ?? $dotKey];
    }
}
