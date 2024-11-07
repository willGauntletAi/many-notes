<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserMenu extends Component
{
    public string $appVersion;

    public string $githubUrl;

    public function mount(): void
    {
        $composerInfo = require base_path('vendor/composer/installed.php');
        $this->appVersion = $composerInfo['root']['pretty_version'];
        $this->githubUrl = 'https://github.com/brufdev/many-notes';
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        $this->redirect(route('login', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.layout.userMenu');
    }
}
