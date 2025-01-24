<?php

namespace App\Services\VaultFiles;

class File
{
    public static function extensionsWithDots(array $extensions): array
    {
        return array_map(fn(string $value): string => '.' . $value, $extensions);
    }
}
