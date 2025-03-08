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
        $this->maxChunks = (int) config('chat.rag.max_chunks', 10); // Default to 10 if not set
        // We're not using relevance threshold anymore, but keep it for backward compatibility
        $this->relevanceThreshold = (float) config('chat.rag.relevance_threshold');
    }

    /**
     * Process a user query using RAG
     */
    public function processQuery(Vault $vault, string $query): string
    {
        try {
            Log::info('Processing RAG query', [
                'vault_id' => $vault->id,
                'query' => $query,
                'query_length' => strlen($query),
            ]);
            
            // Generate embedding for the query
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);
            
            // Retrieve relevant chunks
            $relevantChunks = $this->retrieveRelevantChunks($queryEmbedding);
            
            Log::debug('Retrieved relevant chunks', [
                'count' => count($relevantChunks),
            ]);
            
            if (empty($relevantChunks)) {
                Log::info('No relevant chunks found, generating response without context');
                return $this->generateResponse($query, []);
            }
            
            // Simply take the top chunks directly
            $limitedChunks = array_slice($relevantChunks, 0, $this->maxChunks);
            
            // Log information about the scores of the chunks being used
            $limitedScores = array_column($limitedChunks, 'distance');
            $limitedScoreStats = !empty($limitedScores) ? [
                'min_score' => min($limitedScores),
                'max_score' => max($limitedScores),
                'avg_score' => array_sum($limitedScores) / count($limitedScores)
            ] : [];
            
            Log::debug('Using top most relevant chunks', [
                'chunks_count' => count($limitedChunks),
                'max_chunks' => $this->maxChunks,
                'score_stats' => $limitedScoreStats
            ]);
            
            // Generate response
            $response = $this->generateResponse($query, $limitedChunks);
            
            Log::info('RAG response generated', [
                'response_length' => strlen($response),
                'chunks_used' => count($limitedChunks),
                'avg_chunk_score' => !empty($limitedScores) ? array_sum($limitedScores) / count($limitedScores) : null
            ]);
            
            return $response;
        } catch (\Exception $e) {
            Log::error('RAG processing error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve relevant chunks from the vector database
     */
    protected function retrieveRelevantChunks(array $queryEmbedding): array
    {
        Log::debug('Retrieving relevant chunks from vector database', [
            'max_retrieve_count' => $this->maxChunks,
        ]);
        
        $startTime = microtime(true);
        $results = $this->vecService->searchSimilar(
            $queryEmbedding,
            $this->maxChunks // Retrieve exactly the number of chunks we need
        );
        $duration = microtime(true) - $startTime;
        
        // Create a summary of scores for logging
        $scoresSummary = [];
        if (!empty($results)) {
            $scores = array_column($results, 'distance');
            $scoresSummary = [
                'min_score' => min($scores),
                'max_score' => max($scores),
                'avg_score' => array_sum($scores) / count($scores),
                'individual_scores' => array_map(function ($chunk) {
                    return [
                        'node_id' => $chunk['vault_node_id'],
                        'score' => $chunk['distance']
                    ];
                }, $results)
            ];
        }
        
        Log::debug('Vector search completed', [
            'duration_ms' => round($duration * 1000, 2),
            'results_count' => count($results),
            'scores_summary' => $scoresSummary
        ]);
        
        return $results;
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
        
        // Prepare messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $query]
        ];
        
        // Log the messages before sending to LLM
        Log::info('Sending messages to LLM:', [
            'model' => $this->model,
            'system_prompt_length' => strlen($systemPrompt),
            'user_query' => $query,
            'number_of_chunks' => count($chunks),
            'full_messages' => $messages,
        ]);
        
        // Call OpenAI API
        $response = $this->openaiClient->chat()->create([
            'model' => $this->model,
            'messages' => $messages,
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
            Log::debug('No chunks available for context assembly');
            return '';
        }
        
        $context = "CONTEXT:\n\n";
        $chunksInfo = [];
        
        foreach ($chunks as $chunk) {
            // Get the node information
            $nodeId = $chunk['vault_node_id'];
            
            // You might want to fetch the node title here
            // For now, we'll just use the node ID
            $context .= "[Source: Node #{$nodeId}]\n";
            $context .= $chunk['content_chunk'] . "\n\n";
            
            // Collect info about this chunk for logging with score
            $chunksInfo[] = [
                'node_id' => $nodeId,
                'chunk_length' => strlen($chunk['content_chunk']),
                'score' => isset($chunk['distance']) ? round($chunk['distance'], 6) : null,
            ];
        }
        
        // Sort chunks by score for better logging clarity
        usort($chunksInfo, function($a, $b) {
            if (!isset($a['score']) || !isset($b['score'])) {
                return 0;
            }
            return $b['score'] <=> $a['score']; // Descending order
        });
        
        Log::debug('Context assembled from chunks', [
            'num_chunks' => count($chunks),
            'total_context_length' => strlen($context),
            'chunks_info' => $chunksInfo,
        ]);
        
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
            
            Log::debug('System prompt created with context', [
                'context_length' => strlen($context),
                'total_system_prompt_length' => strlen($systemPrompt),
                'num_context_lines' => substr_count($context, "\n") + 1,
            ]);
        } else {
            $systemPrompt .= "No relevant context was found in the vault. Answer based on your general knowledge.";
            
            Log::debug('System prompt created without context', [
                'total_system_prompt_length' => strlen($systemPrompt),
            ]);
        }
        
        return $systemPrompt;
    }

    /**
     * Process a query with explicitly provided context from notes
     * This is used when we want to include specific notes in the context
     */
    public function processQueryWithExplicitContext(Vault $vault, string $query, string $systemPrompt, string $explicitContext): string
    {
        try {
            Log::info('Processing query with explicit context', [
                'vault_id' => $vault->id,
                'query' => $query,
                'query_length' => strlen($query),
                'context_length' => strlen($explicitContext),
            ]);
            
            // Prepare messages with the provided system prompt and context
            $fullSystemPrompt = $systemPrompt . "\n\n" . "CONTEXT:\n" . $explicitContext;
            
            $messages = [
                ['role' => 'system', 'content' => $fullSystemPrompt],
                ['role' => 'user', 'content' => $query]
            ];
            
            // Log the messages before sending to LLM
            Log::info('Sending messages to LLM with explicit context:', [
                'model' => $this->model,
                'system_prompt_length' => strlen($fullSystemPrompt),
                'user_query' => $query,
            ]);
            
            // Call OpenAI API
            $response = $this->openaiClient->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => (int) $this->maxTokens,
                'temperature' => 0.7,
            ]);
            
            Log::info('Received response from OpenAI with explicit context', [
                'response_length' => strlen($response->choices[0]->message->content),
            ]);
            
            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error('Error in processQueryWithExplicitContext: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}