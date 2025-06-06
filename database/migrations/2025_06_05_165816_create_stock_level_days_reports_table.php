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
        Schema::create('stock_level_days_reports', function (Blueprint $table) {
            $table->id();
            // $table->string('item_name');
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->integer('external_item_id'); // External ID for the item
            $table->integer('current_stock_level')->default(0); // M!
            $table->decimal('daily_sales', 10, 2)->default(0);   // V!
            $table->decimal('average_sales', 10, 2)->default(0); // AA!
            $table->decimal('stock_level_days', 10, 2)->default(0); // M! / (V! or AA!)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_level_days_reports');
    }
};
