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
        Schema::create('make_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            
            // Forecast Demand Days input
            $table->integer('forecast_demand_days')->nullable();
            // Output fields (usually calculated, can be stored if needed)
            $table->decimal('forecast_amount_ugx', 15, 2)->nullable(); // = summation AG!
            $table->integer('forecast_days_till_next_order')->nullable();
            // Budget-based input
            $table->decimal('order_budget', 15, 2)->nullable(); // UGX
            // Budget-based outputs
            $table->decimal('budget_amount_ugx', 15, 2)->nullable(); // = summation AL!
            $table->integer('budget_days_till_next_order')->nullable();
            $table->integer('external_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('make_orders');
    }
};
