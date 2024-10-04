<?php

namespace App\Actions;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;
use GuzzleHttp\Psr7\UriResolver;

class ResolveTwoPaths
{
    public function handle(string $currentPath, string $path): string
    {
        $uri = Utils::uriFor(trim($path));
        $resolvedUri = UriResolver::resolve(Utils::uriFor(trim($currentPath)), $uri);

        return Str::ltrim($resolvedUri, '/');
    }
}
