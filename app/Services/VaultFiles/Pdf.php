<?php

namespace App\Services\VaultFiles;

class Pdf extends File
{
    private static array $extensions = [
        'pdf',
    ];

    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
