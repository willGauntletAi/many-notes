<?php

declare(strict_types=1);

namespace App\Actions;

final class GetPathFromUser
{
    public function handle(): string
    {
        return sprintf(
            'private/vaults/%u/',
            auth()->user()->id,
        );
    }
}
