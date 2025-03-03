<div {{ $attributes }} class="flex justify-between">
    <ul class="flex gap-1">
        <li>
            <x-markdownEditor.button x-bind:class="!isEditMode ? 'bg-light-base-400 dark:bg-base-700' : ''" @click="toggleEditMode">
                {{ __('Preview') }}
            </x-markdownEditor.button>
        </li>
    </ul>
    <div class="relative flex">
        <ul class="flex gap-1">
            <x-markdownEditor.itemDropdown>
                <x-markdownEditor.button>
                    Style
                </x-markdownEditor.button>
                <x-markdownEditor.items x-show="isToolbarOpen" x-anchor.bottom="$refs.button">
                    <x-markdownEditor.subButton @click="unorderedList">Unordered list</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="orderedList">Ordered list</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="taskList">Task list</x-markdownEditor.subButton>
                    <x-markdownEditor.itemDivider />
                    <x-markdownEditor.subButton @click="heading(1)">Heading 1</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="heading(2)">Heading 2</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="heading(3)">Heading 3</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="heading(4)">Heading 4</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="heading(5)">Heading 5</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="heading(6)">Heading 6</x-markdownEditor.subButton>
                    <x-markdownEditor.itemDivider />
                    <x-markdownEditor.subButton @click="blockquote">Blockquote</x-markdownEditor.subButton>
                </x-markdownEditor.items>
            </x-markdownEditor.itemDropdown>
            <x-markdownEditor.itemDropdown>
                <x-markdownEditor.button>
                    Format
                </x-markdownEditor.button>
                <x-markdownEditor.items x-show="isToolbarOpen" x-anchor.bottom="$refs.button">
                    <x-markdownEditor.subButton @click="bold">Bold</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="italic">Italic</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="strikethrough">Strikethrough</x-markdownEditor.subButton>
                </x-markdownEditor.items>
            </x-markdownEditor.itemDropdown>
            <x-markdownEditor.itemDropdown>
                <x-markdownEditor.button>
                    Insert
                </x-markdownEditor.button>
                <x-markdownEditor.items x-show="isToolbarOpen" x-anchor.bottom="$refs.button">
                    <x-markdownEditor.subButton
                        @click="$wire.dispatchTo('modals.markdown-editor-search', 'open-modal')">Link</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="link()">External link</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton
                        @click="$wire.dispatchTo('modals.markdown-editor-search', 'open-modal', { type: 'image' })">Image</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="image()">External image</x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="table">Table</x-markdownEditor.subButton>
                    <x-markdownEditor.itemDivider />
                    <x-markdownEditor.subButton @click="startMicRecording">
                        <div class="flex items-center gap-2">
                            <x-icons.microphone class="w-4 h-4" />
                            <span>Record Microphone</span>
                        </div>
                    </x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="startSystemRecording">
                        <div class="flex items-center gap-2">
                            <x-icons.desktop class="w-4 h-4" />
                            <span>Record System Audio</span>
                        </div>
                    </x-markdownEditor.subButton>
                    <x-markdownEditor.subButton @click="startCombinedRecording">
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <x-icons.microphone class="w-4 h-4" />
                                <x-icons.desktop class="w-3 h-3 absolute -bottom-1 -right-1" />
                            </div>
                            <span>Record Mic + System</span>
                        </div>
                    </x-markdownEditor.subButton>
                </x-markdownEditor.items>
            </x-markdownEditor.itemDropdown>
            
            <!-- Audio Recording Status -->
            <div x-show="isRecording" class="flex items-center gap-2 ml-2 text-red-500 animate-pulse">
                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                <span x-text="recordingSource + ' Recording'"></span>
                <x-markdownEditor.button @click="stopRecording" class="bg-red-500 text-white">
                    Stop
                </x-markdownEditor.button>
            </div>
        </ul>
        <div x-show="!isEditMode" class="absolute inset-0 opacity-25 bg-light-base-200 dark:bg-base-950"></div>
    </div>
</div>
