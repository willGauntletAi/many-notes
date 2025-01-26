<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Vault;

final class GetPathFromVault
{
    public function handle(Vault $vault): string
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        return sprintf(
            'private/vaults/%u/%s/',
            $currentUser->id,
            $vault->name,
        );
    }
}
