<?php

namespace App\Services\VaultFiles;

class Note extends File
{
    private static array $extensions = [
        'md',
    ];

    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
