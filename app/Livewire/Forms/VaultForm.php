<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class VaultForm extends Form
{
    public ?Vault $vault = null;

    #[Validate]
    public string $name = '';

    /**
     * @return array<string, list<string|Unique>>
     */
    public function rules(): array
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        return [
            'name' => [
                'required',
                'min:3',
                'regex:/^[\w]+[\s\w.-]+$/u',
                Rule::unique(Vault::class)
                    ->where('created_by', $currentUser->id)
                    ->ignore($this->vault),
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
        /** @var User $currentUser */
        $currentUser = auth()->user();
        $this->name = Str::trim($this->name);
        $currentUser->vaults()->create([
            'name' => $this->name,
        ]);
        $this->reset(['name']);
    }

    public function update(): void
    {
        $this->validate();

        if (is_null($this->vault)) {
            return;
        }

        $this->name = Str::trim($this->name);
        $this->vault->update([
            'name' => $this->name,
        ]);
    }
}
