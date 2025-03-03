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
                        note_id INTEGER NULL
                    )
                ");
                
                // Create index for faster similarity search
                DB::statement("CREATE INDEX IF NOT EXISTS embeddings_embedding_idx ON embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)");
                
                Log::info('Embeddings table created successfully');
            }
        } catch (Exception $e) {
            Log::error('Error creating vector table: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function insertVector(string $content, array $embedding, string $type = 'note', ?int $note_id = null): bool
    {
        try {
            // Convert embedding array to PostgreSQL vector format
            $embeddingStr = "[" . implode(',', $embedding) . "]";
            
            // Insert into embeddings table
            DB::statement("
                INSERT INTO embeddings (content, embedding, type, note_id)
                VALUES (?, embedding::vector, ?, ?)
            ", [$content, $embeddingStr, $type, $note_id]);
            
            return true;
        } catch (Exception $e) {
            Log::error('Error inserting vector: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function searchSimilar(array $queryEmbedding, int $limit = 5): array
    {
        try {
            // Convert query embedding to PostgreSQL vector format
            $embeddingStr = "[" . implode(',', $queryEmbedding) . "]";
            
            // Perform similarity search using cosine distance
            $results = DB::select("
                SELECT 
                    id, 
                    content,
                    type,
                    note_id,
                    1 - (embedding <=> :query_embedding::vector) as similarity
                FROM 
                    embeddings
                ORDER BY 
                    similarity DESC
                LIMIT :limit
            ", [
                'query_embedding' => $embeddingStr,
                'limit' => $limit
            ]);
            
            return json_decode(json_encode($results), true);
        } catch (Exception $e) {
            Log::error('Error searching similar vectors: ' . $e->getMessage());
            throw $e;
        }
    }
} 