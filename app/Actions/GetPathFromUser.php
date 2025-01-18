<?php

namespace App\Actions;

class GetPathFromUser
{
    public function handle(): string
    {
        return sprintf(
            'private/vaults/%u/',
            auth()->user()->id,
        );
    }
}
