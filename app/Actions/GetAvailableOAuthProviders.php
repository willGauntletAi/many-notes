<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OAuthProviders;

final class GetAvailableOAuthProviders
{
    /** @return array<int, OAuthProviders> */
    public function handle(): array
    {
        return array_filter(
            OAuthProviders::cases(),
            /** @phpstan-ignore-next-line */
            fn ($provider) => config("services.{$provider->value}.client_id"),
        );
    }
}
