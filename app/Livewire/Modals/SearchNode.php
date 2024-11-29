<?php

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use App\Livewire\Forms\VaultNodeForm;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;

class SearchNode extends Modal
{
    public VaultNodeForm $form;

    public Vault $vault;

    public Collection $nodes;

    public bool $show = false;

    public string $search = '';

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
        $this->nodes = VaultNode::query()
            ->select('id', 'name', 'extension')
            ->where('vault_id', $this->vault->id)
            ->where('is_file', true)
            ->when(strlen($this->search), function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $this->nodes->transform(function (VaultNode $item) {
            $item->full_path = $item->ancestorsAndSelf()->get()->last()->full_path;
            $item->dir_name = preg_replace('/' . $item->name . '$/', '', $item->full_path);
            if (strlen($item->dir_name) == 1) {
                $item->dir_name = '';
            }

            return $item;
        });
    }

    public function render()
    {
        $this->search();

        return view('livewire.modals.searchNode');
    }
}
