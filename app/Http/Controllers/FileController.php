<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPathFromVaultNode;
use App\Actions\GetVaultNodeFromPath;
use App\Actions\ResolveTwoPaths;
use App\Models\Vault;
use App\Models\VaultNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FileController extends Controller
{
    /**
     * Show the file for a given user.
     */
    public function show(Vault $vault, Request $request): BinaryFileResponse
    {
        Gate::authorize('view', $request->vault);

        if (!$request->has('path')) {
            abort(404);
        }

        /** @var string $path */
        $path = $request->path;

        if (!str_starts_with($path, '/') && $request->has('node')) {
            /** @var VaultNode $node */
            $node = $vault->nodes()->findOrFail($request->node);

            if ($node->vault_id !== $vault->id) {
                abort(404);
            }

            /**
             * @var string $currentPath
             *
             * @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall
             */
            $currentPath = $node->ancestorsAndSelf()->get()->last()->full_path;
            $path = new ResolveTwoPaths()->handle($currentPath, $path);
        }

        /** @var VaultNode $node */
        $node = new GetVaultNodeFromPath()->handle($vault->id, $path);
        $relativePath = new GetPathFromVaultNode()->handle($node);
        $absolutePath = Storage::disk('local')->path($relativePath);

        ob_end_clean();

        return response()->file($absolutePath);
    }
}
