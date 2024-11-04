<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use Livewire\Component;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use App\Actions\ResolveTwoPaths;
use App\Livewire\Forms\VaultForm;
use Illuminate\Support\Facades\DB;
use App\Actions\GetUrlFromVaultNode;
use App\Actions\GetPathFromVaultNode;
use App\Actions\GetVaultNodeFromPath;
use App\Livewire\Forms\VaultNodeForm;
use Illuminate\Support\Facades\Storage;

class Show extends Component
{
    public Vault $vault;

    public VaultForm $form;

    public VaultNodeForm $nodeForm;

    public ?int $selectedFile = null;

    public ?string $selectedFilePath = null;

    public bool $isEditMode = true;

    public bool $showEditModal = false;

    public function mount(Vault $vault): void
    {
        $this->authorize('view', $vault);
        $this->vault = $vault;
        $this->form->setVault($this->vault);
        $this->nodeForm->setVault($this->vault);
    }

    public function openFile(VaultNode $node): void
    {
        $this->authorize('view', $node->vault);

        if ($node->vault != $this->vault || !$node->is_file) {
            return;
        }

        $this->selectedFile = $node->id;
        $this->selectedFilePath = (new GetUrlFromVaultNode())->handle($node);
        $this->nodeForm->setNode($node);

        if ($node->extension == 'md') {
            $this->dispatch('file-render-markup');
        } else {
            $this->reset('isEditMode');
        }
    }

    public function openFilePath(string $path): void
    {
        $currentPath = $this->nodeForm->node->ancestorsAndSelf()->get()->last()->full_path;
        $resolvedPath = (new ResolveTwoPaths())->handle($currentPath, $path);
        $node = (new GetVaultNodeFromPath())->handle($this->vault->id, $resolvedPath);

        if (is_null($node)) {
            abort(404);
        }

        $this->openFile($node);
    }

    #[On('file-refresh')]
    public function refreshFile(VaultNode $node): void
    {
        $this->authorize('view', $node->vault);

        if ($node->id != $this->selectedFile) {
            return;
        }

        $this->selectedFile = $node->id;
        $this->selectedFilePath = (new GetPathFromVaultNode())->handle($node);
        $this->nodeForm->setNode($node);
    }

    public function closeFile(): void
    {
        $this->reset(['selectedFile', 'selectedFilePath']);
        $this->nodeForm->reset('node');
    }

    public function update(): void
    {
        $this->authorize('update', $this->vault);
        $this->validate();
        $this->form->update();
        $this->vault->refresh();
        $this->reset('showEditModal');
    }

    public function updated(): void
    {
        $this->nodeForm->update();

        if ($this->nodeForm->node->wasChanged(['parent_id', 'name'])) {
            $this->dispatch('node-updated');
        }
    }

    public function deleteNode(VaultNode $node): void
    {
        $this->authorize('delete', $node->vault);

        DB::beginTransaction();
        try {
            if ($node->is_file) {
                $this->deleteFile($node);
            } else {
                $this->deleteFolder($node);
            }

            DB::commit();
            $this->dispatch('node-updated');
        } catch (\Throwable $e) {
            DB::rollBack();
        }
    }

    private function deleteFile(VaultNode $node): void
    {
        if ($this->selectedFile == $node->id) {
            $this->closeFile();
        }

        if ($node->extension !== 'md') {
            $relativePath = (new GetPathFromVaultNode())->handle($node);
            Storage::disk('local')->delete($relativePath);
        }

        $node->delete();
    }

    private function deleteFolder(VaultNode $node): void
    {
        foreach ($node->childs as $child) {
            if ($child->is_file) {
                $this->deleteFile($child);
            } else {
                $this->deleteFolder($child);
            }
        }

        $node->delete();
    }

    public function render()
    {
        return view('livewire.vault.show');
    }
}
