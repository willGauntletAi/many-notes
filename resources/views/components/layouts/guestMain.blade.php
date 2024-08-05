<main class="flex flex-col flex-grow gap-10 place-content-center bg-light-base-200 dark:bg-base-950 text-light-base-950 dark:text-base-50">
    <div class="mx-auto">
        <h1 class="text-3xl font-semibold">Many Notes</h1>
    </div>

    <div class="md:container md:mx-auto">
        <div class="flex flex-col gap-6 p-6 rounded-lg sm:mx-auto sm:w-full sm:max-w-sm sm:p-10 bg-light-base-50 dark:bg-base-900">
            {{ $slot }}
        </div>
    </div>
</main>
