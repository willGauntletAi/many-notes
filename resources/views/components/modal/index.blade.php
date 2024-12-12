<div x-data="{ modalOpen: false }" x-modelable="modalOpen" {{ $attributes }} tabindex="-1"
    @close-modal.window="modalOpen = false">
    {{ $slot }}
</div>
