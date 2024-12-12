<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Illuminate\Support\Arr;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class EditPasswordForm extends Form
{
    #[Validate]
    public string $current_password = '';

    #[Validate]
    public string $password = '';

    #[Validate]
    public string $password_confirmation = '';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    public function update(): void
    {
        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            if (Arr::exists($e->errors(), 'passwordForm.current_password')) {
                $this->reset('current_password');
            }
            $this->reset('password', 'password_confirmation');

            throw $e;
        }

        $this->reset('current_password', 'password', 'password_confirmation');

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
    }
}
