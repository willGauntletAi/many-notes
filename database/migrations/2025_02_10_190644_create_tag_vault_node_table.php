<?php

declare(strict_types=1);

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
        Schema::create('tag_vault_node', function (Blueprint $table): void {
            $table->foreignId('vault_node_id')->constrained('vault_nodes');
            $table->foreignId('tag_id')->constrained('tags');
            $table->unsignedMediumInteger('position');

            $table->primary(['tag_id', 'vault_node_id', 'position']);
        });
    }
};
