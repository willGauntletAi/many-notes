<?php

declare(strict_types=1);

namespace App\Services\VaultFiles;

final class Note extends File
{
    /** @var list<string> */
    private static array $extensions = [
        'md',
        'txt',
    ];

    /**
     * Get the extensions for the note files.
     * 
     * @return list<string>
     */
    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
