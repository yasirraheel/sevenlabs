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
        Schema::table('user_tasks', function (Blueprint $table) {
            // Drop unnecessary columns, keep only essential ones
            $table->dropColumn([
                'input_text',
                'voice_id', 
                'voice_name',
                'model',
                'text_length',
                'status',
                'result_url',
                'subtitle_url',
                'error_message',
                'completed_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_tasks', function (Blueprint $table) {
            // Restore columns if needed
            $table->text('input_text')->nullable();
            $table->string('voice_id')->nullable();
            $table->string('voice_name')->nullable();
            $table->string('model')->nullable();
            $table->integer('text_length')->nullable();
            $table->string('status')->default('pending');
            $table->text('result_url')->nullable();
            $table->text('subtitle_url')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }
};
