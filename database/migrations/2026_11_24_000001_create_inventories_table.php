<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('category', 50);
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('reorder_level', 12, 3)->default(0);
            $table->decimal('cost_per_unit', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
