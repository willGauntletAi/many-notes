<?php

namespace App\Livewire\Vault;

use App\Livewire\Forms\VaultForm;
use App\Models\Vault;
use Livewire\Component;

class Row extends Component
{
    public Vault $vault;

    public VaultForm $form;

    public function mount(): void
    {
        $this->form->setVault($this->vault);
    }

    public function update(): void
    {
        $this->authorize('update', $this->vault);
        $this->validate();
        $this->form->update();
        $this->vault->refresh();
        $this->dispatch('close-modal');
        $this->dispatch('toast', message: __('Vault edited'), type: 'success');
    }

    public function render()
    {
        return view('livewire.vault.row');
    }
}
