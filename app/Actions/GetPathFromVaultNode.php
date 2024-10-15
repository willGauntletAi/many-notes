<?php

namespace App\Actions;

use App\Models\VaultNode;

class GetPathFromVaultNode
{
    public function handle(VaultNode $node): string
    {
        $path =
            'private/vaults' . DIRECTORY_SEPARATOR .
            auth()->user()->id . DIRECTORY_SEPARATOR .
            $node->vault_id . DIRECTORY_SEPARATOR .
            $node->id . '.' . $node->extension;

        return $path;
    }
}
