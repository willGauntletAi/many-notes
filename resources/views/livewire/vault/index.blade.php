<div class="flex flex-col flex-grow">
    <x-layouts.appHeader>
        <div class="flex items-center gap-4"></div>

        <div class="flex items-center gap-4">
            <livewire:layout.user-menu />
        </div>
    </x-layouts.appHeader>

    <x-layouts.appMain>
        <div class="flex flex-col flex-grow px-4 py-10">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl">{{ __('My vaults') }}</h2>
                <div class="flex items-center gap-2">
                    <x-modal wire:model="showCreateModal">
                        <x-modal.open>
                            <x-form.button primary>
                                <x-icons.plus class="w-4 h-4" />
                                <span class="hidden text-sm font-medium md:block">{{ __('Create') }}</span>
                            </x-form.button>
                        </x-modal.open>

                        <x-modal.panel title="{{ __('Create new vault') }}">
                            <x-form wire:submit="create" class="flex flex-col gap-6">
                                <x-form.input
                                    name="form.name"
                                    label="{{ __('Name') }}"
                                    type="name"
                                    required
                                    autofocus
                                />

                                <div class="flex justify-end">
                                    <x-form.submit label="{{ __('Create') }}" target="create" />
                                </div>
                            </x-form>
                        </x-modal.panel>
                    </x-modal>
                </div>
            </div>
            <ul class="flex flex-col mt-5" wire:loading.class="opacity-50">
                @forelse ($vaults as $vault)
                    <livewire:vault.row
                        :key="$vault->id"
                        :$vault
                        @vault-export="export({{ $vault->id }})"
                        @vault-delete="delete({{ $vault->id }})"
                    />
                @empty
                    <li class="items-center pt-3 pb-4">
                        <p>{{ __('You have no vaults yet.') }}</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </x-layouts.appMain>
</div>
