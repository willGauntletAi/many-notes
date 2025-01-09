<button x-ref="button" @click="menuOpen = !menuOpen" @auxclick.outside="menuOpen = false" @keydown.escape="menuOpen = false"
    class="flex items-center hover:text-light-base-950 hover:dark:text-base-50">
    {{ $slot }}
</button>
