<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Vault;
use App\Models\VaultNode;
use App\Services\VaultFile;
use App\Services\VaultFiles\Note;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

final readonly class ProcessImportedFile
{
    public function handle(Vault $vault, VaultNode $parent, string $fileName, string $filePath): void
    {
        $pathInfo = pathinfo($fileName);
        $name = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        if (!in_array($extension, VaultFile::extensions())) {
            abort(400);
        }

        $content = null;
        if (in_array($extension, Note::extensions())) {
            $extension = 'md';
            $content = file_get_contents($filePath);
        }

        // Find new filename if it already exists
        $nodeExists = $vault->nodes()
            ->where('parent_id', $parent->id)
            ->where('is_file', true)
            ->where('name', 'like', "$name")
            ->where('extension', 'md')
            ->exists();
        if ($nodeExists) {
            /** @var list<string> $nodes */
            $nodes = array_column(
                $vault->nodes()
                    ->select('name')
                    ->where('parent_id', $parent->id)
                    ->where('is_file', true)
                    ->where('name', 'like', "$name-%")
                    ->where('extension', 'md')
                    ->get()
                    ->toArray(),
                'name',
            );
            natcasesort($nodes);
            $name .= count($nodes) && preg_match('/-(\d+)$/', end($nodes), $matches) === 1 ?
                '-' . ((int) $matches[1] + 1) :
                '-1';
        }

        $node = $vault->nodes()->createQuietly([
            'parent_id' => $parent->id,
            'is_file' => true,
            'name' => $name,
            'extension' => $extension,
            'content' => $content,
        ]);

        $relativePath = new GetPathFromVaultNode()->handle($node);
        $pathInfo = pathinfo($relativePath);
        $savePath = $pathInfo['dirname'] ?? '';
        $saveName = $pathInfo['basename'];
        Storage::putFileAs($savePath, new File($filePath), $saveName);
    }
}
