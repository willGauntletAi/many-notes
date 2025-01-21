<?php

namespace App\Actions;

use App\Enums\OAuthProviders;

class GetAvailableOAuthProviders
{
    public function handle(): array
    {
        return array_filter(
            OAuthProviders::cases(),
            fn($provider) => env(mb_strtoupper($provider->value) . '_CLIENT_ID'),
        );
    }
}
