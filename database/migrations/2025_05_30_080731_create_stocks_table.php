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
            $table->string('batch_no');
            $table->integer('external_id');
             
            $table->string("purchase_price")->nullable();

            $table->string('duom'); // Delivery Unit of Measure
            $table->string('suom'); // Sale Unit of Measure

            $table->integer('no_of_sale_units_per_duom'); // C!
            $table->integer('qty_sale_units_purchased'); // I * C!
            $table->integer('qty'); // Total quantity purchased in DUOM or SUOM
        
            $table->integer('current_stock_suom');
            $table->integer('opening_stock_suom');
            $table->integer('closing_stock_suom');
        
            $table->date('date_of_delivery');
            $table->integer('stock_aging_days');
            $table->integer('lead_time_days')->nullable(); // G! - AR!

            $table->string('suom');
            $table->string("duom");
            $table->string("duom");
            $table->string('suom_per_duom');
            $table->string('suom_per_ouom');

            
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
