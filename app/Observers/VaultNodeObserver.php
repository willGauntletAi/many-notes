<?php

namespace App\Observers;

use App\Models\VaultNode;
use App\Actions\GetPathFromVaultNode;
use Illuminate\Support\Facades\Storage;

class VaultNodeObserver
{
    /**
     * Handle the VaultNode "deleted" event.
     */
    public function deleted(VaultNode $vaultNode): void
    {
        if (!$vaultNode->is_file || $vaultNode->extension == 'md') {
            return;
        }

        $relativePath = (new GetPathFromVaultNode())->handle($vaultNode);
        Storage::disk('local')->delete($relativePath);
    }
}
