<?php

namespace App\Console\Commands;

use App\Models\VaultNode;
use App\Services\EmbeddingService;
use App\Services\PostgresVecService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        
        $processedCount = 0;
        $skippedCount = 0;
        $embeddedCount = 0;
        
        foreach ($query->cursor() as $node) {
            try {
                $processedCount++;
                $content = $node->content;
                
                // Always calculate and update the content hash
                $contentHash = $vecService->calculateContentHash($content);
                
                // Update the node's content_hash if needed
                if ($node->content_hash !== $contentHash) {
                    $node->content_hash = $contentHash;
                    $node->save();
                }
                
                // Check if an embedding already exists with the same content hash
                if ($vecService->embeddingExistsForHash($contentHash, $node->id)) {
                    $this->info("Skipped node {$node->id} - content unchanged");
                    $skippedCount++;
                } else {
                    // Generate the embedding
                    $embedding = $embeddingService->generateEmbedding($content);
                    
                    // Delete any existing embeddings for this node
                    DB::table('embeddings')
                        ->where('note_id', $node->id)
                        ->where('type', 'node')
                        ->delete();
                    
                    Log::alert('About to call insertVector with contentHash: ' . $contentHash);
                    
                    try {
                        // Insert new embedding with the proper content hash
                        $vecService->insertVector(
                            $content,
                            $embedding,
                            'node',
                            $node->id,
                            $contentHash
                        );
                        
                        // Verify the hash was saved
                        $hasHash = DB::selectOne("
                            SELECT content_hash FROM embeddings 
                            WHERE note_id = ? AND type = 'node'
                            ORDER BY id DESC LIMIT 1
                        ", [$node->id]);
                        
                        if (!$hasHash || empty($hasHash->content_hash)) {
                            Log::error("Hash verification failed for node {$node->id}: hash is missing after insertion");
                            
                            // Fix it directly as a last resort
                            DB::update("
                                UPDATE embeddings 
                                SET content_hash = ? 
                                WHERE note_id = ? AND type = 'node' AND (content_hash IS NULL OR content_hash = '')
                            ", [$contentHash, $node->id]);
                            
                            Log::alert("Applied emergency fix for node {$node->id}");
                        }
                        
                        $this->info("Generated embedding for node {$node->id}");
                        $embeddedCount++;
                    } catch (\Exception $vecException) {
                        Log::error("Vector insertion error for node {$node->id}: " . $vecException->getMessage());
                        Log::error("Vector insertion trace: " . $vecException->getTraceAsString());
                        throw $vecException; // Re-throw to be caught by outer try-catch
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error generating embedding for node {$node->id}: " . $e->getMessage());
                Log::error("Error generating embedding for node {$node->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Embeddings generation completed. Processed: {$processedCount}, Embedded: {$embeddedCount}, Skipped: {$skippedCount}");
        
        return 0;
    }
}
