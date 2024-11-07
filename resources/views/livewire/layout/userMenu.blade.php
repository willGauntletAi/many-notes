<div>
    <x-menu>
        <x-menu.button>
            <x-icons.user class="w-5 h-5" />
        </x-menu.button>

        <x-menu.items>
            <div class="px-3">
                {{ auth()->user()->name }}
            </div>

            <x-menu.close>
                <x-menu.itemDivider></x-menu.itemDivider>

                <x-modal>
                    <x-menu.item @click="modalOpen = true">
                        <x-icons.informationCircle class="w-4 h-4" />
                        {{ __('About') }}
                    </x-menu.item>

                    <x-modal.panel title="About">
                        <div class="flex flex-col gap-4">
                            <p>
                                {{ __('Many Notes is an open-source Markdown note-taking app.') }}
                                {{ __('Follow the development and check for new versions on GitHub.') }}
                            </p>

                            <div>
                                <h2>Version</h2>
                                <p>{{ $appVersion }}</p>
                            </div>

                            <div>
                                <h2>Github</h2>
                                <p>
                                    <a href="{{ $githubUrl }}" target="_blank"
                                        class="text-primary-400 dark:text-primary-500 hover:text-primary-300 dark:hover:text-primary-600">{{ $githubUrl }}</a>
                                </p>
                            </div>
                        </div>
                    </x-modal.panel>
                </x-modal>

                <x-menu.item wire:click="logout">
                    <x-icons.arrowRightStartOnRectangle class="w-4 h-4" />
                    {{ __('Logout') }}
                </x-menu.item>
            </x-menu.close>
        </x-menu.items>
    </x-menu>
</div>
