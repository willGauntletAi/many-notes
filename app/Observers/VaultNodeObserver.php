<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\GetPathFromVaultNode;
use App\Models\VaultNode;
use Illuminate\Support\Facades\Storage;

final readonly class VaultNodeObserver
{
    /**
     * Handle the VaultNode "creating" event.
     */
    public function creating(VaultNode $node): void
    {
        $relativePath = new GetPathFromVaultNode()->handle($node);

        if (Storage::disk('local')->exists($relativePath)) {
            abort(500);
        }

        if ($node->is_file) {
            Storage::disk('local')->put($relativePath, '');
        } else {
            Storage::disk('local')->makeDirectory($relativePath);
        }
    }

    /**
     * Handle the VaultNode "updating" event.
     */
    public function updating(VaultNode $node): void
    {
        $relativePath = new GetPathFromVaultNode()->handle($node, false);

        if (Storage::disk('local')->exists($relativePath . $node->name)) {
            abort(500);
        }

        if ($node->isDirty('name')) {
            /** @var string $originalName */
            $originalName = $node->getOriginal('name');
            $paths = [
                $relativePath . $originalName,
                $relativePath . $node->name,
            ];
            if ($node->is_file) {
                $paths[0] .= '.' . $node->extension;
                $paths[1] .= '.' . $node->extension;
            }
            Storage::disk('local')->move(...$paths);
        }

        if ($node->is_file) {
            Storage::disk('local')->put(
                $relativePath . $node->name . '.' . $node->extension,
                $node->content ?? '',
            );
        }
    }

    /**
     * Handle the VaultNode "deleting" event.
     */
    public function deleting(VaultNode $node): void
    {
        $relativePath = new GetPathFromVaultNode()->handle($node);

        if ($node->is_file) {
            Storage::disk('local')->delete($relativePath);
        } else {
            Storage::disk('local')->deleteDirectory($relativePath);
        }
    }
}
