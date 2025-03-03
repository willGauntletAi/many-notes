<x-modal.index wire:model="isOpen">
    <x-modal.panel class="max-w-md">
        <div class="px-4 py-3 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-light-base-900 dark:text-base-100">
                {{ __('Record Audio') }}
            </h3>
            <p class="mt-1 text-sm text-light-base-500 dark:text-base-400">
                {{ __('Record audio to transcribe and insert into your note.') }}
            </p>
        </div>

        <div class="px-4 py-5 space-y-6 sm:px-6">
            @if (!$transcription)
                <!-- Record UI -->
                <div x-data="audioRecorder($wire)" x-init="init()">
                    <div class="mb-6 text-center">
                        <p class="mb-3 text-sm text-light-base-700 dark:text-base-300">
                            {{ __('Choose your recording mode:') }}
                        </p>
                        <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                            <!-- Microphone only button -->
                            <div class="flex flex-col items-center">
                                <button 
                                    @click="captureSystemAudio = false; startRecording()" 
                                    class="flex flex-col items-center justify-center w-28 h-28 p-4 rounded-lg bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 hover:bg-primary-200 dark:hover:bg-primary-800 focus:outline-none"
                                    x-show="!isRecording && !audioURL"
                                >
                                    <x-icons.microphone class="w-12 h-12 mb-2" />
                                    <span class="text-xs font-medium">{{ __('Microphone Only') }}</span>
                                </button>
                            </div>

                            <!-- Microphone + System audio button -->
                            <div class="flex flex-col items-center">
                                <button 
                                    @click="captureSystemAudio = true; startRecording()" 
                                    class="flex flex-col items-center justify-center w-28 h-28 p-4 rounded-lg bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-800 focus:outline-none"
                                    x-show="!isRecording && !audioURL"
                                >
                                    <div class="relative">
                                        <x-icons.microphone class="w-10 h-10" />
                                        <x-icons.system-audio class="w-6 h-6 absolute -bottom-1 -right-1" />
                                    </div>
                                    <span class="text-xs font-medium mt-2">{{ __('Mic + System Audio') }}</span>
                                </button>
                                <p class="mt-2 text-xs text-light-base-500 dark:text-base-400 max-w-xs text-center" x-show="!isRecording && !audioURL">
                                    {{ __('You\'ll need to select a tab or window to share when prompted.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Recording in progress or playback UI -->
                    <div class="flex flex-col items-center justify-center" x-show="isRecording || audioURL">
                        <div class="relative w-32 h-32 mb-4">
                            <template x-if="isRecording">
                                <button @click="stopRecording" class="flex items-center justify-center w-full h-full rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 focus:outline-none animate-pulse">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                                    </svg>
                                </button>
                            </template>
                            <template x-if="!isRecording && audioURL">
                                <div class="w-full h-full">
                                    <audio x-ref="audioPlayer" :src="audioURL" class="w-full h-12 mt-4" controls></audio>
                                    <div class="flex justify-center mt-4 space-x-2">
                                        <button @click="resetRecording" class="px-3 py-1.5 text-sm text-light-base-700 dark:text-base-300 hover:bg-light-base-100 dark:hover:bg-base-800 rounded border border-light-base-300 dark:border-base-700">
                                            {{ __('Record Again') }}
                                        </button>
                                        <button @click="$wire.insertTranscription()" class="px-3 py-1.5 text-sm text-white bg-primary-600 hover:bg-primary-500 rounded">
                                            {{ __('Use This Transcription') }}
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div x-show="isRecording" class="flex flex-col items-center mt-2">
                            <div class="text-sm font-semibold text-red-600 dark:text-red-400">{{ __('Recording...') }}</div>
                            <div class="text-xs text-light-base-500 dark:text-base-400" x-text="recordingTime"></div>
                            <div class="mt-1 text-xs" x-show="captureSystemAudio">
                                <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300">{{ __('Mic + System Audio') }}</span>
                            </div>
                        </div>

                        <!-- Real-time transcription display -->
                        <div class="mt-4 w-full" x-show="isRecording || currentTranscription">
                            <h4 class="text-sm font-medium mb-2">{{ __('Real-time Transcription') }}</h4>
                            <div class="p-3 rounded-lg bg-light-base-100 dark:bg-base-800">
                                <template x-if="currentTranscription">
                                    <p class="text-sm whitespace-pre-wrap text-light-base-700 dark:text-base-300" x-text="currentTranscription"></p>
                                </template>
                                <template x-if="isRecording && !currentTranscription">
                                    <div class="flex items-center justify-center py-2">
                                        <svg class="animate-spin h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="ml-2 text-sm text-light-base-500 dark:text-base-400">{{ __('Initializing transcription...') }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($errorMessage)
                    <div class="p-3 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/30 dark:text-red-400">
                        {{ $errorMessage }}
                    </div>
                @endif
            @else
                <div class="p-3 rounded-lg bg-light-base-100 dark:bg-base-800">
                    <h4 class="mb-2 font-medium">{{ __('Transcription Result') }}</h4>
                    <p class="text-sm whitespace-pre-wrap text-light-base-700 dark:text-base-300">{{ $transcription }}</p>
                </div>
            @endif
        </div>

        <div class="px-4 py-3 space-x-2 text-right bg-light-base-50 dark:bg-base-900 sm:px-6">
            <x-form.button wire:click="closeModal" type="button">
                {{ __('Cancel') }}
            </x-form.button>

            @if ($transcription)
                <x-form.button 
                    wire:click="insertTranscription" 
                    type="button"
                    class="bg-primary-600 hover:bg-primary-500 text-white"
                >
                    {{ __('Insert Transcription') }}
                </x-form.button>
            @endif
        </div>
    </x-modal.panel>
</x-modal.index>