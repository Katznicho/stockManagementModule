<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ^By4rGmb&P
     */
    public function up(): void
    {
        // database/migrations/xxxx_xx_xx_create_logs_table.php

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('entity_users')->nullOnDelete();
            $table->integer('external_id');
            
            $table->string('action'); // e.g., created, updated, deleted
            $table->string('model_type'); // App\Models\Stock
            $table->unsignedBigInteger('model_id'); // 1
            $table->json('changed_data')->nullable(); // store old/new values
            $table->text('description')->nullable(); // optional description
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
