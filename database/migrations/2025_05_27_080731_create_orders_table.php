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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->integer('external_id');
            $table->integer('order_qty_suom');
            $table->integer('order_qty_ouom');
            $table->string('order_amount_ugx');
            $table->date('order_date');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
