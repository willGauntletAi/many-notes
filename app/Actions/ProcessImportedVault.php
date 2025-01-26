<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\VaultFile;
use App\Services\VaultFiles\Note;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

final class ProcessImportedVault
{
    public function handle(string $fileName, string $filePath): void
    {
        $nodeIds = ['.' => null];
        /** @var User $currentUser */
        $currentUser = auth()->user();
        // Create vault with zip name
        $vaultName = pathinfo($fileName, PATHINFO_FILENAME);
        // Find new vault name if it already exists
        $vaultExists = $currentUser->vaults()
            ->where('name', 'like', "$vaultName")
            ->exists();

        if ($vaultExists) {
            /** @var list<string> $vaults */
            $vaults = array_column(
                $currentUser->vaults()
                    ->select('name')
                    ->where('name', 'like', "$vaultName-%")
                    ->get()
                    ->toArray(),
                'name',
            );
            natcasesort($vaults);
            $vaultName .= count($vaults) && preg_match('/-(\d+)$/', end($vaults), $matches) === 1 ?
                '-'.((int) $matches[1] + 1) :
                '-1';
        }

        $vault = $currentUser->vaults()->create([
            'name' => $vaultName,
        ]);

        // Create vault nodes with valid zip files and folders
        $zip = new ZipArchive();
        $zip->open($filePath);
        for ($i = 0, $zipCount = $zip->count(); $i < $zipCount; $i++) {
            $entryName = $zip->getNameIndex($i);

            if (! $entryName) {
                continue;
            }

            $isFile = ! str_ends_with($entryName, '/');
            $flags = $isFile ? PATHINFO_FILENAME : PATHINFO_BASENAME;
            $name = pathinfo($entryName, $flags);
            $extension = null;
            $content = null;

            if (! $isFile) {
                // ZipArchive folder paths end with a / that should
                // be removed in order for pathinfo() return the correct dirname
                $entryDirName = mb_rtrim($entryName, '/');
                $entryParentDirName = pathinfo($entryDirName, PATHINFO_DIRNAME);
                $parentId = $nodeIds[$entryParentDirName];
            } else {
                $pathInfo = pathinfo($entryName);
                $entryDirName = $pathInfo['dirname'];
                $extension = $pathInfo['extension'] ?? '';
                $parentId = $nodeIds[$entryDirName];

                if (! in_array($extension, VaultFile::extensions())) {
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
                /** @var string $contents */
                $contents = $zip->getFromIndex($i);
                Storage::disk('local')->put($relativePath, $contents);
            } else {
                Storage::disk('local')->makeDirectory($relativePath);
            }

            if (! array_key_exists($entryDirName, $nodeIds)) {
                $nodeIds[$entryDirName] = $node->id;
            }
        }

        $zip->close();
    }
}
