<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class PostgresVecService
{
    public function __construct()
    {
        try {
            // Check if pgvector extension is installed, if not, install it
            $this->initializePgvector();
        } catch (Exception $e) {
            Log::error('PostgresVecService initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function initializePgvector(): void
    {
        try {
            // Check if pgvector extension exists
            $extensionExists = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'vector'");
            
            if (!$extensionExists) {
                // Create the extension if it doesn't exist
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
                Log::info('pgvector extension created successfully');
            }
        } catch (Exception $e) {
            Log::error('Failed to initialize pgvector extension: ' . $e->getMessage());
            throw new Exception('Failed to initialize pgvector extension: ' . $e->getMessage());
        }
    }
    
    public function createVectorTable(): void
    {
        try {
            // Check if table exists
            $tableExists = DB::selectOne("SELECT to_regclass('public.embeddings') IS NOT NULL as exists");
            
            if (!$tableExists || !$tableExists->exists) {
                // Create embeddings table with vector support
                DB::statement("
                    CREATE TABLE IF NOT EXISTS embeddings (
                        id SERIAL PRIMARY KEY,
                        content TEXT NOT NULL,
                        embedding VECTOR(1536) NOT NULL,
                        type VARCHAR(50) DEFAULT 'note',
                        note_id INTEGER NULL,
                        content_hash VARCHAR(64) NULL
                    )
                ");
                
                // Create index for faster similarity search
                DB::statement("CREATE INDEX IF NOT EXISTS embeddings_embedding_idx ON embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)");
                
                Log::info('Embeddings table created successfully');
            } else {
                // Check if content_hash column exists
                $columnExists = DB::selectOne("
                    SELECT 1 
                    FROM information_schema.columns 
                    WHERE table_name='embeddings' AND column_name='content_hash'
                ");
                
                if (!$columnExists) {
                    // Add content_hash column if it doesn't exist
                    DB::statement("ALTER TABLE embeddings ADD COLUMN content_hash VARCHAR(64) NULL");
                    Log::info('Added content_hash column to embeddings table');
                }
            }
        } catch (Exception $e) {
            Log::error('Error creating vector table: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function insertVector(string $content, array $embedding, string $type = 'note', ?int $note_id = null, ?string $contentHash = null): bool
    {
        // Explicit debug log at the very beginning
        Log::alert('PostgresVecService::insertVector called', [
            'note_id' => $note_id,
            'content_hash_is_null' => $contentHash === null,
            'content_hash_length' => $contentHash ? strlen($contentHash) : 0,
            'content_length' => strlen($content),
            'service_instance' => spl_object_hash($this)
        ]);

        try {
            // Validate content hash - log the initial state
            Log::info('insertVector called', [
                'note_id' => $note_id,
                'received_hash' => $contentHash,
                'content_length' => strlen($content)
            ]);
            
            // If content hash wasn't provided, calculate it
            if ($contentHash === null || empty($contentHash)) {
                $contentHash = $this->calculateContentHash($content);
                Log::info('Content hash was null/empty, calculated new hash', [
                    'note_id' => $note_id,
                    'calculated_hash' => $contentHash
                ]);
            }
            
            // Explicitly validate that we have a hash - if not, throw an exception
            if ($contentHash === null || empty($contentHash)) {
                throw new \Exception('Cannot insert embedding without a valid content hash');
            }
            
            // Delete any existing embeddings for this note_id to avoid duplicates
            if ($note_id !== null) {
                $deleted = DB::delete("DELETE FROM embeddings WHERE note_id = ? AND type = ?", [$note_id, $type]);
                Log::info('Deleted existing embeddings', [
                    'note_id' => $note_id,
                    'count_deleted' => $deleted
                ]);
            }
            
            // Convert embedding array to PostgreSQL vector format
            $embeddingStr = "[" . implode(',', $embedding) . "]";
            
            // Insert into embeddings table with content hash
            Log::info('Inserting embedding with hash', [
                'note_id' => $note_id,
                'hash' => $contentHash
            ]);
            
            DB::statement("
                INSERT INTO embeddings (content, embedding, type, note_id, content_hash)
                VALUES (?, ?::vector, ?, ?, ?)
            ", [$content, $embeddingStr, $type, $note_id, $contentHash]);
            
            // Verify the embedding was inserted with a hash
            $inserted = DB::selectOne("
                SELECT content_hash FROM embeddings 
                WHERE note_id = ? AND type = ? 
                ORDER BY id DESC LIMIT 1
            ", [$note_id, $type]);
            
            if (!$inserted || $inserted->content_hash === null || empty($inserted->content_hash)) {
                Log::error('Verification failed: Embedding inserted but hash is null or empty', [
                    'note_id' => $note_id,
                    'expected_hash' => $contentHash
                ]);
                
                // Try an explicit update as a last resort
                DB::update("
                    UPDATE embeddings SET content_hash = ? 
                    WHERE note_id = ? AND type = ? AND (content_hash IS NULL OR content_hash = '')
                ", [$contentHash, $note_id, $type]);
                
                // Re-verify
                $recheck = DB::selectOne("
                    SELECT content_hash FROM embeddings 
                    WHERE note_id = ? AND type = ? 
                    ORDER BY id DESC LIMIT 1
                ", [$note_id, $type]);
                
                if (!$recheck || $recheck->content_hash === null || empty($recheck->content_hash)) {
                    throw new \Exception('Failed to save content hash to embedding even after explicit update');
                }
                
                Log::info('Successfully fixed embedding hash with explicit update', [
                    'note_id' => $note_id,
                    'hash' => $contentHash
                ]);
            } else {
                Log::info('Embedding inserted and verified with hash', [
                    'note_id' => $note_id,
                    'stored_hash' => $inserted->content_hash
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error inserting vector: ' . $e->getMessage(), [
                'note_id' => $note_id,
                'error_trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    public function searchSimilar(array $queryEmbedding, int $limit = 5): array
    {
        try {
            // Convert query embedding to PostgreSQL vector format
            $embeddingStr = "[" . implode(',', $queryEmbedding) . "]";
            
            Log::debug('Performing vector similarity search', [
                'limit' => $limit,
                'embedding_dimensions' => count($queryEmbedding)
            ]);
            
            $startTime = microtime(true);
            
            // Perform similarity search using cosine distance
            $results = DB::select("
                SELECT 
                    id, 
                    content as content_chunk,
                    type,
                    note_id as vault_node_id,
                    1 - (embedding <=> :query_embedding::vector) as distance
                FROM 
                    embeddings
                ORDER BY 
                    distance DESC
                LIMIT :limit
            ", [
                'query_embedding' => $embeddingStr,
                'limit' => $limit
            ]);
            
            $duration = microtime(true) - $startTime;
            $resultsArray = json_decode(json_encode($results), true);
            
            // Prepare summary statistics for logging
            $scoreStats = [];
            if (!empty($resultsArray)) {
                $scores = array_column($resultsArray, 'distance');
                $scoreStats = [
                    'min_score' => min($scores),
                    'max_score' => max($scores),
                    'avg_score' => array_sum($scores) / count($scores),
                    'median_score' => $this->calculateMedian($scores)
                ];
            }
            
            Log::debug('Vector similarity search completed', [
                'duration_ms' => round($duration * 1000, 2),
                'results_count' => count($resultsArray),
                'score_stats' => $scoreStats
            ]);
            
            return $resultsArray;
        } catch (Exception $e) {
            Log::error('Error searching similar vectors: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calculate the median value from an array of numbers
     */
    private function calculateMedian(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }
        
        sort($values);
        $middle = floor($count / 2);
        
        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        
        return $values[$middle];
    }
    
    /**
     * Calculate a hash for the content
     */
    public function calculateContentHash(string $content): string
    {
        return hash('sha256', $content);
    }
    
    /**
     * Check if an embedding exists for the given content hash and node ID
     */
    public function embeddingExistsForHash(string $contentHash, int $noteId): bool
    {
        try {
            $result = DB::selectOne("
                SELECT 1 
                FROM embeddings 
                WHERE content_hash = ? AND note_id = ?
            ", [$contentHash, $noteId]);
            
            return $result !== null;
        } catch (Exception $e) {
            Log::error('Error checking embedding existence: ' . $e->getMessage());
            return false;
        }
    }
} 