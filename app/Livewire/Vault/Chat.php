<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use App\Models\VaultChat;
use App\Models\ChatMessage;
use App\Services\RAGService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Chat extends Component
{
    public Vault $vault;
    public ?VaultChat $activeChat = null;
    public $chats = [];
    public $messages = [];
    public $newChatName = '';
    public $messageContent = '';
    public $isProcessing = false;
    
    protected $rules = [
        'newChatName' => 'required|string|max:255',
        'messageContent' => 'required|string',
    ];
    
    public function mount(Vault $vault, $chatId = null)
    {
        $this->vault = $vault;
        $this->loadChats();
        
        if ($chatId) {
            $this->setActiveChat($chatId);
        }
    }
    
    public function loadChats()
    {
        $this->chats = $this->vault->chats()->orderBy('created_at', 'desc')->get();
    }
    
    public function setActiveChat($chatId)
    {
        $this->activeChat = $this->vault->chats()->findOrFail($chatId);
        $this->loadMessages();
    }
    
    public function loadMessages()
    {
        if ($this->activeChat) {
            $this->messages = $this->activeChat->messages()->orderBy('created_at', 'asc')->get();
        }
    }
    
    public function createChat()
    {
        $this->validate([
            'newChatName' => 'required|string|max:255',
        ]);
        
        $chat = $this->vault->chats()->create([
            'name' => $this->newChatName,
        ]);
        
        $this->newChatName = '';
        $this->loadChats();
        $this->setActiveChat($chat->id);
    }
    
    public function deleteChat($chatId)
    {
        $chat = $this->vault->chats()->findOrFail($chatId);
        $chat->delete();
        
        $this->loadChats();
        $this->activeChat = null;
        $this->messages = [];
    }
    
    public function sendMessage(RAGService $ragService)
    {
        $this->validate([
            'messageContent' => 'required|string',
        ]);
        
        if (!$this->activeChat) {
            $this->addError('messageContent', 'No active chat selected.');
            return;
        }
        
        // Create user message
        $userMessage = $this->activeChat->messages()->create([
            'role' => 'user',
            'content' => $this->messageContent,
        ]);
        
        $this->messages->push($userMessage);
        $content = $this->messageContent;
        $this->messageContent = '';
        $this->isProcessing = true;
        
        try {
            // Process the message with RAG
            $response = $ragService->processQuery($this->vault, $content);
            
            // Create assistant message
            $assistantMessage = $this->activeChat->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);
            
            $this->messages->push($assistantMessage);
        } catch (\Exception $e) {
            $this->addError('messageContent', 'Failed to process message: ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }
    
    public function render()
    {
        return view('livewire.vault.chat');
    }
}
