<label class="flex flex-col gap-2 text-base font-medium">
    <span>
        {{ $label }}
        @if ($attributes->has('required'))
            <span class="text-red-500 opacity-75" aria-hidden="true">*</span>
        @endif
    </span>

    <input
        wire:model="{{ $name }}"
        type="{{ $attributes->merge(['type' => 'text'])->get('type') }}"
        @class([
            'bg-[#FCFFFC] text-gray-900 rounded-lg focus:ring-0 border border-gray-300 focus:border-gray-500 block w-full p-2.5 dark:bg-[#040F0F] dark:border-gray-700 dark:focus:border-gray-500 dark:placeholder-gray-400 dark:text-white',
            'border border-red-500 focus:border-red-700 dark:border-red-500 dark:focus:border-red-600' => $errors->has($name),
        ])
        @error($name)
            aria-invalid="true"
            aria-description="{{ $message }}"
        @enderror
        @if ($attributes->has('autofocus'))
            autofocus
        @endif
    >

    @error($name)
        <p class="text-sm text-red-500" aria-live="assertive">{{ $message }}</p>
    @enderror
</label>
