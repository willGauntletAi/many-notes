<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use Livewire\Component;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use App\Livewire\Forms\VaultNodeForm;

#[On('node-updated')]
class TreeView extends Component
{
    public Vault $vault;

    public VaultNodeForm $nodeForm;

    public $showEditModal = false;

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
