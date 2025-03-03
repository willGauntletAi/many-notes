<?php

namespace App\Providers;

use App\Services\EmbeddingService;
use App\Services\PostgresVecService;
use App\Services\RAGService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register PostgresVecService
        $this->app->singleton(PostgresVecService::class, function ($app) {
            try {
                return new PostgresVecService();
            } catch (Exception $e) {
                Log::error('Failed to initialize PostgresVecService: ' . $e->getMessage());
                // Return a null instance - service methods will need to check for this
                return null;
            }
        });

        // Register EmbeddingService
        $this->app->singleton(EmbeddingService::class, function ($app) {
            $apiKey = config('chat.openai.api_key');
            $model = config('chat.openai.embedding_model');
            return new EmbeddingService($apiKey, $model);
        });
        
        $this->app->singleton(RAGService::class, function ($app) {
            return new RAGService(
                $app->make(EmbeddingService::class),
                $app->make(PostgresVecService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Initialize the database for vector storage
        try {
            $vecService = $this->app->make(PostgresVecService::class);
            if ($vecService) {
                $vecService->createVectorTable();
            }
        } catch (Exception $e) {
            Log::error('Failed to initialize vector database: ' . $e->getMessage());
            // We don't rethrow here as the application might still function without vector search
        }
    }
}
