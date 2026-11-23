<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('channel', 20)->default('pos')->after('payment_method');
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('discount_amount');

            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropColumn(['channel', 'delivery_fee']);
        });
    }
};
