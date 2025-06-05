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
            $table->string('name');
            $table->integer('external_id');
            $table->integer("external_item_id");
            $table->string('item_code');
            $table->string('suom')->nullable();
            $table->string('duom')->nullable();
            $table->string('ouom')->nullable();
            $table->string('suom_per_duom')->nullable();
            $table->string('suom_per_ouom')->nullable();
            $table->string('purchase_price')->nullable();
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
