<?php

declare(strict_types=1);

namespace App\Services\VaultFiles;

final class Audio extends File
{
    /** @var list<string> */
    private static array $extensions = [
        'mp3',
        'flac',
    ];

    /**
     * Get the extensions for the audio files.
     *
     * @return list<string>
     */
    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? parent::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
