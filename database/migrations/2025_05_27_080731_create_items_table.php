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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            $table->integer('external_id');
            $table->string('name');
            $table->string('quantity');
            $table->date('date_of_delivery');
            $table->integer('external_id');
            $table->integer("external_item_id");
            $table->string('item_code');
            $table->string("batch_no");
            $table->string('suom')->nullable();
            $table->string('duom')->nullable();
            $table->string('ouom')->nullable();
            $table->string('suom_per_duom')->nullable();
            $table->string('suom_per_ouom')->nullable();
            $table->string('purchase_price')->nullable();
            $table->integer('no_of_sale_units_per_duom'); // C!
            $table->integer('qty_sale_units_purchased'); // I * C!
            $table->integer('qty'); // Total quantity purchased in DUOM or SUOM
            $table->string("lead_time")->nullable();
            $table->string("stock_levels_report")->nullable();
            $table->string("inventory_valuation_report")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
