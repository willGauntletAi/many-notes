<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Vault;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;

class VaultForm extends Form
{
    public ?Vault $vault = null;

    #[Validate]
    public $name = '';

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'min:3',
                'regex:/^[\s\w.-]+$/',
            ],
        ];
    }

    public function setVault(Vault $vault): void
    {
        $this->vault = $vault;
        $this->name = $vault->name;
    }

    public function create(): void
    {
        $this->validate();

        $this->name = Str::trim($this->name);
        auth()->user()->vaults()->create([
            'name' => $this->name,
        ]);
        $this->reset(['name']);
    }

    public function update(): void
    {
        $this->validate();

        $this->name = Str::trim($this->name);
        $this->vault->update([
            'name' => $this->name,
        ]);
    }
}
