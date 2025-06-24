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
        Schema::create('current_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id')->index();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('external_item_id');
            $table->unsignedBigInteger('external_id'); // external_id of the entity
            $table->string("physical_stock")->default(0);
            $table->string("purchases")->default(0);
            $table->string("sales")->default(0);
            $table->string("transfers")->default(0);
            $table->date("date")->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_stocks');
    }
};
