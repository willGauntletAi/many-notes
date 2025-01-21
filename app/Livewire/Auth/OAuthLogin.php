<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Laravel\Socialite\Facades\Socialite;

class OAuthLogin extends Component
{
    public function mount($provider)
    {
        $this->redirect(Socialite::driver($provider)->redirect()->getTargetUrl());
    }
}
