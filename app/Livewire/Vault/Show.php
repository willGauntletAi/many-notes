<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use Livewire\Component;
use App\Models\VaultNode;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use App\Actions\ResolveTwoPaths;
use App\Livewire\Forms\VaultForm;
use App\Services\VaultFiles\Note;
use Illuminate\Support\Facades\DB;
use App\Actions\GetUrlFromVaultNode;
use App\Actions\GetPathFromVaultNode;
use App\Actions\GetVaultNodeFromPath;
use App\Livewire\Forms\VaultNodeForm;
use Illuminate\Database\Eloquent\Collection;

class Show extends Component
{
    public Vault $vault;

    public VaultForm $vaultForm;

    public VaultNodeForm $nodeForm;

    public Collection $templates;

    #[Url(as: 'file')]
    public ?int $selectedFile = null;

    public ?string $selectedFilePath = null;

    public bool $isEditMode = true;

    private array $deletedNodes = [];

    public function mount(Vault $vault): void
    {
        $this->authorize('view', $vault);
        $this->vault = $vault;
        $this->vaultForm->setVault($this->vault);
        $this->nodeForm->setVault($this->vault);
        $this->getTemplates();

        if ($this->selectedFile) {
            $selectedFile = $vault->nodes()->where('id', $this->selectedFile)->first();

            if (!$selectedFile) {
                $this->selectedFile = null;
                return;
            }

            $this->openFile($selectedFile);
        }
    }

    public function openFile(VaultNode $node): void
    {
        $this->authorize('view', $node->vault);

        if (!$node->vault->is($this->vault) || !$node->is_file) {
            return;
        }

        $this->selectedFile = $node->id;
        $this->selectedFilePath = new GetUrlFromVaultNode()->handle($node);
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
        $resolvedPath = new ResolveTwoPaths()->handle($currentPath, $path);
        $node = new GetVaultNodeFromPath()->handle($this->vault->id, $resolvedPath);

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
        $this->selectedFilePath = new GetPathFromVaultNode()->handle($node);
        $this->nodeForm->setNode($node);
    }

    public function closeFile(): void
    {
        $this->reset(['selectedFile', 'selectedFilePath']);
        $this->nodeForm->reset('node');
    }

    public function editVault(): void
    {
        $this->authorize('update', $this->vault);
        $this->vaultForm->update();
        $this->vault->refresh();
        $this->dispatch('close-modal');
        $this->dispatch('toast', message: __('Vault edited'), type: 'success');
    }

    public function updated($name): void
    {
        if (!Str::of($name)->startsWith('nodeForm')) {
            return;
        }

        $this->nodeForm->update();

        if ($this->nodeForm->node->wasChanged(['parent_id', 'name'])) {
            $this->dispatch('node-updated');

            if ($this->nodeForm->node->parent_id == $this->vault->templates_node_id) {
                $this->getTemplates();
            }
        }
    }

    public function setTemplateFolder(VaultNode $node): void
    {
        $this->authorize('update', $node->vault);

        if ($this->vault->id !== $node->vault->id || $node->is_file) {
            $this->dispatch('toast', message: __('Something went wrong'), type: 'error');
            return;
        }

        $this->vault->update(['templates_node_id' => $node->id]);
        $this->getTemplates();
        $this->dispatch('toast', message: __('Template folder updated'), type: 'success');
    }

    public function insertTemplate(VaultNode $node): void
    {
        $this->authorize('update', $this->vault);
        $sameVault = $this->vault->id === $node->vault->id;
        $isNote = $node->is_file && in_array($node->extension, Note::extensions());
        $isTemplate = $node->parent_id == $this->vault->templates_node_id;
        if (!$sameVault || !$isNote || !$isTemplate || !$this->selectedFile || !$this->isEditMode) {
            $this->dispatch('toast', message: __('Something went wrong'), type: 'error');
            return;
        }

        $selectedNode = $this->nodeForm->node;
        $now = now();
        $content = $node->content;

        $content = str_replace(
            ['{{date}}', '{{time}}'],
            [$now->format('Y-m-d'), $now->format('H:i')],
            $content,
        );

        $content = str_contains($content, '{{content}}')
            ? str_replace('{{content}}', $selectedNode->content, $content)
            : $content . PHP_EOL . $selectedNode->content;
        $selectedNode->update(['content' => $content]);
        $this->nodeForm->setNode($selectedNode);
        $this->dispatch('toast', message: __('Template inserted'), type: 'success');
    }

    #[On('templates-refresh')]
    public function getTemplates(): void
    {
        if (!$this->vault->templatesNode) {
            return;
        }

        $this->templates = $this->vault
            ->templatesNode
            ->childs()
            ->where('is_file', true)
            ->where('extension', 'LIKE', 'md')
            ->orderBy('name')
            ->get();
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
            $templateDeleted = !is_null(
                array_find($this->deletedNodes, function ($node) {
                    return $node->parent_id == $this->vault->templates_node_id;
                })
            );
            if ($templateDeleted) {
                $this->getTemplates();
            }
            $this->deletedNodes = [];
            $message = $node->is_file ? __('File deleted') : __('Folder deleted');
            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Throwable $e) {
            DB::rollBack();
        }
    }

    private function deleteFile(VaultNode $node): void
    {
        if ($this->selectedFile == $node->id) {
            $this->closeFile();
        }

        $this->deletedNodes[] = $node;
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

        $this->deletedNodes[] = $node;
        $node->delete();
    }

    public function render()
    {
        return view('livewire.vault.show');
    }
}
