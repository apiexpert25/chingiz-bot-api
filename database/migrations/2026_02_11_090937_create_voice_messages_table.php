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
        Schema::create('voice_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('voice_id')->unique();
            $table->bigInteger('telegram_id');
            $table->enum('status', ['started', 'completed'])->default('started');
            $table->string('voice_download_link')->nullable();
            $table->timestamps();

            // Index for daily limit queries
            $table->index(['telegram_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_messages');
    }
};
