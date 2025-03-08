<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use App\Models\VaultChat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\JobStatus;
use App\Models\VaultNode;
use App\Services\PostgresVecService;

class Chat extends Component
{
    public Vault $vault;
    public ?array $activeChat = null;
    public array $chats = [];
    public array $messages = [];
    public string $messageText = '';
    public string $newChatName = '';
    public bool $showModal = false;
    public ?string $indexingStatus = null;
    public ?array $jobStatus = null;
    public bool $isPolling = false;
    public $embeddingPercentage = 0;
    
    // Make sure listeners are properly defined
    protected $listeners = [
        'openModal', 
        'closeModal',
        'resetState',
        'checkJobStatus',
        'clearIndexingStatus',
        'refreshEmbeddingPercentage',
        'processAiResponse',
        'processAiResponseWithContext',
        'echo-presence:online-users,here' => 'refreshOnlineUsers',
        'echo-presence:online-users,joining' => 'userJoining',
        'echo-presence:online-users,leaving' => 'userLeaving',
    ];
    
    // Enable reactive updates with Livewire 3
    protected $isReactive = true;

    public function mount(Vault $vault)
    {
        $this->vault = $vault;
        $this->loadChats();
        
        // Calculate initial embedding percentage
        $this->embeddingPercentage = $this->getEmbeddingCompletionPercentage();
        
        // Ensure modal is closed on initial load
        $this->showModal = false;
    }

    public function resetState()
    {
        $this->showModal = false;
        $this->newChatName = '';
        $this->dispatch('state-reset');
    }

    public function loadChats()
    {
        $chatModels = $this->vault->chats()->orderBy('created_at', 'desc')->get();
        
        $this->chats = $chatModels->map(function($chat) {
            return [
                'id' => $chat->id,
                'name' => $chat->name,
                'created_at' => $chat->created_at->toDateTimeString(),
                'updated_at' => $chat->updated_at->toDateTimeString(),
            ];
        })->toArray();
        
        $this->dispatch('chats-loaded', count: count($this->chats));
    }

    public function setActiveChat($chatId)
    {
        $chatModel = VaultChat::find($chatId);
        
        if ($chatModel) {
            $this->activeChat = [
                'id' => $chatModel->id,
                'name' => $chatModel->name,
                'created_at' => $chatModel->created_at->toDateTimeString(),
                'updated_at' => $chatModel->updated_at->toDateTimeString(),
                'vault_id' => $chatModel->vault_id,
            ];
            
            $this->loadMessages($chatModel);
        } else {
            $this->activeChat = null;
            $this->messages = [];
            Log::warning("Chat not found", ['chat_id' => $chatId]);
        }
    }
    
    public function loadMessages($chatModel = null)
    {
        $this->messages = [];
        
        if (!$chatModel && !$this->activeChat) {
            return;
        }
        
        if (!$chatModel) {
            $chatModel = VaultChat::find($this->activeChat['id']);
            if (!$chatModel) {
                return;
            }
        }
        
        $messageModels = $chatModel->messages()->orderBy('created_at', 'asc')->get();
        
        $this->messages = $messageModels->map(function($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'role' => $message->role,
                'created_at' => $message->created_at->toDateTimeString(),
            ];
        })->toArray();
        
        // Dispatch event to indicate messages have been loaded
        $this->dispatch('messages-loaded');
    }

    public function openModal()
    {
        $this->newChatName = '';
        $this->showModal = true;
        $this->dispatch('modal-state-changed', state: true);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->newChatName = '';
        $this->dispatch('modal-state-changed', state: false);
    }

    public function createChat()
    {
        try {
            $this->validate([
                'newChatName' => 'required|min:3|max:255',
            ]);
            
            if (empty(trim($this->newChatName))) {
                session()->flash('error', 'Chat name cannot be empty');
                return;
            }
            
            try {
                $chat = new VaultChat();
                $chat->name = $this->newChatName;
                $chat->vault_id = $this->vault->id;
                $chat->save();
                
                Log::info("Chat created", ['chat_id' => $chat->id]);
            } catch (\Exception $innerEx) {
                Log::error("Database error creating chat", [
                    'error' => $innerEx->getMessage()
                ]);
                
                session()->flash('error', 'Database error creating chat');
                return;
            }
            
            $this->closeModal();
            $this->newChatName = '';
            $this->loadChats();
            $this->resetState();
            $this->setActiveChat($chat->id);
            
            session()->flash('message', 'Chat created successfully!');
        } catch (\Exception $e) {
            Log::error("Error creating chat", [
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Error creating chat: ' . $e->getMessage());
        }
    }

    public function deleteChat($chatId)
    {
        try {
            $chat = VaultChat::find($chatId);
            
            if ($chat && $chat->vault_id === $this->vault->id) {
                $chat->delete();
                
                if ($this->activeChat && $this->activeChat['id'] == $chatId) {
                    $this->activeChat = null;
                    $this->messages = [];
                }
                
                $this->loadChats();
                $this->resetState();
                
                session()->flash('message', 'Chat deleted successfully!');
                
                Log::info("Chat deleted", ['chat_id' => $chatId]);
            } else {
                session()->flash('error', 'Chat not found or not associated with this vault');
            }
        } catch (\Exception $e) {
            Log::error("Error deleting chat", [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
            
            session()->flash('error', 'Failed to delete chat: ' . $e->getMessage());
        }
    }

    public function sendMessage()
    {
        Log::info('sendMessage started');
        
        if (!$this->activeChat) {
            session()->flash('error', 'No active chat selected');
            return;
        }
        
        if (empty(trim($this->messageText))) {
            return;
        }
        
        try {
            $chatModel = VaultChat::find($this->activeChat['id']);
            
            if (!$chatModel) {
                session()->flash('error', 'Cannot find chat to send message');
                return;
            }
            
            // Store the message text and clear the input field
            $messageContent = $this->messageText;
            $this->messageText = '';
            
            Log::info('Creating user message');
            
            // Create user message
            $userMessage = $chatModel->messages()->create([
                'content' => $messageContent,
                'role' => 'user',
            ]);
            
            Log::info('User message created: ' . $userMessage->id);
            
            // Add the user message directly to the messages array for immediate display
            $this->messages[] = [
                'id' => $userMessage->id,
                'content' => $userMessage->content,
                'role' => 'user',
                'created_at' => $userMessage->created_at->toDateTimeString(),
            ];
            
            Log::info('Added user message to messages array');
            
            // Add a temporary "thinking" message
            $this->messages[] = [
                'id' => 'temp-thinking',
                'content' => '...',
                'role' => 'assistant',
                'created_at' => now()->toDateTimeString(),
                'is_loading' => true
            ];
            
            Log::info('Added thinking indicator to messages array');
            
            // Dispatch event to update UI immediately
            $this->dispatch('messages-loaded');
            
            Log::info('Dispatched messages-loaded event');
            
            // Store the message ID in the session for later processing
            session(['last_user_message_id' => $userMessage->id]);
            
            // Call processAiResponse in a way that doesn't block the main thread
            // We'll use JavaScript to trigger this in a separate request
            $this->dispatch('process-ai-response', messageId: $userMessage->id);
            
        } catch (\Exception $e) {
            Log::error("Error sending message", ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to send message: ' . $e->getMessage());
        }
        
        Log::info('sendMessage completed without waiting for AI');
    }

    /**
     * Process AI response for a user message
     * This method is called separately after the user message is displayed
     */
    public function processAiResponse($messageId)
    {
        Log::info('processAiResponse started for message: ' . $messageId);
        
        try {
            $userMessage = ChatMessage::find($messageId);
            
            if (!$userMessage) {
                Log::error('User message not found', ['id' => $messageId]);
                return;
            }
            
            $chatModel = $userMessage->chat;
            
            if (!$chatModel) {
                Log::error('Chat not found for message', ['message_id' => $messageId]);
                return;
            }
            
            // Make sure we have access to the vault
            if (!isset($this->vault) || !$this->vault) {
                // Get vault from chat model
                $this->vault = $chatModel->vault;
                
                if (!$this->vault) {
                    Log::error('Vault not found for chat', ['chat_id' => $chatModel->id]);
                    return;
                }
                
                Log::info('Retrieved vault: ' . $this->vault->id);
            }
            
            // Check if API key is configured
            $apiKey = config('chat.openai.api_key');
            if (empty($apiKey) || strpos($apiKey, 'sk-') !== 0) {
                Log::warning('OpenAI API key is not properly configured');
                $dummyResponse = "I'm sorry, but I can't process your request right now. The AI service is not properly configured. Please contact the administrator to set up the OpenAI API key.";
                
                // Create a dummy assistant message
                $assistantMessage = $chatModel->messages()->create([
                    'role' => 'assistant',
                    'content' => $dummyResponse,
                ]);
                
                Log::info('Created dummy assistant message: ' . $assistantMessage->id);
                
                $this->loadMessages($chatModel);
                return;
            }
            
            Log::info('About to call AI service in separate method');
            
            try {
                // Get RAG service and generate AI response
                $ragService = app(\App\Services\RAGService::class);
                
                // Process the message with RAG
                Log::info('Calling processQuery in separate method');
                $response = $ragService->processQuery($this->vault, $userMessage->content);
                Log::info('Received AI response in separate method');
                
                // Create assistant message
                $assistantMessage = $chatModel->messages()->create([
                    'role' => 'assistant',
                    'content' => $response,
                ]);
                
                Log::info('Created assistant message in database: ' . $assistantMessage->id);
                
                // Make sure we have active chat set
                if (!$this->activeChat) {
                    $this->setActiveChat($chatModel->id);
                    Log::info('Set active chat: ' . $chatModel->id);
                }
                
                // Replace the temp thinking message with the real response
                foreach ($this->messages as $key => $message) {
                    if (isset($message['id']) && $message['id'] === 'temp-thinking') {
                        // Remove the temp message
                        unset($this->messages[$key]);
                        break;
                    }
                }
                
                // Reload messages to show the AI response
                $this->loadMessages($chatModel);
                Log::info('Loaded all messages including AI response');
                
            } catch (\Exception $aiError) {
                Log::error('Error generating AI response: ' . $aiError->getMessage(), [
                    'stack_trace' => $aiError->getTraceAsString()
                ]);
                
                // Provide a friendly error message as the assistant response
                $errorResponse = "I'm sorry, but I encountered an error while processing your request. The system administrator has been notified.";
                
                $assistantMessage = $chatModel->messages()->create([
                    'role' => 'assistant',
                    'content' => $errorResponse,
                ]);
                
                Log::info('Created error assistant message: ' . $assistantMessage->id);
                
                $this->loadMessages($chatModel);
            }
        } catch (\Exception $e) {
            Log::error("Error processing AI response", [
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            // If we encounter an error here, we should try to provide some UI feedback
            $this->dispatch('ai-response-error', message: 'Failed to process AI response: ' . $e->getMessage());
        }
        
        Log::info('processAiResponse completed');
    }

    /**
     * Process AI response with context from multiple selected notes
     * This is called from the ChatSidebar component
     */
    public function processAiResponseWithContext($messageId, array $nodeIds = [])
    {
        Log::info('processAiResponseWithContext started', [
            'message_id' => $messageId,
            'node_ids' => $nodeIds
        ]);
        
        try {
            // Get the response message placeholder
            $responseMessage = ChatMessage::find($messageId);
            
            if (!$responseMessage || $responseMessage->role !== 'assistant') {
                Log::error('Invalid response message', ['id' => $messageId]);
                return;
            }
            
            $chatModel = $responseMessage->chat;
            
            if (!$chatModel) {
                Log::error('Chat not found for message', ['message_id' => $messageId]);
                return;
            }
            
            // Get the user's query (most recent user message before this assistant message)
            $userMessage = ChatMessage::where('chat_id', $chatModel->id)
                ->where('role', 'user')
                ->where('created_at', '<', $responseMessage->created_at)
                ->orderByDesc('created_at')
                ->first();
                
            if (!$userMessage) {
                Log::error('No user message found before assistant message', [
                    'chat_id' => $chatModel->id, 
                    'assistant_message_id' => $messageId
                ]);
                return;
            }
            
            // Make sure we have access to the vault
            if (!isset($this->vault) || !$this->vault) {
                $this->vault = $chatModel->vault;
                
                if (!$this->vault) {
                    Log::error('Vault not found for chat', ['chat_id' => $chatModel->id]);
                    return;
                }
            }
            
            // Check if API key is configured
            $apiKey = config('chat.openai.api_key');
            if (empty($apiKey) || strpos($apiKey, 'sk-') !== 0) {
                Log::warning('OpenAI API key is not properly configured');
                $errorResponse = "I'm sorry, but I can't process your request right now. The AI service is not properly configured. Please contact the administrator to set up the OpenAI API key.";
                
                $responseMessage->update(['content' => $errorResponse]);
                $this->dispatch('chatUpdated');
                return;
            }
            
            // Get RAG service
            $ragService = app(\App\Services\RAGService::class);
            
            try {
                // Process differently based on whether we have specific note context
                if (empty($nodeIds)) {
                    // If no specific notes, use the standard RAG process
                    Log::info('Using standard RAG process (no specific notes)');
                    $response = $ragService->processQuery($this->vault, $userMessage->content);
                } else {
                    // When specific notes are provided, use them as explicit context
                    Log::info('Using specific notes as context', ['note_count' => count($nodeIds)]);
                    
                    // Get the content from the specified notes
                    $notesContent = '';
                    $nodes = VaultNode::whereIn('id', $nodeIds)->get();
                    
                    foreach ($nodes as $node) {
                        $notesContent .= "\n\n### " . $node->name . " ###\n" . $node->content;
                    }
                    
                    // Create a custom system prompt for multi-note context
                    $systemPrompt = "You are a helpful assistant answering questions about notes. Use the following notes as context to answer the user's question. If the information isn't in the notes, you can use your general knowledge but make it clear you're doing so.";
                    
                    // Use the explicit context method
                    $response = $ragService->processQueryWithExplicitContext(
                        $this->vault,
                        $userMessage->content,
                        $systemPrompt,
                        $notesContent
                    );
                }
                
                // Update the response message with the generated content
                $responseMessage->update(['content' => $response]);
                Log::info('Updated response message', ['id' => $responseMessage->id]);
                
                // Notify the ChatSidebar component that messages have been updated
                $this->dispatch('chatUpdated');
                
            } catch (\Exception $aiError) {
                Log::error('Error generating AI response with context', [
                    'error' => $aiError->getMessage(),
                    'trace' => $aiError->getTraceAsString()
                ]);
                
                $errorResponse = "I'm sorry, but I encountered an error processing your request. The system administrator has been notified.";
                $responseMessage->update(['content' => $errorResponse]);
                $this->dispatch('chatUpdated');
            }
            
        } catch (\Exception $e) {
            Log::error('Error in processAiResponseWithContext', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('ai-response-error', message: 'Failed to process response: ' . $e->getMessage());
        }
    }

    public function hydrate()
    {
        if ($this->showModal) {
            $this->dispatch('modal-state-changed', state: true);
        }
    }

    public function dehydrate()
    {
        // This method runs before the component is dehydrated for a Livewire response
        Log::info("Component dehydrating", [
            'modal_state' => $this->showModal,
            'chat_count' => count($this->chats)
        ]);
    }

    public function render()
    {
        // Ensure we re-render when messages change
        $messages = $this->messages;
        $activeChat = $this->activeChat;
        
        // Set a small timeout before rendering to ensure message updates are visible
        usleep(10000); // 10ms delay
        
        return view('livewire.vault.chat', [
            'JobStatus' => \App\Models\JobStatus::class,
        ]);
    }

    public function generateEmbeddings()
    {
        $this->indexingStatus = "Starting indexing process...";
        
        try {
            // Check if there's already an active job status for this vault
            $existingJobStatus = JobStatus::where('vault_id', $this->vault->id)
                ->where('type', JobStatus::TYPE_EMBEDDING)
                ->whereIn('status', [JobStatus::STATUS_PENDING, JobStatus::STATUS_PROCESSING])
                ->first();
                
            if ($existingJobStatus) {
                // Don't start a new job if one is already running
                $this->indexingStatus = "Indexing is already in progress. Please wait for it to complete.";
                $this->isPolling = true;
                $this->dispatch('startJobStatusPolling');
                return;
            }
            
            // Clear any existing job in the queue for this vault
            DB::table('jobs')
                ->where('queue', 'embeddings')
                ->where('payload', 'like', '%"vault_id":' . $this->vault->id . '%')
                ->delete();
            
            // Create and dispatch the job directly
            $job = new \App\Jobs\GenerateEmbeddingsJob($this->vault);
            
            dispatch($job)
                ->onQueue('embeddings')
                ->afterCommit()
                ->delay(now()->addSeconds(5));
            
            // Start polling for job status
            $this->isPolling = true;
            $this->dispatch('startJobStatusPolling');
            
            // Log that we've queued the job
            Log::info('Embedding generation job dispatched for vault: ' . $this->vault->id);
            
            // Check if the job was queued
            $pendingJobs = DB::table('jobs')->where('queue', 'embeddings')->count();
            Log::info('Pending embedding jobs in queue: ' . $pendingJobs);
            
            $this->indexingStatus = "Notes indexing has started in the background. This process may take several minutes to complete.";
            
            // Don't automatically clear the status for background processes
        } catch (\Exception $e) {
            $this->indexingStatus = "Error starting indexing process: " . $e->getMessage();
            Log::error('Error in generateEmbeddings Livewire method: ' . $e->getMessage());
            
            // Set a timer to clear error messages
            $this->dispatch('clearIndexingStatus');
        }
    }
    
    /**
     * Check the status of the embedding job
     */
    public function checkJobStatus()
    {
        try {
            // Also refresh the embedding percentage
            $this->refreshEmbeddingPercentage();
            
            $status = JobStatus::where('vault_id', $this->vault->id)
                ->where('type', JobStatus::TYPE_EMBEDDING)
                ->latest()
                ->first();
            
            if ($status) {
                $this->jobStatus = [
                    'status' => $status->status,
                    'message' => $status->message,
                    'progress' => $status->progress,
                    'updated_at' => $status->updated_at->diffForHumans()
                ];
                
                // Update the indexing status based on job status
                if ($status->status == JobStatus::STATUS_COMPLETED) {
                    $this->indexingStatus = "✅ " . $status->message;
                    $this->isPolling = false;
                } elseif ($status->status == JobStatus::STATUS_FAILED) {
                    $this->indexingStatus = "❌ " . $status->message;
                    $this->isPolling = false;
                } else {
                    $this->indexingStatus = "⏳ " . $status->message . " (" . $status->progress . "%)";
                }
            }
        } catch (\Exception $e) {
            Log::error('Error checking job status: ' . $e->getMessage());
            $this->isPolling = false;
        }
    }
    
    // Add method to clear indexing status
    public function clearIndexingStatus()
    {
        $this->indexingStatus = null;
    }

    /**
     * Calculate what percentage of notes have embeddings with matching hashes
     * 
     * @return int
     */
    protected function getEmbeddingCompletionPercentage(): int
    {
        try {
            // Get total count of notes in the vault
            $totalNodes = VaultNode::where('vault_id', $this->vault->id)
                ->where('is_file', 1) // Using 1 instead of true for PostgreSQL smallint
                ->count();
            
            if ($totalNodes === 0) {
                return 100; // No notes to index
            }
            
            // Count notes with valid embeddings by comparing stored content_hash
            // Only count embeddings where both the node and embedding have non-null matching content hashes
            $validEmbeddings = DB::select("
                SELECT COUNT(DISTINCT vn.id) as count
                FROM vault_nodes vn
                JOIN embeddings e ON vn.id = e.note_id
                WHERE vn.vault_id = ?
                AND vn.is_file = 1
                AND vn.content_hash IS NOT NULL
                AND e.content_hash IS NOT NULL
                AND vn.content_hash = e.content_hash
            ", [$this->vault->id])[0]->count;
            
            // For debugging
            Log::info('Embedding completion calculation', [
                'totalNodes' => $totalNodes,
                'validEmbeddings' => $validEmbeddings,
                'percentage' => (int) round(($validEmbeddings / $totalNodes) * 100)
            ]);
            
            // Calculate percentage
            $percentage = (int) round(($validEmbeddings / $totalNodes) * 100);
            
            return $percentage;
        } catch (\Exception $e) {
            Log::error('Error calculating embedding completion percentage: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            return 0;
        }
    }

    /**
     * Refresh the embedding percentage
     */
    public function refreshEmbeddingPercentage()
    {
        try {
            // Get embedding percentage
            $this->embeddingPercentage = $this->getEmbeddingCompletionPercentage();
        } catch (\Exception $e) {
            Log::error('Error refreshing embedding percentage: ' . $e->getMessage());
            // Set a reasonable default if there's an error
            $this->embeddingPercentage = 0;
        }
    }
    
    // Placeholder for online user handling
    public function refreshOnlineUsers($users)
    {
        // This can be implemented later if needed
    }
    
    // Placeholder for user joining event
    public function userJoining($user)
    {
        // This can be implemented later if needed
    }
    
    // Placeholder for user leaving event
    public function userLeaving($user)
    {
        // This can be implemented later if needed
    }
}
