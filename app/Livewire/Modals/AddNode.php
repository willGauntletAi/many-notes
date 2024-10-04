<?php

namespace App\Livewire\Modals;

use App\Models\Vault;
use App\Models\VaultNode;
use Livewire\Attributes\On;
use App\Livewire\Forms\VaultNodeForm;

class AddNode extends Modal
{
    public VaultNodeForm $form;

    public bool $show = false;

    public function mount(Vault $vault): void
    {
        $this->authorize('update', $vault);
        $this->form->setVault($vault);
    }

    #[On('open-modal')]
    public function open(?VaultNode $parent = null, bool $isFile = true): void
    {
        if (!is_null($parent->vault)) {
            $this->authorize('update', $parent->vault);
        }

        $this->form->parent_id = $parent->id;
        $this->form->is_file = $isFile;
        $this->openModal();
    }

    public function add(): void
    {
        $this->form->create();
        $this->closeModal();
        $this->dispatch('node-updated');
    }

    public function render()
    {
        return view('livewire.modals.addNode');
    }
}
