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
        Schema::create('item_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key to items table
            $table->integer('external_item_id'); // External ID for the item
            $table->foreignId('entity_id')->constrained('entities');
            $table->integer('external_id');
            $table->string("name");
            $table->string('daily_consumption')->nullable();
            $table->integer('safety_stock_days')->nullable();
            $table->string('buffer_stock')->default(0);
            $table->string('opening_stock')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_settings');
    }
};
