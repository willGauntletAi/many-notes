<x-layouts.app>
    <div class="flex flex-col h-dvh">
        <x-layouts.appHeader>
            <div class="flex items-center gap-4">
                <a href="{{ route('chat.index', $vault) }}" class="hover:text-light-base-950 dark:hover:text-base-50">
                    <x-icons.chevronLeft class="w-5 h-5" />
                </a>
                <h2 class="text-lg">{{ $chat->name }} - {{ $vault->name }}</h2>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('vaults.show', $vault) }}" class="hover:text-light-base-950 dark:hover:text-base-50">
                    Back to Vault
                </a>
                <livewire:layout.user-menu />
            </div>
        </x-layouts.appHeader>

        <x-layouts.appMain>
            <div class="relative flex w-full">
                <div class="absolute inset-0 overflow-y-auto">
                    <div class="p-6 text-gray-900 h-[calc(100vh-12rem)]">
                        <livewire:vault.chat :vault="$vault" :chatId="$chat->id" />
                    </div>
                </div>
            </div>
        </x-layouts.appMain>
    </div>
</x-layouts.app>