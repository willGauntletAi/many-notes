<?php

declare(strict_types=1);

namespace App\Actions;

use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;

final class ResolveTwoPaths
{
    public function handle(string $currentPath, string $path): string
    {
        $uri = Utils::uriFor(mb_trim($path));
        $resolvedUri = UriResolver::resolve(Utils::uriFor(mb_trim($currentPath)), $uri);

        return Str::ltrim($resolvedUri, '/');
    }
}
