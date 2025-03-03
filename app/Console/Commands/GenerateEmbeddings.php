<?php

namespace App\Console\Commands;

use App\Models\VaultNode;
use App\Services\EmbeddingService;
use App\Services\PostgresVecService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vault:generate-embeddings {vault_id? : The ID of the vault to generate embeddings for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate vector embeddings for vault nodes';

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingService $embeddingService, PostgresVecService $vecService)
    {
        $vaultId = $this->argument('vault_id');
        
        $query = VaultNode::query();
        
        if ($vaultId) {
            $query->where('vault_id', $vaultId);
        }
        
        $totalNodes = $query->count();
        
        if ($totalNodes === 0) {
            $this->error('No nodes found to process');
            return 1;
        }
        
        $this->info("Generating embeddings for {$totalNodes} nodes...");
        $bar = $this->output->createProgressBar($totalNodes);
        
        foreach ($query->cursor() as $node) {
            try {
                $content = $node->content;
                $embedding = $embeddingService->generateEmbedding($content);
                
                $vecService->insertVector(
                    $content,
                    $embedding,
                    'node',
                    $node->id
                );
                
                $this->info("Generated embedding for node {$node->id}");
            } catch (\Exception $e) {
                $this->error("Error generating embedding for node {$node->id}: " . $e->getMessage());
                Log::error("Error generating embedding for node {$node->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Embeddings generation completed.');
        
        return 0;
    }
}
