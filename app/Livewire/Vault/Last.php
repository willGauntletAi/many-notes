<?php

namespace App\Livewire\Vault;

use Livewire\Component;

class Last extends Component
{
    public function mount(): void
    {
        $lastVault = auth()->user()->vaults()->orderBy('opened_at', 'desc')->first();

        if (!$lastVault) {
            $this->redirect(route('vaults.index'), navigate: true);
            return;
        }

        $this->redirect(route('vaults.show', ['vault' => $lastVault]), navigate: true);
    }
}
