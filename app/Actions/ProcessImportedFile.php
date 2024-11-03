<?php

namespace App\Actions;

use App\Models\Vault;
use App\Models\VaultNode;
use Illuminate\Http\File;
use App\Services\VaultFile;
use App\Services\VaultFiles\Note;
use App\Actions\GetPathFromVaultNode;
use Illuminate\Support\Facades\Storage;

class ProcessImportedFile
{
    public function handle(Vault $vault, VaultNode $parent, string $fileName, string $filePath): void
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!in_array($extension, VaultFile::extensions())) {
            abort(400);
        }

        // Find new filename if it already exists
        $counter = 0;
        while (
            $vault->nodes()
                ->where('parent_id', $parent->id)
                ->where('is_file', true)
                ->where('name', !$counter ? $name : $name . '-' . $counter)
                ->where('extension', $extension)
                ->exists()
        ) {
            $counter++;
        }
        $name = !$counter ? $name : $name . '-' . $counter;

        $content = null;
        if (in_array($extension, Note::extensions())) {
            $content = file_get_contents($filePath);
        }

        $node = $vault->nodes()->create([
            'parent_id' => $parent->id,
            'is_file' => true,
            'name' => $name,
            'extension' => $extension,
            'content' => $content,
        ]);

        if (!in_array($extension, Note::extensions())) {
            $relativePath = (new GetPathFromVaultNode())->handle($node);
            $savePath = pathinfo($relativePath, PATHINFO_DIRNAME);
            $saveName = pathinfo($relativePath, PATHINFO_BASENAME);
            Storage::putFileAs($savePath, new File($filePath), $saveName);
        }
    }
}
