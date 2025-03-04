<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use App\Models\VaultChat;
use App\Models\VaultChatMessage;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Chat extends Component
{
    public Vault $vault;
    public ?array $activeChat = null;
    public array $chats = [];
    public array $messages = [];
    public string $messageText = '';
    public string $newChatName = '';
    public bool $showModal = false;
    
    // Make sure listeners are properly defined
    protected $listeners = [
        'openModal', 
        'closeModal',
        'resetState'
    ];

    public function mount(Vault $vault)
    {
        $this->vault = $vault;
        $this->loadChats();
        
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
        
        $model = $chatModel ?: VaultChat::find($this->activeChat['id']);
        
        if (!$model) {
            Log::warning("Cannot load messages - chat model not found");
            return;
        }
        
        $messageModels = $model->messages()->orderBy('created_at', 'asc')->get();
        
        $this->messages = $messageModels->map(function($message) {
            return [
                'id' => $message->id,
                'content' => $message->content,
                'role' => $message->role,
                'created_at' => $message->created_at->toDateTimeString(),
            ];
        })->toArray();
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
            
            $chatModel->messages()->create([
                'content' => $this->messageText,
                'role' => 'user',
            ]);
            
            $this->messageText = '';
            $this->loadMessages($chatModel);
        } catch (\Exception $e) {
            Log::error("Error sending message", ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to send message: ' . $e->getMessage());
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
        return view('livewire.vault.chat');
    }
}
