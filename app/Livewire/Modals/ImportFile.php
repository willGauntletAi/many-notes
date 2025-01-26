<?php

declare(strict_types=1);

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use App\Services\VaultFile;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Contracts\View\View;
use App\Actions\ProcessImportedFile;
use Illuminate\Contracts\View\Factory;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportFile extends Modal
{
    use WithFileUploads;

    public Vault $vault;

    public VaultNode $parent;

    public bool $show;

    public string $fileMimes;

    #[Validate]
    public ?TemporaryUploadedFile $file = null;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'mimes:' . Arr::join(VaultFile::extensions(), ','),
            ],
        ];
    }

    public function mount(Vault $vault): void
    {
        $this->authorize('view', $vault);
        $this->vault = $vault;
        $this->show = false;
        $this->fileMimes = Arr::join(VaultFile::extensions(true), ',');
    }

    #[On('open-modal')]
    public function open(VaultNode $parent): void
    {
        $this->parent = $parent;

        if ($this->parent->exists) {
            $this->authorize('update', $this->parent->vault);

            // Make sure submitted parent node is a folder
            if ($this->parent->is_file) {
                abort(400);
            }
        }

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
        new ProcessImportedFile()->handle($this->vault, $this->parent, $fileName, $filePath);
        $this->dispatch('node-updated');
        $this->closeModal();
        $this->dispatch('toast', message: __('File imported'), type: 'success');
    }

    public function render(): Factory|View
    {
        return view('livewire.modals.importFile');
    }
}
