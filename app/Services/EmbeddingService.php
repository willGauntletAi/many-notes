<?php

declare(strict_types=1);

namespace App\Services;

use OpenAI;

class EmbeddingService
{
    protected $client;
    protected $model;
    
    public function __construct(string $apiKey, string $model = 'text-embedding-3-small')
    {
        $this->client = OpenAI::client($apiKey);
        $this->model = $model;
    }
    
    public function generateEmbedding(string $text): array
    {
        $response = $this->client->embeddings()->create([
            'model' => $this->model,
            'input' => $text,
        ]);
        
        return $response->embeddings[0]->embedding;
    }
    
    public function generateBatchEmbeddings(array $texts): array
    {
        $response = $this->client->embeddings()->create([
            'model' => $this->model,
            'input' => $texts,
        ]);
        
        $embeddings = [];
        foreach ($response->embeddings as $embedding) {
            $embeddings[] = $embedding->embedding;
        }
        
        return $embeddings;
    }
}