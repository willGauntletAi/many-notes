<button
    x-ref="button"
    @click="menuOpen = !menuOpen"
    @keydown.escape="menuOpen = false"
    class="flex items-center"
>
    {{ $slot }}
</button>
