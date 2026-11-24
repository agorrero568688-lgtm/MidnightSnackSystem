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
        'require_shift' => '1',

        // Online ordering
        'online_ordering_enabled' => '1',
        'orders_paused' => '0',
        'store_hours' => 'Open 24/7, daily',
        'delivery_enabled' => '1',
        'pickup_enabled' => '1',
        'delivery_fee' => '20.00',
        'delivery_free_barangays' => "Magosilom\nLininti-an",
        'free_delivery_threshold' => '0',
        'minimum_order_total' => '0',
        'gcash_name' => '',
        'gcash_number' => '',
        'gcash_qr_path' => '',
        'order_alerts_enabled' => '1',
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',

        // SMS for customers
        'sms_enabled' => '0',
        'sms_gateway_url' => '',
        'sms_gateway_key' => '',
        'sms_sender_name' => 'Diding SMS',
        'sms_reminder_minutes' => '15',
        'sms_feedback_every_n' => '1',
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
