<?php

namespace App\Livewire\Vault;

use App\Models\Vault;
use Livewire\Component;
use App\Livewire\Forms\VaultForm;

class Index extends Component
{
    public VaultForm $form;

    public $showCreateModal = false;

    public function create()
    {
        $this->validate();
        $this->form->create();
        $this->reset('showCreateModal');
    }

    public function export(Vault $vault)
    {
    }

    public function delete(Vault $vault)
    {
    }

    public function render()
    {
        return view('livewire.vault.index', [
            'vaults' => auth()->user()->vaults()->orderBy('updated_at', 'DESC')->get(),
        ]);
    }
}
