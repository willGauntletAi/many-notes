<main class="flex flex-grow bg-light-base-100 dark:bg-base-800 text-light-base-950 dark:text-base-50">
    <div class="relative flex flex-grow w-full mx-auto bg-light-base-50 dark:bg-base-900">
        {{ $slot }}

        <x-toast />
    </div>
</main>
