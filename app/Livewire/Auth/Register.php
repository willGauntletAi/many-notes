<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Livewire\Forms\RegisterForm;

class Register extends Component
{
    public RegisterForm $form;

    public function send()
    {
        $this->form->register();

        $this->redirect(route('vaults.index', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
