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
        Schema::create('order_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('notification_to_order_days')->nullable();
            $table->foreignId('entity_id')->constrained('entities');
            $table->integer('external_id');
            $table->decimal('anticipated_peak_period_percentage', 5, 2)->nullable();
            $table->decimal('expected_increase_during_peak', 5, 2)->nullable();
            $table->decimal('shrinkage_total_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_settings');
    }
};
