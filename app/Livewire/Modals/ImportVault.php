<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Contracts\View\View;
use App\Actions\ProcessImportedVault;
use Illuminate\Contracts\View\Factory;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportVault extends Modal
{
    use WithFileUploads;

    public bool $show = false;

    #[Validate('required|file|mimes:zip')]
    public ?TemporaryUploadedFile $file = null;

    #[On('open-modal')]
    public function open(): void
    {
        $this->openModal();
    }

    public function updatedFile(): void
    {
        $this->validate();

        if (is_null($this->file)) {
            return;
        }

        $fileName = $this->file->getClientOriginalName();
        $filePath = $this->file->getRealPath();
        new ProcessImportedVault()->handle($fileName, $filePath);
        $this->dispatch('vault-imported');
        $this->closeModal();
        $this->dispatch('toast', message: __('Vault imported'), type: 'success');
    }

    public function render(): Factory|View
    {
        return view('livewire.modals.importVault');
    }
}
