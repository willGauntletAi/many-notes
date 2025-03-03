<div class="flex h-full" x-data="{ modalOpen: false }">
    <!-- Chat List Sidebar -->
    <div class="w-1/4 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Chats</h2>
            <x-form.button 
                type="button" 
                class="px-2 py-1 bg-indigo-600 dark:bg-indigo-700 text-white rounded hover:bg-indigo-700 dark:hover:bg-indigo-800 text-sm"
                @click="modalOpen = true; $dispatch('modal-opened')"
            >
                New Chat
            </x-form.button>
        </div>
        
        <div class="overflow-y-auto flex-grow">
            @if(count($chats) === 0)
                <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                    No chats yet. Create your first chat to get started.
                </div>
            @else
                <ul class="space-y-2">
                    @foreach($chats as $chat)
                        <li>
                            <button 
                                wire:click="setActiveChat({{ $chat->id }})"
                                class="w-full text-left px-3 py-2 rounded {{ $activeChat && $activeChat->id === $chat->id ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200' }}"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="truncate">{{ $chat->name }}</span>
                                    <button 
                                        wire:click.stop="deleteChat({{ $chat->id }})"
                                        class="text-gray-400 hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                                        title="Delete chat"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $chat->created_at->format('M d, Y') }}
                                </div>
                            </button>
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
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ $activeChat->name }}</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                @foreach($messages as $message)
                    <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="{{ $message->role === 'user' ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }} rounded-lg px-4 py-2 max-w-md">
                            <div class="text-sm whitespace-pre-wrap">{{ $message->content }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $message->created_at->format('g:i A') }}
                            </div>
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
                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                    <button 
                        type="submit" 
                        class="bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded"
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
                    @click="modalOpen = true; $dispatch('modal-opened')" 
                    class="bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded"
                >
                    Create New Chat
                </button>
            </div>
        @endif
    </div>
    
    <!-- New Chat Modal -->
    <template x-teleport="body">
        <div x-show="modalOpen" 
            class="modal-container fixed inset-0 z-[9999] overflow-y-auto"
            style="display: none;"
            x-init="$watch('modalOpen', value => { if(value) $dispatch('modal-opened'); })"
        >
            <!-- Backdrop - Full black with maximum opacity -->
            <div class="modal-backdrop fixed inset-0 bg-black opacity-90" @click="modalOpen = false"></div>

            <!-- Modal Content -->
            <div class="modal-content-wrapper fixed inset-0 flex items-center justify-center p-4">
                <div class="modal-content bg-white dark:bg-gray-800 w-full max-w-md rounded-xl shadow-2xl relative" @click.outside="modalOpen = false">
                    <form wire:submit.prevent="createChat" class="p-6 text-gray-800 dark:text-gray-200" style="color: var(--modal-text-color, inherit);">
                        <div class="flex justify-between pb-3">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                Create New Chat
                            </h2>
                            <button type="button" @click="modalOpen = false" class="text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-100">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <label for="newChatName" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Chat Name</label>
                            <input
                                id="newChatName"
                                class="block mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                type="text"
                                wire:model="newChatName"
                                required
                                autofocus
                            />
                            @error('newChatName') 
                                <p class="text-red-500 dark:text-red-400 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button 
                                type="button" 
                                class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-md"
                                @click="modalOpen = false"
                            >
                                Cancel
                            </button>
                            
                            <button 
                                type="submit" 
                                class="ml-3 px-4 py-2 bg-indigo-600 dark:bg-indigo-700 text-white hover:bg-indigo-700 dark:hover:bg-indigo-800 rounded-md"
                            >
                                Create
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Debug logging
            console.log('Chat component initialized');
            
            Livewire.on('chatUpdated', () => {
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
            
            // Check if dark mode is active
            const isDarkMode = document.documentElement.classList.contains('dark') || 
                              (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            console.log('Dark mode detection:', {
                'documentHasDarkClass': document.documentElement.classList.contains('dark'),
                'prefersColorScheme': window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches,
                'isDarkMode': isDarkMode
            });
            
            // Set CSS variables for modal text color
            if (isDarkMode) {
                document.documentElement.style.setProperty('--modal-text-color', 'rgb(229, 231, 235)');
            } else {
                document.documentElement.style.setProperty('--modal-text-color', 'rgb(31, 41, 55)');
            }
            
            // Apply dark mode class to document for teleported elements
            if (isDarkMode && !document.documentElement.classList.contains('dark')) {
                console.log('Adding dark class to document');
                document.documentElement.classList.add('dark');
            }
            
            // Listen for the custom modal-opened event
            document.addEventListener('modal-opened', () => {
                console.log('Modal opened event detected');
                
                // Set modal text color using a more direct approach
                setTimeout(() => {
                    const modalForms = document.querySelectorAll('.fixed.inset-0.z-50 form');
                    console.log('Modal forms found:', modalForms.length);
                    
                    modalForms.forEach(form => {
                        console.log('Applying direct styling to modal form');
                        if (isDarkMode) {
                            form.style.color = 'rgb(229, 231, 235)';
                        } else {
                            form.style.color = 'rgb(31, 41, 55)';
                        }
                    });
                    
                    // Also try to find and style any text elements in the modal
                    const modalTexts = document.querySelectorAll('.fixed.inset-0.z-50 .text-gray-800');
                    console.log('Modal text elements found:', modalTexts.length);
                    
                    modalTexts.forEach(el => {
                        if (isDarkMode) {
                            el.style.color = 'rgb(229, 231, 235)';
                        }
                    });
                }, 50);
            });
            
            // Direct approach to style the modal when it opens
            document.addEventListener('click', (e) => {
                // Check if the click is on a button that opens the modal
                if (e.target.closest('[x-on\\:click*="modalOpen = true"]')) {
                    console.log('Modal button clicked, will style modal');
                    
                    // Use setTimeout to let the modal render
                    setTimeout(() => {
                        const modalElements = document.querySelectorAll('.fixed.inset-0.z-50 form');
                        console.log('Found modal elements:', modalElements.length);
                        
                        modalElements.forEach(el => {
                            console.log('Styling modal element');
                            if (isDarkMode) {
                                // Force text color for dark mode
                                el.style.color = 'rgb(229, 231, 235)';
                            } else {
                                el.style.color = 'rgb(31, 41, 55)';
                            }
                        });
                    }, 100);
                }
            });
            
            // Ensure teleported elements like modals inherit dark mode
            document.addEventListener('teleport:mounted', (e) => {
                console.log('Teleport mounted event detected');
                
                setTimeout(() => {
                    const teleportedForms = document.querySelectorAll('[x-teleport] form, .fixed.inset-0.z-50 form');
                    console.log('Teleported forms found:', teleportedForms.length);
                    
                    teleportedForms.forEach(form => {
                        console.log('Styling teleported form');
                        if (isDarkMode) {
                            form.style.color = 'rgb(229, 231, 235)';
                        } else {
                            form.style.color = 'rgb(31, 41, 55)';
                        }
                    });
                }, 100);
            });
        });
    </script>
    
    <style>
        /* Force dark text for modal in dark mode */
        .force-dark-text {
            color: rgb(229, 231, 235) !important;
        }
        
        /* Ensure modal has proper stacking */
        .modal-container {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 9999;
            overflow: hidden;
        }
        
        /* Modal backdrop */
        .modal-backdrop {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-color: #000;
            opacity: 0.9;
            z-index: 9998;
        }
        
        /* Modal content wrapper */
        .modal-content-wrapper {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        /* Modal content */
        .modal-content {
            position: relative;
            width: 100%;
            max-width: 28rem;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            z-index: 10000;
            overflow: hidden;
        }
        
        /* Dark mode overrides */
        .dark .modal-content {
            background-color: #1f2937;
        }
    </style>
</div>
