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
        Schema::create('shrinkages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('store_id')->constrained('stores');
            $table->integer('system_qty_suom');
            $table->integer('external_id');
            $table->integer('physical_qty_suom');
            $table->decimal('shrinkage_percentage', 5, 2);
            $table->decimal('shrinkage_amount_ugx', 15, 2);
            $table->date('stock_take_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shrinkages');
    }
};
