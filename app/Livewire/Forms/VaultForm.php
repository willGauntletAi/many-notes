<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Vault;
use Livewire\Attributes\Validate;

class VaultForm extends Form
{
    public ?Vault $vault = null;

    #[Validate('required|min:3')]
    public $name = '';

    public function setVault(Vault $vault): void
    {
        $this->vault = $vault;
        $this->name = $vault->name;
    }

    public function create()
    {
        auth()->user()->vaults()->create([
            'name' => $this->name,
        ]);

        $this->reset(['name']);
    }

    public function update()
    {
        $this->vault->update([
            'name' => $this->name,
        ]);
    }
}
