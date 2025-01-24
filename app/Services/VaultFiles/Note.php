<?php

declare(strict_types=1);

namespace App\Services\VaultFiles;

class Note extends File
{
    private static array $extensions = [
        'md',
        'txt',
    ];

    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
