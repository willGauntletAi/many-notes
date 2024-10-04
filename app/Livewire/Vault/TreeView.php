<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use Livewire\Component;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use App\Actions\GetPathFromVaultNode;
use App\Livewire\Forms\VaultNodeForm;
use Illuminate\Support\Facades\Storage;

#[On('node-updated')]
class TreeView extends Component
{
    public Vault $vault;

    public VaultNodeForm $nodeForm;

    public $showEditModal = false;

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
        } catch (\Throwable $e) {
            DB::rollBack();
        }
    }

    private function deleteFile(VaultNode $node): void
    {
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
        $constraint = function ($query) {
            $query->whereNull('parent_id')->where('vault_id', $this->vault->id);
        };

        $nodes = VaultNode::treeOf($constraint)->orderBy('is_file')->orderBy('name')->get()->toTree();

        return view('livewire.vault.treeView', [
            'nodes' => $nodes,
        ]);
    }
}
