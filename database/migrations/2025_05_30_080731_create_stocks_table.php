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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained('entities');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('store_id')->constrained('stores');
            $table->string('batch_no')->nullable();
            $table->integer('external_id');
            $table->integer("external_store_id")->nullable();
            $table->string("purchase_price")->nullable();
            $table->integer('no_of_sale_units_per_duom')->nullable(); // C!
            $table->integer('qty_sale_units_purchased')->nullable(); // I * C!
            $table->integer('qty'); // Total quantity purchased in DUOM or SUOM
            $table->integer('current_stock_suom')->nullable();
            $table->integer('opening_stock_suom')->nullable();
            $table->integer('closing_stock_suom')->nullable();
            $table->date('date_of_delivery')->nullable();
            $table->integer('stock_aging_days')->nullable();
            $table->integer('lead_time_days')->nullable(); // G! - AR!
            $table->string('suom')->nullable();
            $table->string("duom")->nullable();
            $table->string('suom_per_duom')->nullable();
            $table->string('suom_per_ouom')->nullable();
            $table->string("stock_aging_days")->nullable(); // G! - AR!
            $table->string("lead_time")->nullable(); // G! - AR!

            
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
