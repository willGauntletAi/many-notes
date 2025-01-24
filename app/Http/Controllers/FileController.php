<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPathFromVaultNode;
use App\Actions\GetVaultNodeFromPath;
use App\Actions\ResolveTwoPaths;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FileController extends Controller
{
    /**
     * Show the file for a given user.
     */
    public function show(Vault $vault, Request $request)
    {
        Gate::authorize('view', $request->vault);

        if (! $request->has('path')) {
            abort(404);
        }

        $path = $request->path;

        if (! Str::of($request->path)->startsWith('/') && $request->has('node')) {
            $node = $vault->nodes()->findOrFail($request->node);

            if ($node->vault_id !== $vault->id) {
                abort(404);
            }

            $currentPath = $node->ancestorsAndSelf()->get()->last()->full_path;
            $path = new ResolveTwoPaths()->handle($currentPath, $request->path);
        }

        $node = new GetVaultNodeFromPath()->handle($vault->id, $path);
        $relativePath = new GetPathFromVaultNode()->handle($node);
        $absolutePath = Storage::disk('local')->path($relativePath);

        ob_end_clean();

        return response()->file($absolutePath);
    }
}
