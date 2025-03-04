<?php

namespace App\Services;

use App\Models\Vault;
use Illuminate\Support\Facades\Log;
use OpenAI;

class RAGService
{
    protected $embeddingService;
    protected $vecService;
    protected $openaiClient;
    protected $model;
    protected $maxTokens;
    protected $maxChunks;
    protected $relevanceThreshold;

    public function __construct(
        EmbeddingService $embeddingService,
        PostgresVecService $vecService
    ) {
        $this->embeddingService = $embeddingService;
        $this->vecService = $vecService;
        
        // Initialize OpenAI client
        $this->openaiClient = OpenAI::client(config('chat.openai.api_key'));
        $this->model = config('chat.openai.model');
        $this->maxTokens = (int) config('chat.openai.max_tokens');
        
        // RAG configuration
        $this->maxChunks = (int) config('chat.rag.max_chunks');
        $this->relevanceThreshold = (float) config('chat.rag.relevance_threshold');
    }

    /**
     * Process a user query using RAG
     */
    public function processQuery(Vault $vault, string $query): string
    {
        try {
            // Generate embedding for the query
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);
            
            // Retrieve relevant chunks
            $relevantChunks = $this->retrieveRelevantChunks($queryEmbedding);
            
            if (empty($relevantChunks)) {
                return $this->generateResponse($query, []);
            }
            
            // Filter chunks by relevance threshold
            $filteredChunks = array_filter($relevantChunks, function ($chunk) {
                return $chunk['distance'] <= $this->relevanceThreshold;
            });
            
            // Limit to max chunks
            $limitedChunks = array_slice($filteredChunks, 0, $this->maxChunks);
            
            // Generate response
            return $this->generateResponse($query, $limitedChunks);
        } catch (\Exception $e) {
            Log::error('RAG processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve relevant chunks from the vector database
     */
    protected function retrieveRelevantChunks(array $queryEmbedding): array
    {
        return $this->vecService->searchSimilar(
            $queryEmbedding,
            $this->maxChunks * 2 // Retrieve more than needed to allow for filtering
        );
    }

    /**
     * Generate a response using OpenAI
     */
    protected function generateResponse(string $query, array $chunks): string
    {
        // Assemble context from chunks
        $context = $this->assembleContext($chunks);
        
        // Create system prompt
        $systemPrompt = $this->createSystemPrompt($context);
        
        // Call OpenAI API
        $response = $this->openaiClient->chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $query]
            ],
            'max_tokens' => (int) $this->maxTokens,
            'temperature' => 0.7,
        ]);
        
        return $response->choices[0]->message->content;
    }

    /**
     * Assemble context from retrieved chunks
     */
    protected function assembleContext(array $chunks): string
    {
        if (empty($chunks)) {
            return '';
        }
        
        $context = "CONTEXT:\n\n";
        
        foreach ($chunks as $chunk) {
            // Get the node information
            $nodeId = $chunk['vault_node_id'];
            
            // You might want to fetch the node title here
            // For now, we'll just use the node ID
            $context .= "[Source: Node #{$nodeId}]\n";
            $context .= $chunk['content_chunk'] . "\n\n";
        }
        
        return $context;
    }

    /**
     * Create system prompt with context
     */
    protected function createSystemPrompt(string $context): string
    {
        $systemPrompt = "You are a helpful assistant that answers questions based on the user's vault content. ";
        
        if (!empty($context)) {
            $systemPrompt .= "Use the following context to answer the user's question. If the context doesn't contain relevant information, say so and answer based on your general knowledge.\n\n";
            $systemPrompt .= $context;
        } else {
            $systemPrompt .= "No relevant context was found in the vault. Answer based on your general knowledge.";
        }
        
        return $systemPrompt;
    }
}