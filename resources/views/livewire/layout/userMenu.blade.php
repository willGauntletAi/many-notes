<div>
    <x-menu>
        <x-menu.button>
            <x-icons.user class="w-5 h-5" />
        </x-menu.button>

        <x-menu.items>
            <x-menu.close>
                <x-menu.item wire:click="logout">
                    <x-icons.arrowRightStartOnRectangle class="w-4 h-4" />

                    {{ __('Logout') }}
                </x-menu.item>
            </x-menu.close>
        </x-menu.items>
    </x-menu>
</div>
