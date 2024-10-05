<?php

namespace App\Livewire\Modals;

use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\Actions\ProcessUploadedVaults;

class ImportVault extends Modal
{
    use WithFileUploads;

    public bool $show = false;

    #[Validate('required|file|mimes:zip')]
    public $file;

    #[On('open-modal')]
    public function open(): void
    {
        $this->openModal();
    }

    public function updatedFile(): void
    {
        $this->validate();
        $fileName = $this->file->getClientOriginalName();
        $filePath = $this->file->getRealPath();
        (new ProcessUploadedVaults())->handle($fileName, $filePath);
        $this->dispatch('vault-imported');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.modals.importVault');
    }
}
