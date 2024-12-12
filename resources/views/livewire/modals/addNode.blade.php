<x-modal wire:model="show">
    <x-modal.panel title="{{ $form->is_file ? __('New note') : __('New folder') }}">
        <x-form wire:submit="add" class="flex flex-col gap-6">
            <x-form.input name="form.name" placeholder="{{ __('Name') }}" type="text" required autofocus />

            <div class="flex justify-end">
                <x-form.submit label="{{ __('Add') }}" target="add" />
            </div>
        </x-form>
    </x-modal.panel>
</x-modal>
