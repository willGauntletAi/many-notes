<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use App\Actions\GetAvailableOAuthProviders;

class Login extends Component
{
    public LoginForm $form;

    public array $providers;

    public function mount()
    {
        $this->providers = new GetAvailableOAuthProviders()->handle();
    }

    /**
     * Handle an incoming authentication request.
     */
    public function send(): void
    {
        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('vaults.last', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
