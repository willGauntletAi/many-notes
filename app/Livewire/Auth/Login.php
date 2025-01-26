<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\GetAvailableOAuthProviders;
use App\Enums\OAuthProviders;
use App\Livewire\Forms\LoginForm;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

final class Login extends Component
{
    public LoginForm $form;

    /** @var array<int, OAuthProviders> */
    public array $providers;

    public function mount(): void
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

    public function render(): Factory|View
    {
        return view('livewire.auth.login');
    }
}
