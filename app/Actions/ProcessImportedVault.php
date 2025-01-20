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

        // Find new vault name if it already exists
        $vault = auth()->user()
            ->vaults()
            ->where('name', 'like', "$vaultName%")
            ->orderByDesc('name')
            ->first();
        if ($vault) {
            $vaultName .= preg_match('/-(\d+)$/', $vault->name, $matches) === 1 ?
                '-' . ((int) $matches[1] + 1) :
                '-1';
        }

        $vault = auth()->user()->vaults()->create([
            'name' => $vaultName,
        ]);

        // Create vault nodes with valid zip files and folders
        $zip = new ZipArchive();
        $zip->open($filePath);
        for ($i = 0; $i < $zip->count(); $i++) {
            $entryName = $zip->getNameIndex($i);

            $isFile = substr($entryName, -1) !== '/';
            $flags = !$isFile ? PATHINFO_BASENAME : PATHINFO_FILENAME;
            $name = pathinfo($entryName, $flags);
            $extension = null;
            $content = null;

            if (!$isFile) {
                // ZipArchive folder paths end with a / that should
                // be removed in order for pathinfo() return the correct dirname
                $entryDirName = rtrim($entryName, '/');
                $entryParentDirName = pathinfo($entryDirName, PATHINFO_DIRNAME);
                $parentId = $nodeIds[$entryParentDirName];
            } else {
                ['dirname' => $entryDirName, 'extension' => $extension] = pathinfo($entryName);
                $parentId = $nodeIds[$entryDirName];

                if (!in_array($extension, VaultFile::extensions())) {
                    continue;
                }

                if (in_array($extension, Note::extensions())) {
                    $extension = 'md';
                    $content = $zip->getFromIndex($i);
                }
            }

            $node = $vault->nodes()->createQuietly([
                'parent_id' => $parentId,
                'is_file' => $isFile,
                'name' => $name,
                'extension' => $extension,
                'content' => $content,
            ]);

            $relativePath = new GetPathFromVaultNode()->handle($node);
            if ($isFile) {
                Storage::disk('local')->put($relativePath, $zip->getFromIndex($i));
            } else {
                Storage::disk('local')->makeDirectory($relativePath);
            }

            if (!array_key_exists($entryDirName, $nodeIds)) {
                $nodeIds[$entryDirName] = $node->id;
            }
        }

        $zip->close();
    }
}
