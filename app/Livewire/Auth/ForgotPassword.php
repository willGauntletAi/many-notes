<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Livewire\Forms\ForgotPasswordForm;

class ForgotPassword extends Component
{
    public ForgotPasswordForm $form;

    public function send()
    {
        $this->form->sendPasswordResetLink();
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
