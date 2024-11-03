<?php

namespace App\Services\VaultFiles;

class Video extends File
{
    private static array $extensions = [
        'mp4',
        'avi',
    ];

    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
