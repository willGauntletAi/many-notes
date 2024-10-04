<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class Index extends Component
{
    public function boot(): void
    {
        $this->redirect(route('vaults.index'), true);
    }
}
