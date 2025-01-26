<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\GetPathFromUser;
use App\Models\Vault;
use Illuminate\Support\Facades\Storage;

final class VaultObserver
{
    /**
     * Handle the Vault "creating" event.
     */
    public function creating(Vault $vault): void
    {
        $relativePath = new GetPathFromUser()->handle();

        if (Storage::disk('local')->exists($relativePath.$vault->name)) {
            abort(500);
        }

        Storage::disk('local')->makeDirectory($relativePath.$vault->name);
    }

    /**
     * Handle the Vault "updating" event.
     */
    public function updating(Vault $vault): void
    {
        if (! $vault->isDirty('name')) {
            return;
        }

        $relativePath = new GetPathFromUser()->handle();

        if (Storage::disk('local')->exists($relativePath.$vault->name)) {
            abort(500);
        }

        /** @var string $originalName */
        $originalName = $vault->getOriginal('name');
        Storage::disk('local')->move(
            $relativePath.$originalName,
            $relativePath.$vault->name,
        );
    }

    /**
     * Handle the Vault "deleting" event.
     */
    public function deleting(Vault $vault): void
    {
        $relativePath = new GetPathFromUser()->handle();
        Storage::disk('local')->deleteDirectory($relativePath.$vault->name);
    }
}
