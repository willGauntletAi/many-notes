<?php

namespace App\Observers;

use App\Models\Vault;
use App\Actions\GetPathFromUser;
use Illuminate\Support\Facades\Storage;

class VaultObserver
{
    /**
     * Handle the Vault "creating" event.
     */
    public function creating(Vault $vault): void
    {
        $relativePath = new GetPathFromUser()->handle();

        if (Storage::disk('local')->exists($relativePath . $vault->name)) {
            abort(500);
        }

        Storage::disk('local')->makeDirectory($relativePath . $vault->name);
    }

    /**
     * Handle the Vault "updating" event.
     */
    public function updating(Vault $vault): void
    {
        $relativePath = new GetPathFromUser()->handle();

        if (Storage::disk('local')->exists($relativePath . $vault->name)) {
            abort(500);
        }

        Storage::disk('local')->move(
            $relativePath . $vault->getOriginal('name'),
            $relativePath . $vault->name,
        );
    }

    /**
     * Handle the Vault "deleting" event.
     */
    public function deleting(Vault $vault): void
    {
        $relativePath = new GetPathFromUser()->handle();
        Storage::disk('local')->deleteDirectory($relativePath . $vault->name);
    }
}
