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
        Schema::create('make_order_settings', function (Blueprint $table) {
            $table->id();
            // Parameter type: either forecast days or budget
            $table->enum('parameter', ['forecast_demand_days', 'budget'])->comment('Order type: forecast_demand_days or budget');
             $table->unsignedBigInteger('external_id')->index();
            // User-defined value for the order setting
            $table->string('value')->nullable();
            // Amount in days until next order, calculated or defined
            $table->integer('amount_days_until_next_order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('make_order_settings');
    }
};
