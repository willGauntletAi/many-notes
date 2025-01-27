<?php

declare(strict_types=1);

namespace App\Services\VaultFiles;

final class Pdf
{
    /** @var list<string> */
    private static array $extensions = [
        'pdf',
    ];

    /**
     * Get the extensions for the pdf files.
     *
     * @return list<string>
     */
    public static function extensions(bool $withDots = false): array
    {
        return $withDots ? File::extensionsWithDots(self::$extensions) : self::$extensions;
    }
}
