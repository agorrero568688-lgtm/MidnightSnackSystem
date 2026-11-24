<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('selling_price');
            $table->dropIndex('products_stock_qty_index');
            $table->dropColumn(['stock_qty', 'low_stock_threshold', 'track_stock']);
        });

        Schema::dropIfExists('stock_movements');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('track_stock')->default(true);
            $table->dropColumn('is_available');
            $table->index('stock_qty');
        });

        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 20);
                $table->integer('qty_change');
                $table->integer('qty_after');
                $table->string('reference_type', 50)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'created_at']);
                $table->index('type');
            });
        }
    }
};
