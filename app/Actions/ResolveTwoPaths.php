<?php

declare(strict_types=1);

namespace App\Actions;

use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;

final readonly class ResolveTwoPaths
{
    public function handle(string $currentPath, string $path): string
    {
        $uri = Utils::uriFor(mb_trim($path));

        return (string) UriResolver::resolve(Utils::uriFor(mb_trim($currentPath)), $uri);
    }
}
