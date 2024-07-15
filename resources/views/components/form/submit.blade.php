<button
    type="submit"
    class="relative flex w-full justify-center rounded-md bg-[#2BA84A] px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-[#248232] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#248232] disabled:cursor-not-allowed disabled:opacity-75"
>
    {{ $label }}

    @if (isset($target))
        <span wire:loading.flex wire:target="send" class="absolute top-0 bottom-0 right-0 flex items-center pr-4">
            <svg class="w-5 h-5 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
    @endif
</button>
