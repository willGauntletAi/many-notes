<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Exception;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Component;

final class OAuthLogin extends Component
{
    public function mount(string $provider): void
    {
        try {
            $this->redirect(Socialite::driver($provider)->redirect()->getTargetUrl());
        } catch (Exception) {
            abort(404);
        }
    }
}
