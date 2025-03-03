<think>The user wants a comprehensive implementation plan for adding chat functionality to the vault using RAG with sqlite-vec for vector storage and OpenAI API for embeddings. This plan should be detailed enough for a junior developer to follow.

Let me organize this into a clear, step-by-step implementation plan covering:
1. Database schema changes
2. Embedding generation and storage
3. RAG implementation
4. Chat interface
5. Integration points

I'll focus on making this a practical guide rather than writing code.</think># Implementation Plan: Vault Chat with RAG using SQLite-Vec and OpenAI

This implementation plan outlines all steps required to add chat capabilities to the vault system using Retrieval-Augmented Generation (RAG) with SQLite-Vec for vector storage and OpenAI for embeddings.

## Phase 1: Setup and Database Infrastructure

### 1.1 Install Dependencies

1. **Install SQLite-Vec Extension**
   - Download the latest SQLite-Vec extension from GitHub using the install script:
     ```bash
     mkdir -p extensions
     curl -o install.sh -L https://github.com/asg017/sqlite-vec/releases/latest/download/install.sh
     chmod +x install.sh
     ./install.sh
     ```
   - Move the extension to a location accessible by PHP:
     ```bash
     # Assuming the install script places the extension in the current directory
     mv vec0.so extensions/
     ```
   - Create a PHP wrapper for the SQLite-Vec extension:
     ```php
     // app/Services/SqliteVecService.php
     namespace App\Services;
     
     class SqliteVecService
     {
         protected $db;
         protected $extensionPath;
         
         public function __construct(string $dbPath, string $extensionPath = null)
         {
             $this->extensionPath = $extensionPath ?? base_path('extensions/vec0.so');
             $this->db = new \SQLite3($dbPath);
             $this->loadExtension();
         }
         
         protected function loadExtension(): void
         {
             $this->db->loadExtension($this->extensionPath);
         }
         
         public function createVectorTable(string $tableName, array $columns): bool
         {
             $columnDefs = [];
             foreach ($columns as $name => $type) {
                 $columnDefs[] = "$name $type";
             }
             
             $sql = "CREATE VIRTUAL TABLE IF NOT EXISTS $tableName USING vec0(" . implode(', ', $columnDefs) . ")";
             return $this->db->exec($sql);
         }
         
         public function insertVector(string $tableName, array $data): bool
         {
             $columns = array_keys($data);
             $placeholders = array_fill(0, count($columns), '?');
             
             $stmt = $this->db->prepare("INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")");
             
             $i = 1;
             foreach ($data as $value) {
                 if (is_array($value)) {
                     $stmt->bindValue($i++, json_encode($value));
                 } else {
                     $stmt->bindValue($i++, $value);
                 }
             }
             
             return $stmt->execute() !== false;
         }
         
         public function searchSimilar(string $tableName, string $vectorColumn, array $queryVector, int $limit = 5): array
         {
             $queryJson = json_encode($queryVector);
             $sql = "SELECT rowid, * FROM $tableName WHERE $vectorColumn MATCH '$queryJson' ORDER BY distance LIMIT $limit";
             
             $result = $this->db->query($sql);
             $rows = [];
             
             while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                 $rows[] = $row;
             }
             
             return $rows;
         }
         
         public function close(): void
         {
             $this->db->close();
         }
     }
     ```

2. **Add OpenAI Client**
   - Add to `composer.json`:
     ```
     "require": {
         "openai-php/client": "^0.6.0"
     }
     ```
   - Run `composer update`

### 1.2 Create Database Schema

1. **Create Migration for Chat Tables**
   - Create a migration for `vault_chats` table:
     - `id` (primary key)
     - `vault_id` (foreign key to vaults)
     - `name` (string)
     - `created_at`, `updated_at` (timestamps)

2. **Create Migration for Chat Messages**
   - Create a migration for `chat_messages` table:
     - `id` (primary key)
     - `vault_chat_id` (foreign key to vault_chats)
     - `role` (enum: 'user', 'assistant')
     - `content` (text)
     - `created_at`, `updated_at` (timestamps)

3. **Create Migration for Node Embeddings**
   - Create a migration to set up SQLite-Vec:
     - Create a virtual table `node_embeddings` using vec0:
       - `vault_node_id` (foreign key to vault_nodes)
       - `chunk_index` (integer)
       - `content_chunk` (text)
       - `embedding` (float[1536]) - for OpenAI embeddings
       - Metadata columns for additional filtering

4. **Add Relationships to Models**
   - Update `Vault` model to have a relationship with `VaultChat`
   - Update `VaultNode` model to have a relationship with embeddings

## Phase 2: Embedding and RAG Implementation

### 2.1 Create Services for Embeddings

1. **Create EmbeddingService**
   - Implement a service to generate embeddings from OpenAI
   - Add configuration for embedding model (`text-embedding-3-small` recommended)
   - Add methods for:
     - Generating embeddings from text
     - Storing embeddings in the database
     - Searching for similar embeddings

2. **Implement Content Chunking**
   - Create a service to split document content into meaningful chunks
   - Implement chunking strategies:
     - Fixed-size chunks with overlap
     - Semantic chunking based on headers/paragraphs
   - Add maximum token size configuration

3. **Create Background Jobs**
   - Create a job to process vault nodes and generate embeddings
   - Implement job to update embeddings when node content changes
   - Add command to reindex entire vault: `php artisan vault:reindex-embeddings {vault_id}`

### 2.2 Implement RAG Pipeline

1. **Create RAGService**
   - Implement RAG query processing flow:
     - Receive user query
     - Generate embedding for query
     - Search for relevant chunks using SQLite-Vec
     - Assemble context from retrieved chunks
     - Send context + query to OpenAI
     - Process and return response

2. **Create Context Assembly Logic**
   - Implement logic to assemble context from retrieved chunks
   - Add token counting to ensure context fits within limits
   - Implement prioritization for most relevant chunks
   - Add citations and source tracking

3. **Add Configuration**
   - Create configuration for RAG parameters:
     - Number of chunks to retrieve
     - Relevance threshold
     - Context window size
     - Model selection

## Phase 3: Chat Interface Implementation

### 3.1 Create Livewire Components

1. **Create Chat Component**
   - Create `app/Livewire/Vault/Chat.php`
   - Implement methods:
     - Create new chat
     - Send message
     - Process RAG pipeline
     - Retrieve chat history

2. **Create Chat Sessions Component**
   - Create `app/Livewire/Vault/ChatSessions.php`
   - Implement methods:
     - List chat sessions
     - Create new session
     - Delete session

3. **Create Chat Settings Component**
   - Create `app/Livewire/Vault/ChatSettings.php`
   - Implement controls for RAG parameters
   - Add ability to pin specific nodes to context

### 3.2 Create Blade Templates

1. **Create Chat View**
   - Create `resources/views/livewire/vault/chat.blade.php`
   - Implement UI for:
     - Message history display
     - Message input
     - Send button
     - Loading indicators
     - Citation display

2. **Create Chat Sessions View**
   - Create `resources/views/livewire/vault/chat-sessions.blade.php`
   - Implement UI for displaying and managing chat sessions

3. **Modify Vault Show View**
   - Update `resources/views/livewire/vault/show.blade.php`
   - Add chat panel or toggle button
   - Integrate chat component into layout

### 3.3 Add JavaScript for Chat UI

1. **Create Alpine.js Components**
   - Implement Alpine.js components for chat UI
   - Add real-time updates and animations
   - Implement markdown rendering for chat messages

2. **Implement Loading States**
   - Add loading indicators for AI responses
   - Implement typing animation for assistant messages

## Phase 4: Integration and Finalization

### 4.1 Update Routes and Controllers

1. **Add Routes for Chat API**
   - Add routes for chat API endpoints
   - Implement authentication and authorization

2. **Update HTTP Controllers**
   - Add methods to handle chat-related requests
   - Implement error handling and validation

### 4.2 Add Environment Configuration

1. **Update .env File**
   - Add configuration for OpenAI API:
     ```
     OPENAI_API_KEY=your_api_key
     OPENAI_MODEL=gpt-4o
     OPENAI_EMBEDDING_MODEL=text-embedding-3-small
     MAX_TOKENS=16384
     ```

2. **Create Configuration Files**
   - Create `config/chat.php` for chat-specific configuration
   - Add default values for RAG parameters

### 4.3 Add Commands and Jobs

1. **Create Artisan Commands**
   - Create command to generate embeddings: `php artisan vault:generate-embeddings {vault_id}`
   - Create command to clear embeddings: `php artisan vault:clear-embeddings {vault_id}`

2. **Create Queue Jobs**
   - Implement job to process embeddings in the background
   - Add job to handle long-running chat requests

## Phase 5: UI and UX Enhancements

### 5.1 Add UI Components

1. **Create Source Citation Component**
   - Create component to display source citations
   - Implement clickable links to navigate to source nodes

2. **Create Context Visualization**
   - Add UI to show which nodes are included in context
   - Implement visual indicators for relevance scores

3. **Add Chat Controls**
   - Implement UI for regenerating responses
   - Add options to adjust RAG parameters
   - Create controls for clearing chat history

### 5.2 Implement Advanced Features

1. **Add Chat Export**
   - Implement functionality to export chat as Markdown
   - Add option to save insights as new notes

2. **Add Node Pinning**
   - Allow users to pin specific nodes to always include in context
   - Implement UI for managing pinned nodes

3. **Implement Conversation Memory**
   - Add system to track important information across conversation
   - Implement context management for multi-turn conversations

## Technical Implementation Details

### SQLite-Vec Usage

```sql
-- Example of creating the embeddings virtual table
CREATE VIRTUAL TABLE node_embeddings USING vec0(
  embedding FLOAT[1536],
  vault_node_id INTEGER,
  chunk_index INTEGER,
  content_chunk TEXT
);

-- Example of querying for similar chunks
SELECT 
  vault_node_id, 
  content_chunk, 
  distance
FROM node_embeddings
WHERE embedding MATCH '<query_embedding_json>'
ORDER BY distance
LIMIT 10;
```

### OpenAI Embeddings API Integration

The `EmbeddingService` should implement:

1. Connection to OpenAI API
2. Methods to generate embeddings
3. Efficient batch processing for multiple chunks
4. Error handling and retries

### RAG Context Assembly Logic

When processing a user query:

1. Generate embedding for query
2. Find similar chunks using SQLite-Vec
3. Assemble context with format:
   ```
   CONTEXT:
   [Source: Node Title 1]
   Content from chunk 1...
   
   [Source: Node Title 2]
   Content from chunk 2...
   
   USER QUERY:
   The user's question here
   ```
4. Send to OpenAI with appropriate system prompt
5. Process response to include citations

## Conclusion

This implementation plan provides a comprehensive roadmap for adding chat capabilities to your vault using RAG with SQLite-Vec and OpenAI. By following these steps, a junior developer should be able to implement the feature systematically.

The approach balances simplicity with scalability, allowing for future enhancements while providing a solid foundation for the core functionality. The SQLite-Vec integration offers an efficient vector storage solution that works well with your existing SQLite database.

[Source: github.com/asg017/sqlite-vec](https://github.com/asg017/sqlite-vec)
