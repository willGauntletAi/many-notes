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
        Schema::create('vault_node_vault_node', function (Blueprint $table): void {
            $table->foreignId('source_id')->constrained('vault_nodes');
            $table->foreignId('destination_id')->constrained('vault_nodes');
            $table->unsignedMediumInteger('position');

            $table->primary(['source_id', 'destination_id', 'position']);
        });
    }
};
