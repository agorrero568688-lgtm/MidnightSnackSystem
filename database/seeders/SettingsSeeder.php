<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store_name' => "Diding's Midnight Snack",
            'store_address' => 'Poblacion, Philippines',
            'store_phone' => '',
            'receipt_footer' => 'Salamat po! Come again.',
            'receipt_width' => '58',
            'allow_negative_stock' => '0',
            'low_stock_default' => '5',
            'require_shift' => '1',
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

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
