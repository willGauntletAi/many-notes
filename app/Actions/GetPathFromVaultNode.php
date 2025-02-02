<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\Vault;
use App\Models\VaultNode;

final readonly class GetPathFromVaultNode
{
    public function handle(VaultNode $node, bool $includeSelf = true): string
    {
        /** @var Vault $vault */
        $vault = $node->load(['vault', 'parent'])->vault;
        /** @var User $user */
        $user = $vault->user()->first();
        $relativePath = '';

        if ($node->parent) {
            /**
             * @var string $fullPath
             *
             * @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall
             */
            $fullPath = $node->parent->ancestorsAndSelf()->get()->last()->full_path;
            $relativePath = $fullPath . '/';
        }

        $path = sprintf(
            'private/vaults/%u/%s/%s',
            $user->id,
            $vault->name,
            $relativePath,
        );

        if ($includeSelf) {
            $path .= $node->name . ($node->is_file ? '.' . $node->extension : '');
        }

        return $path;
    }
}
