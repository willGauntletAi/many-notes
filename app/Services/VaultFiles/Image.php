<?php

declare(strict_types=1);

namespace App\Services\VaultFiles;

class Image extends File
{
    private static array $extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ];

    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
