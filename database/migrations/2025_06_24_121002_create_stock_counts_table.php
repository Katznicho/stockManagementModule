<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     *             // 'physical_stock_suom' => $request->input('physical_stock_suom'),

     */
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id')->index();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('external_item_id');
            $table->unsignedBigInteger('external_id'); // external_id of the entity
            $table->date("date")->index();
            $table->decimal('physical_stock_suom', 10, 2)->default(0.00);
            $table->decimal('damaged_stock_suom', 10, 2)->default(0.00);  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
