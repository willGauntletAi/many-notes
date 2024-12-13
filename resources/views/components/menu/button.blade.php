<button x-ref="button" @click="menuOpen = !menuOpen" @auxclick.outside="menuOpen = false" @keydown.escape="menuOpen = false"
    class="flex items-center">
    {{ $slot }}
</button>
