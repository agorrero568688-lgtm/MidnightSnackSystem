<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('image_path')->nullable()->after('description');
            $table->boolean('is_online')->default(true)->after('is_active');
            $table->unsignedInteger('sort_order')->default(0)->after('is_online');

            $table->index(['is_online', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_online', 'sort_order']);
            $table->dropColumn(['description', 'image_path', 'is_online', 'sort_order']);
        });
    }
};
