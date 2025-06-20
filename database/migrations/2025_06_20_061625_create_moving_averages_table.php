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
        Schema::create('moving_averages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id')->index();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('external_item_id');
            $table->unsignedBigInteger('external_id'); // external_id of the entity
            $table->string('bi_monthly_suom');
            $table->string('monthly_suom');
            $table->string('quarterly_suom');
            $table->string('biannual_suom');
            $table->string('annual_suom');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moving_averages');
    }
};
