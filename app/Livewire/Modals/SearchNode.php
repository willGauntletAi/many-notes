<?php

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use App\Livewire\Forms\VaultNodeForm;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;

class SearchNode extends Modal
{
    public VaultNodeForm $form;

    public Vault $vault;

    public string $search = '';

    public Collection $nodes;

    public bool $show = false;

    public function mount(Vault $vault): void
    {
        $this->authorize('view', $vault);
        $this->vault = $vault;
        $this->form->setVault($vault);
    }

    #[On('open-modal')]
    public function open(): void
    {
        $this->openModal();
    }

    public function search(): void
    {
        if ($this->search === '') {
            $this->nodes = VaultNode::query()
                ->where('vault_id', $this->vault->id)
                ->where('is_file', true)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();
        } else {
            $this->nodes = VaultNode::query()
                ->where('vault_id', $this->vault->id)
                ->where('is_file', true)
                ->where('name', 'like', '%' . $this->search . '%')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();
        }

        $this->nodes->transform(function (VaultNode $item) {
            $item->full_path = $item->ancestorsAndSelf()->get()->last()->full_path;

            return $item;
        });
    }

    public function render()
    {
        $this->search();

        return view('livewire.modals.searchNode');
    }
}
