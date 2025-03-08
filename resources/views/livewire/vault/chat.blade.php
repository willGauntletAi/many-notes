<div 
    id="chat-component"
    wire:id="{{ $_instance->getId() }}"
    class="flex flex-row h-full"
    x-data="{ 
        showModalAlpine: @entangle('showModal').live,
        toggleModal() {
            this.showModalAlpine = !this.showModalAlpine;
            $wire.set('showModal', this.showModalAlpine);
        },
        scrollToBottom() {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
    }"
    x-init="
        $wire.on('modal-state-changed', ({ state }) => {
            showModalAlpine = state;
            
            // Force immediate DOM update when state changes
            if (state) {
                document.getElementById('direct-modal').style.display = 'flex';
            } else {
                document.getElementById('direct-modal').style.display = 'none';
            }
        });
        
        $wire.on('state-reset', () => {
            showModalAlpine = false;
            document.getElementById('direct-modal').style.display = 'none';
        });
        
        $wire.on('clearIndexingStatus', () => {
            // Wait 5 seconds then clear the status
            setTimeout(() => {
                $wire.clearIndexingStatus();
            }, 5000);
        });
        
        // Scroll to bottom when messages are loaded
        $wire.on('messages-loaded', () => {
            scrollToBottom();
        });
        
        $wire.on('startJobStatusPolling', () => {
            // Poll every 2 seconds for job status updates
            let pollInterval = setInterval(() => {
                if (!$wire.isPolling) {
                    clearInterval(pollInterval);
                    // Do one final refresh of the embedding percentage
                    $wire.refreshEmbeddingPercentage();
                    return;
                }
                $wire.checkJobStatus();
            }, 2000);
            
            // Initial check immediately
            $wire.checkJobStatus();
        });
        
        // Periodically refresh embedding percentage (every 30 seconds)
        setInterval(() => {
            // Only refresh if not actively polling job status
            if (!$wire.isPolling) {
                $wire.refreshEmbeddingPercentage();
            }
        }, 30000);
    "
>
    <!-- Chat List Sidebar -->
    <div class="w-1/4 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full">
        <div class="flex justify-between items-center mb-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Chats</h2>
            <div class="flex space-x-1">
                <button 
                    type="button" 
                    class="px-2 py-1 bg-green-600 dark:bg-green-700 text-white rounded hover:bg-green-700 dark:hover:bg-green-800 text-sm"
                    wire:click="generateEmbeddings"
                    wire:loading.attr="disabled"
                    title="Index your notes to make them available for AI chat"
                >
                    Index Notes
                </button>
                <button 
                    type="button" 
                    class="px-2 py-1 bg-indigo-600 dark:bg-indigo-700 text-white rounded hover:bg-indigo-700 dark:hover:bg-indigo-800 text-sm"
                    wire:click="openModal"
                    id="new-chat-button"
                >
                    New Chat
                </button>
            </div>
        </div>

        <!-- Progress Bar for Indexing -->
        <div class="mb-3 flex items-center">
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 flex-grow">
                <div class="bg-green-600 h-2 rounded-full dark:bg-green-500" style="width: {{ max(2, $embeddingPercentage) }}%"></div>
            </div>
            <div class="ml-2 text-xs text-gray-600 dark:text-gray-400 flex items-center">
                {{ $embeddingPercentage }}% indexed
                @if(($jobStatus && $jobStatus['status'] != $JobStatus::STATUS_COMPLETED && $jobStatus['status'] != $JobStatus::STATUS_FAILED) || $isPolling)
                    <svg class="animate-spin ml-1 h-3 w-3 text-green-600 dark:text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                @endif
            </div>
        </div>
        
        @if($indexingStatus)
            <div class="mb-4 p-2 rounded flex justify-between items-center 
                {{ str_contains($indexingStatus, '✅') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                   (str_contains($indexingStatus, '❌') ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                   'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                <div class="flex-1">
                    <span>{{ $indexingStatus }}</span>
                </div>
                <button 
                    type="button"
                    class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 ml-2"
                    wire:click="clearIndexingStatus"
                    title="Dismiss message"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif
        
        <div class="overflow-y-auto flex-grow">
            @if(count($chats) === 0)
                <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                    No chats yet. Create your first chat to get started.
                </div>
            @else
                <ul class="space-y-2">
                    @foreach($chats as $chat)
                        <li>
                            <div class="w-full text-left px-3 py-2 rounded {{ $activeChat && $activeChat['id'] == $chat['id'] ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200' }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <button 
                                            wire:click="setActiveChat({{ $chat['id'] }})"
                                            class="w-full text-left truncate"
                                        >
                                            {{ $chat['name'] }}
                                        </button>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ \Carbon\Carbon::parse($chat['created_at'])->format('M d, Y') }}
                                        </div>
                                    </div>
                                    <button 
                                        wire:click.stop="deleteChat({{ $chat['id'] }})"
                                        class="text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400 ml-2"
                                        title="Delete chat"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    
    <!-- Chat Area -->
    <div class="flex-1 flex flex-col bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700">
        @if($activeChat)
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ $activeChat['name'] }}</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages" 
                x-init="
                    // Scroll to bottom when initialized
                    scrollToBottom();
                    
                    // Observe for changes in the chat-messages div
                    const observer = new MutationObserver(() => {
                        scrollToBottom();
                    });
                    
                    // Start observing the chat messages container
                    observer.observe($el, { childList: true, subtree: true });
                "
                wire:poll.visible.5s
            >
                @foreach($messages as $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}" wire:key="message-{{ $message['id'] ?? 'temp-'.uniqid() }}">
                        <div class="{{ $message['role'] === 'user' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }} rounded-lg px-4 pt-2 pb-1 max-w-md {{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}">
                            <div class="text-sm {{ $message['role'] === 'user' ? 'text-right' : 'text-left' }} leading-tight p-0 m-0 block">@if(isset($message['is_loading']) && $message['is_loading'])<div class="flex items-center"><div class="loader mr-2"><div class="typing-indicator"><span></span><span></span><span></span></div></div><span>AI is thinking...</span></div>@else<span class="{{ $message['role'] === 'user' ? 'text-right' : 'text-left' }} inline-block w-full">{{ $message['content'] }}</span>@endif</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 leading-none p-0" style="margin-top: 2px;">{{ \Carbon\Carbon::parse($message['created_at'])->format('g:i A') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                    <input 
                        wire:model="messageText" 
                        type="text" 
                        placeholder="Type your message..." 
                        class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 !bg-[#e5e7eb] dark:!bg-[#374151] text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        x-on:keydown.enter="$nextTick(() => { scrollToBottom(); })"
                    >
                    <button 
                        type="submit" 
                        class="bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded"
                        x-on:click="$nextTick(() => { scrollToBottom(); })"
                    >
                        Send
                    </button>
                </form>
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center p-4 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Select a chat or create a new one</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Choose an existing chat from the sidebar or create a new one to get started.</p>
                <button 
                    wire:click="openModal"
                    class="bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded"
                    id="empty-state-new-chat-button"
                >
                    Create New Chat
                </button>
            </div>
        @endif
    </div>
    
    <!-- Notification Area -->
    <div id="notification-area" class="fixed top-4 right-4 z-[400]">
        @if(session()->has('message'))
            <div class="bg-green-500 text-white px-4 py-2 rounded shadow-lg">
                {{ session('message') }}
            </div>
        @endif
        
        @if(session()->has('error'))
            <div class="bg-red-500 text-white px-4 py-2 rounded shadow-lg">
                {{ session('error') }}
            </div>
        @endif
    </div>
    
    <!-- Alpine.js modal -->
    <div 
        id="chat-modal"
        class="fixed inset-0 flex items-center justify-center z-50" 
        style="background-color: rgba(0,0,0,0.5);"
        x-show="showModalAlpine"
        x-cloak
    >
        <div 
            class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-md w-full relative z-50"
            @click.outside="$wire.closeModal()"
        >
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Create New Chat</h3>
                <button 
                    class="text-gray-400 hover:text-gray-500" 
                    wire:click="closeModal"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="mb-4">
                <label for="newChatName" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Chat Name
                </label>
                <input 
                    type="text" 
                    id="newChatName" 
                    wire:model="newChatName" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-300"
                    placeholder="Enter chat name"
                    x-on:keydown.enter.prevent="$wire.createChat()"
                    autofocus
                >
                @error('newChatName') 
                    <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> 
                @enderror
            </div>
            
            <div class="flex justify-end">
                <button 
                    type="button" 
                    class="px-4 py-2 mr-2 text-sm font-medium text-gray-700 bg-white dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none"
                    wire:click="closeModal"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    wire:click="createChat"
                >
                    Create
                </button>
            </div>
        </div>
    </div>
    
    <!-- Direct DOM modal (kept for stability) -->
    <div 
        id="direct-modal"
        class="fixed inset-0 items-center justify-center z-[100]" 
        style="background-color: rgba(0,0,0,0.5); display: none;"
    >
        <div 
            class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-md w-full relative z-50 m-auto"
        >
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Create New Chat</h3>
                <button 
                    class="text-gray-400 hover:text-gray-500" 
                    onclick="closeDirectModal()"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="mb-4">
                <label for="directChatName" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Chat Name
                </label>
                <input 
                    type="text" 
                    id="directChatName" 
                    wire:model="newChatName" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-300"
                    placeholder="Enter chat name"
                    autofocus
                >
                @error('newChatName') 
                    <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> 
                @enderror
            </div>
            
            <div class="flex justify-end">
                <button 
                    type="button" 
                    class="px-4 py-2 mr-2 text-sm font-medium text-gray-700 bg-white dark:bg-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none"
                    onclick="closeDirectModal()"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    id="create-chat-button"
                    onclick="createChatFromDirect()"
                >
                    Create
                </button>
            </div>
        </div>
    </div>

<script>
    // Helper functions for direct modal
    function getLivewireComponent() {
        const componentId = document.getElementById('chat-component').getAttribute('wire:id');
        return window.Livewire.find(componentId);
    }
    
    function closeDirectModal() {
        document.getElementById('direct-modal').style.display = 'none';
        const component = getLivewireComponent();
        if (component) {
            component.call('closeModal');
        }
    }
    
    function createChatFromDirect() {
        const component = getLivewireComponent();
        if (component) {
            // Get the input value before calling createChat
            const chatNameInput = document.getElementById('directChatName');
            const chatName = chatNameInput ? chatNameInput.value : '';
            
            if (!chatName || chatName.trim().length < 3) {
                alert('Chat name is required and must be at least 3 characters');
                return;
            }
            
            // Set the newChatName property directly first
            component.set('newChatName', chatName);
            
            // Then call the createChat method
            component.call('createChat');
        } else {
            alert('Error: Could not find Livewire component. Please refresh the page.');
        }
    }
    
    // Handle Enter key on direct modal input
    document.addEventListener('DOMContentLoaded', function() {
        const directChatNameInput = document.getElementById('directChatName');
        if (directChatNameInput) {
            directChatNameInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    createChatFromDirect();
                }
            });
        }
        
        // Listen for process-ai-response event
        window.Livewire.on('process-ai-response', async (data) => {
            console.log('Received process-ai-response event', data);
            
            // Small delay to ensure DOM is updated first
            setTimeout(() => {
                // Get the Livewire component
                const component = getLivewireComponent();
                if (component) {
                    // Call the processAiResponse method
                    console.log('Calling processAiResponse with message ID:', data.messageId);
                    component.call('processAiResponse', data.messageId);
                }
            }, 500); // Half-second delay
        });
        
        // Listen for AI response errors
        window.Livewire.on('ai-response-error', (data) => {
            console.error('AI response error:', data.message);
            
            // Show an error notification to the user
            const notificationArea = document.getElementById('notification-area');
            if (notificationArea) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-500 text-white px-4 py-2 rounded shadow-lg mb-2';
                errorDiv.textContent = data.message;
                
                notificationArea.appendChild(errorDiv);
                
                // Remove the notification after 5 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode === notificationArea) {
                        notificationArea.removeChild(errorDiv);
                    }
                }, 5000);
            }
        });
    });
</script>

<style>
    [x-cloak] { display: none !important; }
    
    /* Modal display rules */
    #direct-modal[style*="display: flex"] {
        display: flex !important;
    }
    
    /* Typing indicator animation */
    .typing-indicator {
        display: flex;
        align-items: center;
    }
    
    .typing-indicator span {
        height: 6px;
        width: 6px;
        background-color: #606060;
        border-radius: 50%;
        display: inline-block;
        margin: 0 1px;
        animation: typing 1.4s infinite ease-in-out;
    }
    
    .typing-indicator span:nth-child(1) {
        animation-delay: 0s;
    }
    
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    
    @keyframes typing {
        0% {
            transform: translateY(0px);
            opacity: 0.4;
        }
        50% {
            transform: translateY(-5px);
            opacity: 0.9;
        }
        100% {
            transform: translateY(0px);
            opacity: 0.4;
        }
    }
    
    .dark .typing-indicator span {
        background-color: #a0a0a0;
    }
</style>
