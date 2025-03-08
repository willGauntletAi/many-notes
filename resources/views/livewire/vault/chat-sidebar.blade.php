<div 
    id="chat-sidebar-component"
    class="flex flex-col h-full"
    x-data="{ 
        scrollToBottom() {
            const chatMessages = document.getElementById('sidebar-chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
    }"
    x-init="
        // Scroll to bottom when messages are loaded
        $wire.on('messages-loaded', () => {
            scrollToBottom();
        });
    "
>
    <!-- Chat Selection -->
    <div class="flex-none px-3 py-2 border-b border-gray-200 dark:border-gray-700">
        <select wire:model.live="activeChat.id" wire:change="setActiveChat($event.target.value)" class="w-full px-2 py-1 text-sm bg-light-base-100 dark:bg-base-800 rounded">
            @forelse($chats as $chat)
                <option value="{{ $chat['id'] }}">{{ $chat['name'] }}</option>
            @empty
                <option value="">No chats available</option>
            @endforelse
        </select>
    </div>
    
    <!-- Included Notes Section -->
    <div class="flex-none px-3 py-2 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-sm font-medium">Included Notes</h4>
            <button 
                type="button" 
                wire:click="toggleNoteSearch"
                class="p-1 text-sm text-primary-500 hover:text-primary-700 dark:hover:text-primary-300"
                title="Add Note to Chat Context"
            >
                <x-icons.plus class="w-4 h-4" />
            </button>
        </div>
        
        <!-- List of included notes -->
        <div class="text-xs space-y-1 max-h-24 overflow-y-auto">
            @forelse($includedNodes as $node)
                <div class="flex items-center justify-between py-1 px-2 rounded bg-light-base-100 dark:bg-base-800">
                    <span class="truncate" title="{{ $node->name }}">
                        {{ $node->name }}
                    </span>
                    <button 
                        type="button" 
                        wire:click="removeNodeFromContext({{ $node->id }})" 
                        class="ml-2 text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                        title="Remove from chat context"
                    >
                        <x-icons.xMark class="w-3 h-3" />
                    </button>
                </div>
            @empty
                <div class="text-gray-500 dark:text-gray-400 italic text-center">
                    No notes included
                </div>
            @endforelse
        </div>
        
        <!-- Note search popup -->
        @if($showAddNoteSearch)
            <div class="mt-2 p-2 bg-light-base-100 dark:bg-base-800 rounded shadow-lg">
                <div class="mb-2">
                    <input 
                        type="text" 
                        wire:model="noteSearchQuery"
                        wire:keyup.debounce.300ms="searchNotes"
                        placeholder="Search notes..." 
                        class="w-full p-1 text-xs bg-light-base-50 dark:bg-base-900 border border-gray-300 dark:border-gray-700 rounded"
                    />
                </div>
                
                <div class="max-h-32 overflow-y-auto">
                    @if(count($searchResults) > 0)
                        <div class="space-y-1">
                            @foreach($searchResults as $result)
                                <div 
                                    wire:click="addNoteFromSearch({{ $result['id'] }})"
                                    class="flex items-center text-xs py-1 px-2 hover:bg-light-base-200 dark:hover:bg-base-700 rounded cursor-pointer"
                                >
                                    <x-icons.documentDuplicate class="w-3 h-3 mr-1 flex-shrink-0" />
                                    <span class="truncate">{{ $result['name'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif(strlen($noteSearchQuery) >= 2)
                        <div class="text-xs text-gray-500 dark:text-gray-400 italic text-center py-2">
                            No notes found matching "{{ $noteSearchQuery }}"
                        </div>
                    @else
                        <div class="text-xs text-gray-500 dark:text-gray-400 italic text-center py-2">
                            Type at least 2 characters to search
                        </div>
                    @endif
                </div>
                
                <div class="mt-2 flex justify-end">
                    <button 
                        type="button"
                        wire:click="toggleNoteSearch"
                        class="text-xs px-2 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded"
                    >
                        Close
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Chat Messages -->
    <div id="sidebar-chat-messages" class="flex-1 overflow-y-auto p-2 space-y-3">
        @forelse($messages as $message)
            <div class="text-sm rounded-lg p-2 max-w-[95%] {{ $message['role'] === 'user' ? 'ml-auto bg-primary-100 dark:bg-primary-800' : 'bg-light-base-100 dark:bg-base-800' }}">
                <div class="flex items-start gap-2">
                    @if($message['role'] === 'user')
                        <div class="flex-1 break-words">
                            {!! nl2br(e($message['content'])) !!}
                        </div>
                        <span class="flex-none">
                            <x-icons.userCircle class="w-5 h-5 text-primary-400" />
                        </span>
                    @else
                        <span class="flex-none">
                            <x-icons.sparkles class="w-5 h-5 text-amber-400" />
                        </span>
                        <div class="flex-1 break-words prose-sm prose dark:prose-invert max-w-none">
                            @if($message['content'] === '...')
                                <div class="flex items-center justify-center">
                                    <x-icons.spinner class="w-4 h-4 animate-spin" />
                                </div>
                            @else
                                {!! $message['content'] !!}
                            @endif
                        </div>
                    @endif
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 {{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}">
                    {{ $message['created_at'] }}
                </div>
            </div>
        @empty
            <div class="text-center text-gray-500 dark:text-gray-400 p-4">
                <p>No messages yet</p>
                <p class="text-xs mt-1">Start a conversation about these notes</p>
            </div>
        @endforelse
    </div>

    <!-- Message Input -->
    <div class="flex-none p-2 border-t border-gray-200 dark:border-gray-700">
        <form wire:submit="sendMessage" class="flex items-center gap-2">
            <textarea 
                wire:model="messageText" 
                placeholder="Type your message..."
                class="flex-1 p-2 text-sm bg-light-base-100 dark:bg-base-800 rounded resize-none"
                rows="2"
                @keydown.enter.prevent="if(!event.shiftKey) { $wire.sendMessage(); }"
            ></textarea>
            <button type="submit" class="p-2 rounded-full bg-primary-500 text-white hover:bg-primary-600 disabled:opacity-50" wire:loading.attr="disabled">
                <x-icons.paperAirplane class="w-4 h-4" />
            </button>
        </form>
    </div>
</div> 