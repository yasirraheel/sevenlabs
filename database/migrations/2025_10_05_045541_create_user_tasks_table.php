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
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_id')->unique(); // SevenLabs task ID
            $table->unsignedBigInteger('user_id');
            $table->text('input_text'); // The text that was converted
            $table->string('voice_id'); // Voice used
            $table->string('voice_name')->nullable(); // Voice name for display
            $table->integer('text_length'); // Character count
            $table->integer('credits_used'); // Credits consumed
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->text('result_url')->nullable(); // Audio file URL
            $table->text('subtitle_url')->nullable(); // Subtitle file URL
            $table->text('error_message')->nullable(); // Error if failed
            $table->timestamp('completed_at')->nullable(); // When task completed
            $table->timestamps();

            // Foreign key constraint (commented out for now)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index('task_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};
