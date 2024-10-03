<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Livewire\Forms\ResetPasswordForm;

class ResetPassword extends Component
{
    public ResetPasswordForm $form;

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->form->setToken($token);
        $this->form->setEmail(request()->string('email'));
    }

    public function send(): void
    {
        if (!$this->form->resetPassword()) {
            return;
        }

        $this->redirect(route('login', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
