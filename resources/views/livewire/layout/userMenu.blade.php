<div>
    <x-menu>
        <x-menu.button>
            <x-icons.user class="w-5 h-5" />
        </x-menu.button>

        <x-menu.items>
            <div class="px-3">
                {{ auth()->user()->name }}
            </div>

            <x-menu.itemDivider></x-menu.itemDivider>

            <x-modal>
                <x-menu.close>
                    <x-menu.item @click="modalOpen = true">
                        <x-icons.user class="w-4 h-4" />
                        {{ __('Profile') }}
                    </x-menu.item>
                </x-menu.close>

                <x-modal.panel title="Edit profile">
                    <x-form wire:submit="editProfile" class="flex flex-col gap-6">
                        <x-form.input name="profileForm.name" placeholder="{{ __('Name') }}" type="text" required
                            autofocus />

                        <x-form.input name="profileForm.email" placeholder="{{ __('Email') }}" type="email"
                            required />

                        <div class="flex justify-end">
                            <x-form.submit label="{{ __('Edit') }}" target="edit" />
                        </div>
                    </x-form>
                </x-modal.panel>
            </x-modal>

            <x-modal>
                <x-menu.close>
                    <x-menu.item @click="modalOpen = true">
                        <x-icons.lockClosed class="w-4 h-4" />
                        {{ __('Password') }}
                    </x-menu.item>
                </x-menu.close>

                <x-modal.panel title="Edit password">
                    <x-form wire:submit="editPassword" class="flex flex-col gap-6">
                        <x-form.input name="passwordForm.current_password" placeholder="{{ __('Current password') }}"
                            type="password" required autofocus />

                        <x-form.input name="passwordForm.password" placeholder="{{ __('New password') }}"
                            type="password" required />

                        <x-form.input name="passwordForm.password_confirmation"
                            placeholder="{{ __('Confirm password') }}" type="password" required />

                        <div class="flex justify-end">
                            <x-form.submit label="{{ __('Edit') }}" target="edit" />
                        </div>
                    </x-form>
                </x-modal.panel>
            </x-modal>

            <x-menu.close>
                <x-menu.itemLink href="/vaults" wire:navigate>
                    <x-icons.circleStack class="w-4 h-4" />
                    {{ __('Vaults') }}
                </x-menu.itemLink>
            </x-menu.close>

            <x-menu.itemDivider></x-menu.itemDivider>

            <x-modal>
                <x-menu.close>
                    <x-menu.item @click="modalOpen = true">
                        <x-icons.questionMarkCircle class="w-4 h-4" />
                        {{ __('Help') }}
                    </x-menu.item>
                </x-menu.close>

                <x-modal.panel title="Help">
                    <div x-data="{
                        selected: 1,
                        isSelected(selection) { return this.selected == selection },
                        toggle(selection) { this.selected = this.selected != selection ? selection : 0 },
                    }">
                        <ul>
                            <li class="relative p-3 mb-3 last:mb-0 bg-light-base-200 dark:bg-base-950" x-data="{ index: 1 }">
                                <button type="button" class="w-full text-left" @click="toggle(index)">
                                    <div class="flex items-center justify-between">
                                        <span>{{ __( 'Templates' ) }}</span>
                                        <x-icons.chevronRight x-show="!isSelected(index)" class="w-4 h-4" />
                                        <x-icons.chevronDown x-show="isSelected(index)" class="w-4 h-4" x-cloak />
                                    </div>
                                </button>
                                <div class="relative overflow-hidden transition-all duration-700" x-show="isSelected(index)" x-collapse>
                                    <div class="flex flex-col gap-3 pt-3">
                                        <p>{{ __( 'To effectively use templates in the app, start by selecting a folder where you want to store all your template notes in the tree view component. Right-click on the desired folder and select "Template Folder" to designate it for templates.') }}</p>
                                        <p>{!! __('Notes created within this designated folder will automatically be treated as templates. You can incorporate dynamic variables such as @{{date}}, @{{time}} and @{{content}} in these notes, which will be replaced upon template insertion.') !!}</p>
                                        <p>{{ __( 'When you\'re ready to use a template, open any note and choose the "Insert Template" option from the menu, then select your desired template from the list that appears. This allows for efficient note-taking with consistent formatting and content.') }}</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </x-modal.panel>
            </x-modal>

            <x-modal>
                <x-menu.close>
                    <x-menu.item @click="modalOpen = true">
                        <x-icons.informationCircle class="w-4 h-4" />
                        {{ __('About') }}
                    </x-menu.item>
                </x-menu.close>

                <x-modal.panel title="About">
                    <div class="flex flex-col gap-4">
                        <p>
                            {{ __('Follow the development, report any issues, and check for new versions on GitHub.') }}
                        </p>

                        <div>
                            <h2>{{ __('Version') }}</h2>
                            <p>{{ $appVersion }}</p>
                        </div>

                        <div>
                            <h2>{{ __('GitHub') }}</h2>
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
        </x-menu.items>
    </x-menu>
</div>
