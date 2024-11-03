<?php

namespace App\Services\VaultFiles;

use Illuminate\Support\Arr;

class File
{
    public static function extensionsWithDots(array $extensions): array
    {
        $mapped = Arr::map($extensions, function (string $value) {
            return '.' . $value;
        });

        return $mapped;
    }
}
