<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Livewire\Component;

final class Index extends Component
{
    public function boot(): void
    {
        $this->redirect(route('vaults.index'), true);
    }
}
