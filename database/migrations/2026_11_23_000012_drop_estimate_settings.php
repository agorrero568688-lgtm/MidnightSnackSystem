<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->whereIn('key', ['delivery_estimate', 'pickup_estimate'])->delete();
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'delivery_estimate')->updateOrInsert(['key' => 'delivery_estimate'], ['value' => '30–45 min']);
        DB::table('settings')->where('key', 'pickup_estimate')->updateOrInsert(['key' => 'pickup_estimate'], ['value' => '15–20 min']);
    }
};
