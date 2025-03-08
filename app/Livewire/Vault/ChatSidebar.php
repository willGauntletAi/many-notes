<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use App\Models\VaultChat;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\VaultNode;
use Illuminate\Support\Collection;

class ChatSidebar extends Component
{
    public Vault $vault;
    public ?VaultNode $currentNode = null;
    public ?array $activeChat = null;
    public array $chats = [];
    public array $messages = [];
    public string $messageText = '';
    public Collection $includedNodes;
    public bool $showAddNoteSearch = false;
    public string $noteSearchQuery = '';
    public array $searchResults = [];
    
    protected $listeners = [
        'chatUpdated' => 'loadMessages',
        'noteAdded' => 'refreshIncludedNodes',
    ];
    
    public function mount(Vault $vault, ?VaultNode $node = null)
    {
        $this->vault = $vault;
        $this->currentNode = $node;
        $this->includedNodes = collect([]);
        $this->loadChats();
        
        // Add current node to chat context if provided
        if ($node) {
            $this->addNodeToContext($node);
        }
        
        // Auto-select the first chat or create a default one if none exists
        if (count($this->chats) > 0) {
            $this->setActiveChat($this->chats[0]['id']);
        } else {
            $this->createDefaultChat();
        }
    }
    
    public function loadChats()
    {
        try {
            $this->chats = VaultChat::where('vault_id', $this->vault->id)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($chat) {
                    return [
                        'id' => $chat->id,
                        'name' => $chat->name,
                    ];
                })->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading chats: ' . $e->getMessage());
            $this->chats = [];
        }
    }
    
    public function setActiveChat($chatId)
    {
        try {
            $chat = VaultChat::find($chatId);
            if ($chat) {
                $this->activeChat = [
                    'id' => $chat->id,
                    'name' => $chat->name,
                ];
                
                // Load the notes attached to this chat
                $this->loadChatNodes($chat);
                
                $this->loadMessages($chat);
            }
        } catch (\Exception $e) {
            Log::error('Error setting active chat: ' . $e->getMessage());
        }
    }
    
    protected function loadChatNodes(VaultChat $chat)
    {
        // Assuming chat has a many-to-many relationship with nodes
        // If not, this will need to be adjusted based on your data model
        $this->includedNodes = $chat->nodes ?? collect([]);
        
        // If current node is not in included nodes, add it
        if ($this->currentNode && !$this->includedNodes->contains('id', $this->currentNode->id)) {
            $this->addNodeToContext($this->currentNode);
        }
    }
    
    public function addNodeToContext(VaultNode $node)
    {
        // Only add if not already in the list
        if (!$this->includedNodes->contains('id', $node->id)) {
            $this->includedNodes->push($node);
            
            // If we have an active chat, save the association
            if ($this->activeChat) {
                try {
                    $chat = VaultChat::find($this->activeChat['id']);
                    if ($chat) {
                        // Assuming a many-to-many relationship setup
                        // If not, modify based on your actual data model
                        $chat->nodes()->syncWithoutDetaching([$node->id]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error adding node to chat: ' . $e->getMessage());
                }
            }
            
            // Let other components know the chat context has changed
            $this->dispatch('chatContextChanged', $node->id);
        }
    }
    
    public function removeNodeFromContext($nodeId)
    {
        $this->includedNodes = $this->includedNodes->reject(function ($node) use ($nodeId) {
            return $node->id == $nodeId;
        });
        
        // If we have an active chat, remove the association
        if ($this->activeChat) {
            try {
                $chat = VaultChat::find($this->activeChat['id']);
                if ($chat) {
                    // Assuming a many-to-many relationship setup
                    $chat->nodes()->detach($nodeId);
                }
            } catch (\Exception $e) {
                Log::error('Error removing node from chat: ' . $e->getMessage());
            }
        }
        
        // Let other components know the chat context has changed
        $this->dispatch('chatContextChanged', $nodeId);
    }
    
    public function toggleNoteSearch()
    {
        $this->showAddNoteSearch = !$this->showAddNoteSearch;
        $this->searchResults = [];
        $this->noteSearchQuery = '';
    }
    
    public function updatedNoteSearchQuery()
    {
        // When the search query changes, automatically perform the search
        $this->searchNotes();
    }
    
    public function searchNotes()
    {
        try {
            // Clear results if the query is too short
            if (strlen($this->noteSearchQuery) < 2) {
                $this->searchResults = [];
                return;
            }
            
            // Sanitize query and make it SQL-safe
            $query = trim($this->noteSearchQuery);
            $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $query);
            
            // Perform the search
            $results = VaultNode::where('vault_id', $this->vault->id)
                ->where('is_file', true)
                ->where('extension', 'md')
                ->where(function($q) use ($escapedQuery) {
                    $q->where('name', 'like', "%{$escapedQuery}%");
                })
                ->orderBy('updated_at', 'desc')
                ->limit(8)
                ->get();
            
            // Prepare results for display
            $this->searchResults = $results->map(function ($node) {
                return [
                    'id' => $node->id,
                    'name' => $node->name,
                ];
            })->toArray();
            
            Log::info('Note search results', [
                'query' => $query, 
                'count' => count($this->searchResults),
                'vault_id' => $this->vault->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching notes: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            $this->searchResults = [];
            
            // Let the user know something went wrong
            $this->dispatch('toast', message: 'Error searching notes', type: 'error');
        }
    }
    
    public function addNoteFromSearch($nodeId)
    {
        try {
            $node = VaultNode::find($nodeId);
            if ($node) {
                Log::info('Adding note from search', ['node_id' => $node->id, 'name' => $node->name]);
                $this->addNodeToContext($node);
                $this->showAddNoteSearch = false;
                $this->searchResults = [];
                $this->noteSearchQuery = '';
                
                // Display a notification to the user
                $this->dispatch('toast', message: "Added note '{$node->name}' to chat", type: 'success');
            } else {
                Log::warning('Note not found for adding to context', ['node_id' => $nodeId]);
                $this->dispatch('toast', message: "Note not found", type: 'error');
            }
        } catch (\Exception $e) {
            Log::error('Error adding note from search: ' . $e->getMessage());
            $this->dispatch('toast', message: "Error adding note: {$e->getMessage()}", type: 'error');
        }
    }
    
    public function refreshIncludedNodes()
    {
        if ($this->activeChat) {
            try {
                $chat = VaultChat::find($this->activeChat['id']);
                if ($chat) {
                    $this->loadChatNodes($chat);
                }
            } catch (\Exception $e) {
                Log::error('Error refreshing included nodes: ' . $e->getMessage());
            }
        }
    }
    
    public function loadMessages($chatModel = null)
    {
        if (!$this->activeChat && !$chatModel) {
            $this->messages = [];
            return;
        }
        
        try {
            $chatId = $chatModel ? $chatModel->id : $this->activeChat['id'];
            $this->messages = ChatMessage::where('chat_id', $chatId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'role' => $message->role,
                        'content' => $message->content,
                        'created_at' => $message->created_at->format('M j, g:i a'),
                    ];
                })->toArray();
                
            $this->dispatch('messages-loaded');
        } catch (\Exception $e) {
            Log::error('Error loading messages: ' . $e->getMessage());
            $this->messages = [];
        }
    }
    
    public function sendMessage()
    {
        if (empty(trim($this->messageText)) || !$this->activeChat) {
            return;
        }
        
        try {
            // Save user message
            $message = new ChatMessage();
            $message->chat_id = $this->activeChat['id'];
            $message->role = 'user';
            $message->content = $this->messageText;
            $message->save();
            
            $this->messageText = '';
            $this->loadMessages();
            
            // Create AI response message
            $responseMessage = new ChatMessage();
            $responseMessage->chat_id = $this->activeChat['id'];
            $responseMessage->role = 'assistant';
            $responseMessage->content = '...';
            $responseMessage->save();
            
            // Update chat timestamp
            VaultChat::where('id', $this->activeChat['id'])->update(['updated_at' => now()]);
            
            // Load messages to show the placeholder
            $this->loadMessages();
            
            // Process AI response in the background, including context from multiple notes
            $nodeIds = $this->includedNodes->pluck('id')->toArray();
            $this->dispatch('processAiResponseWithContext', $responseMessage->id, $nodeIds)->to(Chat::class);
        } catch (\Exception $e) {
            Log::error('Error sending message: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a default chat when none exists
     */
    protected function createDefaultChat()
    {
        try {
            // Create a new chat named after the current note or a default name
            $chatName = $this->currentNode ? 'Chat about ' . $this->currentNode->name : 'New Chat';
            
            $chat = new VaultChat();
            $chat->vault_id = $this->vault->id;
            $chat->name = $chatName;
            $chat->save();
            
            // Reload chats and select the new one
            $this->loadChats();
            $this->setActiveChat($chat->id);
            
            Log::info('Created default chat', ['chat_id' => $chat->id, 'name' => $chatName]);
        } catch (\Exception $e) {
            Log::error('Error creating default chat: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.vault.chat-sidebar');
    }
} 