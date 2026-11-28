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
            'require_shift' => '1',
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
            'order_alert_phones' => '',

            // SMS gateway & customer updates
            'sms_enabled' => '0',
            'sms_gateway_url' => '',
            'sms_gateway_key' => '',
            'sms_sender_name' => 'Diding SMS',
            'sms_reminder_minutes' => '15',
            'sms_feedback_every_n' => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
