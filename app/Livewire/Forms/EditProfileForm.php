<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;

class EditProfileForm extends Form
{
    #[Validate]
    public string $name;

    #[Validate]
    public string $email;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore(auth()->user())],
        ];
    }

    public function setUser(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function update(): void
    {
        $this->name = Str::trim($this->name);
        $this->email = Str::trim($this->email);

        $this->validate();

        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);
    }
}
