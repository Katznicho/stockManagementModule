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
        Schema::create('item_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('external_item_id');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('external_id'); // external_id of the entity
            $table->unsignedBigInteger('external_store_id'); // Assuming this is the entity_id of the store
            $table->unsignedInteger('quantity_suom'); // Sale in SUOM
            $table->string('source')->nullable();     // e.g. 'api', 'manual', etc.
            $table->string('reference')->nullable();  // Optional transaction ID
            $table->text('remarks')->nullable();
            $table->string('price')->nullable(); // Assuming price is a float
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_sales');
    }
};
