<button x-ref="button" @click="menuOpen = !menuOpen" @auxclick.outside="menuOpen = false" @keydown.escape="menuOpen = false"
    class="flex items-center hover:text-light-base-950 dark:hover:text-base-50">
    {{ $slot }}
</button>
