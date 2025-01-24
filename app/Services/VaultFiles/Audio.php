<?php

declare(strict_types=1);

namespace App\Services\VaultFiles;

class Audio extends File
{
    private static array $extensions = [
        'mp3',
        'flac',
    ];

    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
