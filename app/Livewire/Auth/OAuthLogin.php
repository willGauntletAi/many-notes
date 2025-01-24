<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Laravel\Socialite\Facades\Socialite;

class OAuthLogin extends Component
{
    public function mount($provider): void
    {
        $this->redirect(Socialite::driver($provider)->redirect()->getTargetUrl());
    }
}
