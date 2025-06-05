<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('store_id')->constrained('stores');
            $table->integer('external_id');
            $table->decimal('bi_monthly_avg_suom', 12, 2);
            $table->decimal('monthly_avg_suom', 12, 2);
            $table->decimal('quarterly_avg_suom', 12, 2);
            $table->decimal('biannual_avg_suom', 12, 2);
            $table->decimal('annual_avg_suom', 12, 2);
            $table->decimal('fixed_avg_suom', 12, 2);
            $table->integer('safety_stock_days');
            $table->decimal('safety_stock_suom', 12, 2);
            $table->integer('buffer_stock_days');
            $table->decimal('buffer_stock_suom', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_metrics');
    }
};
