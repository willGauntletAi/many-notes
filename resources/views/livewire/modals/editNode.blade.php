<x-modal wire:model="show">
    <x-modal.panel title="{{ $form->is_file ? __('Rename file') : __('Rename folder') }}">
        <x-form wire:submit="edit" class="flex flex-col gap-6">
            <x-form.input name="form.name" placeholder="{{ __('Name') }}" type="text" required autofocus />

            <div class="flex justify-end">
                <x-form.submit label="{{ __('Edit') }}" target="edit" />
            </div>
        </x-form>
    </x-modal.panel>
</x-modal>
