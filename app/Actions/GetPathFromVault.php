<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Vault;

final class GetPathFromVault
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
