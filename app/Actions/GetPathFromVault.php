<?php

namespace App\Actions;

use App\Models\Vault;

class GetPathFromVault
{
    public function handle(Vault $vault): string
    {
        return sprintf(
            'private/vaults/%u/%s/',
            auth()->user()->id,
            $vault->name,
        );
    }
}
