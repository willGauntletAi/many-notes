<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OAuthProviders;

final class GetAvailableOAuthProviders
{
    public function handle(): array
    {
        return array_filter(
            OAuthProviders::cases(),
            fn ($provider) => env(mb_strtoupper($provider->value).'_CLIENT_ID'),
        );
    }
}
