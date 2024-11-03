<?php

namespace App\Services;

use App\Services\VaultFiles\Pdf;
use App\Services\VaultFiles\Note;
use App\Services\VaultFiles\Audio;
use App\Services\VaultFiles\Image;
use App\Services\VaultFiles\Video;

class VaultFile
{
    public static function extensions(bool $withDots = false): array
    {
        return array_merge(
            Audio::extensions($withDots),
            Image::extensions($withDots),
            Note::extensions($withDots),
            Pdf::extensions($withDots),
            Video::extensions($withDots),
        );
    }
}
