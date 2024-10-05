<?php

namespace App\Actions;

use ZipArchive;
use App\Actions\GetPathFromVaultNode;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedVaults
{
    private $validExtensions = [
        'md',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'pdf',
        'webp',
        'mp4',
        'avi',
        'mp3',
        'flac',
    ];

    public function handle(string $fileName, string $filePath): void
    {
        $nodeIds = ['.' => null];

        // Create vault with zip name
        $vaultName = pathinfo($fileName, PATHINFO_FILENAME);
        $vault = auth()->user()->vaults()->create([
            'name' => $vaultName,
        ]);

        // Create vault nodes with valid zip files and folders
        $zip = new ZipArchive();
        $zip->open($filePath);
        for ($i = 0; $i < $zip->count(); $i++) {
            $entryName = $zip->getNameIndex($i);

            $isFile = substr($entryName, -1) !== DIRECTORY_SEPARATOR;
            $name = pathinfo($entryName, PATHINFO_FILENAME);
            $extension = null;
            $content = null;

            if (!$isFile) {
                // ZipArchive folder paths end with a DIRECTORY_SEPARATOR that should
                // be removed in order for pathinfo() return the correct dirname
                $entryDirName = substr($entryName, 0, -1);
                $entryParentDirName = pathinfo(substr($entryName, 0, -1), PATHINFO_DIRNAME);
                $parentId = $nodeIds[$entryParentDirName];
            } else {
                $entryDirName = pathinfo($entryName, PATHINFO_DIRNAME);
                $parentId = $nodeIds[$entryDirName];
                $extension = pathinfo($entryName, PATHINFO_EXTENSION);

                if (!in_array($extension, $this->validExtensions)) {
                    continue;
                }

                if ($extension === 'md') {
                    $content = $zip->getFromIndex($i);
                }
            }

            $node = $vault->nodes()->create([
                'parent_id' => $parentId,
                'is_file' => $isFile,
                'name' => $name,
                'extension' => $extension,
                'content' => $content,
            ]);

            if ($isFile && $extension != 'md') {
                $relativePath = (new GetPathFromVaultNode())->handle($node);
                Storage::disk('local')->put($relativePath, $zip->getFromIndex($i));
            }

            if (!array_key_exists($entryDirName, $nodeIds)) {
                $nodeIds[$entryDirName] = $node->id;
            }
        }

        $zip->close();
    }
}
