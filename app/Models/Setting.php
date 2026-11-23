<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public const DEFAULTS = [
        'store_name' => "Diding's Midnight Snack",
        'store_address' => '',
        'store_phone' => '',
        'receipt_footer' => 'Salamat po! Come again.',
        'receipt_width' => '58',
        'allow_negative_stock' => '0',
        'low_stock_default' => '5',
        'require_shift' => '1',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Cache::rememberForever('setting.'.$key, function () use ($key) {
            return static::query()->where('key', $key)->value('value');
        });

        return $value ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function bool(string $key): bool
    {
        return in_array(static::get($key), ['1', 'true', 'on', 'yes'], true);
    }

    public static function int(string $key): int
    {
        return (int) static::get($key);
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget('setting.'.$key);
    }

    /** @param array<string, string|null> $values */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::set($key, $value);
        }
    }
}
