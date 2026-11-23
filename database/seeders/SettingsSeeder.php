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
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
