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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_entity_id')->constrained('entities');
            $table->foreignId('to_entity_id')->nullable()->constrained('entities');
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('from_store_id')->constrained('stores');
            $table->foreignId('to_store_id')->constrained('stores');
            $table->integer('external_id');
            $table->integer('qty_requested_suom');
            $table->integer('qty_approved_suom');
            $table->integer('qty_received_suom');
            $table->string('reason');
            $table->string('status');
            $table->foreignId('requesting_user_id')->constrained('entity_users');
            $table->foreignId('approving_user_id')->nullable()->constrained('entity_users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
