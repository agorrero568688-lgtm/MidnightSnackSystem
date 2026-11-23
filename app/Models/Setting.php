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

        // Online ordering
        'online_ordering_enabled' => '1',
        'orders_paused' => '0',
        'store_hours' => 'Open 24/7, daily',
        'delivery_enabled' => '1',
        'pickup_enabled' => '1',
        'delivery_fee' => '39.00',
        'free_delivery_threshold' => '0',
        'minimum_order_total' => '0',
        'gcash_name' => '',
        'gcash_number' => '',
        'gcash_qr_path' => '',
        'order_alerts_enabled' => '1',
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
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
