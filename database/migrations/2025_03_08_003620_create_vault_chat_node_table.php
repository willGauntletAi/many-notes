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
        Schema::create('vault_chat_node', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vault_chat_id')->constrained('vault_chats')->onDelete('cascade');
            $table->foreignId('vault_node_id')->constrained('vault_nodes')->onDelete('cascade');
            $table->timestamps();
            
            // Create a unique constraint to prevent duplicate associations
            $table->unique(['vault_chat_id', 'vault_node_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_chat_node');
    }
};
