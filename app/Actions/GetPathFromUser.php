<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final class GetPathFromUser
{
    public function handle(): string
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        return sprintf(
            'private/vaults/%u/',
            $currentUser->id,
        );
    }
}
