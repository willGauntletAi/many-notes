<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use Livewire\Component;
use App\Models\VaultNode;
use Livewire\Attributes\On;

#[On('node-updated')]
class TreeView extends Component
{
    public Vault $vault;

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="fixed inset-0 z-40 opacity-50 bg-light-base-200 dark:bg-base-950">
            <div class="flex items-center justify-center h-full">
                <x-icons.spinner class="w-5 h-5 animate-spin" />
            </div>
        </div>
        HTML;
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
