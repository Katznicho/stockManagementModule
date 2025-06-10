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
        Schema::create('inventory_valuation_reports', function (Blueprint $table) {
            $table->id();
            //  $table->string('item_name');
            $table->foreignId('item_id')->constrained()->onDelete('cascade'); // Foreign key to items table
            $table->integer('external_item_id'); // External ID for the item
            $table->foreignId('entity_id')->constrained('entities');
            $table->integer('external_id');
            $table->integer('current_stock_level')->default(0); // M!
            $table->decimal('price_per_litre', 10, 2)->default(0); // F!
            $table->decimal('conversion_rate', 10, 2)->default(1); // J!
            $table->decimal('inventory_valuation', 12, 2)->default(0); // M!*F!/J!
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_reports');
    }
};
