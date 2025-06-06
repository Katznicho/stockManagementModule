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
        Schema::create('product_stock_levels', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->integer("external_item_id");
            $table->integer('opening_stock')->default(0);
            $table->integer('deliveries_to_date')->default(0);
            $table->integer('sales_to_date')->default(0);
            $table->integer('returns')->default(0);
            $table->integer('external_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock_levels');
    }
};
