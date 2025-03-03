<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for the OpenAI API, including
    | the API key, model to use for chat completions, and embedding model.
    |
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'max_tokens' => env('MAX_TOKENS', 16384),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG (Retrieval-Augmented Generation) Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for the RAG system, including
    | chunk size, overlap, maximum chunks to retrieve, and relevance threshold.
    |
    */
    'rag' => [
        'chunk_size' => env('RAG_CHUNK_SIZE', 1000),
        'chunk_overlap' => env('RAG_CHUNK_OVERLAP', 200),
        'max_chunks' => env('RAG_MAX_CHUNKS', 5),
        'relevance_threshold' => env('RAG_RELEVANCE_THRESHOLD', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | PGVector Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for the PostgreSQL pgvector extension.
    |
    */
    'pgvector' => [
        'connection' => env('DB_CONNECTION', 'pgsql'),
        'database' => env('DB_DATABASE', 'many_notes'),
        'vector_dimension' => 1536, // Default dimension for OpenAI embeddings
    ],
]; 