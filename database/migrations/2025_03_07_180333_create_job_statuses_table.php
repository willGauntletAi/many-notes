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
        Schema::create('job_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique()->nullable(); // UUID of the job
            $table->string('type'); // Type of job (e.g., 'embedding')
            $table->foreignId('vault_id')->constrained('vaults')->onDelete('cascade');
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed'
            $table->text('message')->nullable(); // Status message or error details
            $table->integer('progress')->default(0); // Progress percentage
            $table->timestamps();
            
            // Add index for quick lookups
            $table->index(['vault_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_statuses');
    }
};
