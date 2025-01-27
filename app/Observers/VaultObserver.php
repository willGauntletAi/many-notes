<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\GetPathFromUser;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\Storage;

final readonly class VaultObserver
{
    /**
     * Handle the Vault "creating" event.
     */
    public function creating(Vault $vault): void
    {
        /** @var User $user */
        $user = $vault->user;

        $relativePath = new GetPathFromUser()->handle($user);

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
        /** @var User $user */
        $user = $vault->user;

        if (!$vault->isDirty('name')) {
            return;
        }

        $relativePath = new GetPathFromUser()->handle($user);

        if (Storage::disk('local')->exists($relativePath . $vault->name)) {
            abort(500);
        }

        /** @var string $originalName */
        $originalName = $vault->getOriginal('name');
        Storage::disk('local')->move(
            $relativePath . $originalName,
            $relativePath . $vault->name,
        );
    }

    /**
     * Handle the Vault "deleting" event.
     */
    public function deleting(Vault $vault): void
    {
        /** @var User $user */
        $user = $vault->user;

        $relativePath = new GetPathFromUser()->handle($user);
        Storage::disk('local')->deleteDirectory($relativePath . $vault->name);
    }
}
