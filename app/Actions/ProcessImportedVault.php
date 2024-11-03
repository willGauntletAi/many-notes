<?php

namespace App\Actions;

use ZipArchive;
use App\Services\VaultFile;
use App\Services\VaultFiles\Note;
use App\Actions\GetPathFromVaultNode;
use Illuminate\Support\Facades\Storage;

class ProcessImportedVault
{
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
            $flags = !$isFile ? PATHINFO_BASENAME : PATHINFO_FILENAME;
            $name = pathinfo($entryName, $flags);
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

                if (!in_array($extension, VaultFile::extensions())) {
                    continue;
                }

                if (in_array($extension, Note::extensions())) {
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

            if ($isFile && !in_array($extension, Note::extensions())) {
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
