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
        ['filename' => $name, 'extension' => $extension] = pathinfo($fileName);

        if (!in_array($extension, VaultFile::extensions())) {
            abort(400);
        }

        $content = null;
        if (in_array($extension, Note::extensions())) {
            $extension = 'md';
            $content = file_get_contents($filePath);
        }

        // Find new filename if it already exists
        $counter = 0;
        while (
            $vault->nodes()
                ->where('parent_id', $parent->id)
                ->where('is_file', true)
                ->where('name', $counter > 0 ? "$name-$counter" : $name)
                ->where('extension', $extension)
                ->exists()
        ) {
            $counter++;
        }
        $name .= $counter > 0? "-$counter" : '';

        $node = $vault->nodes()->createQuietly([
            'parent_id' => $parent->id,
            'is_file' => true,
            'name' => $name,
            'extension' => $extension,
            'content' => $content,
        ]);

        $relativePath = new GetPathFromVaultNode()->handle($node);
        ['dirname' => $savePath, 'basename' => $saveName] = pathinfo($relativePath);
        Storage::putFileAs($savePath, new File($filePath), $saveName);
    }
}
