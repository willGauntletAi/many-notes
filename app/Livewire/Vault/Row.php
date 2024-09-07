<?php

namespace App\Livewire\Vault;

use App\Livewire\Forms\VaultForm;
use App\Models\Vault;
use Livewire\Component;

class Row extends Component
{
    public Vault $vault;

    public VaultForm $form;

    public $showEditModal = false;

    public function mount()
    {
        $this->form->setVault($this->vault);
    }

    public function update()
    {
        $this->authorize('update', $this->vault);
        $this->validate();
        $this->form->update();
        $this->vault->refresh();
        $this->reset('showEditModal');
    }

    public function render()
    {
        return view('livewire.vault.row');
    }
}
