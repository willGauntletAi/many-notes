<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VaultNode;

final class GetPathFromVaultNode
{
    public function handle(VaultNode $node, bool $includeSelf = true): string
    {
        $relativePath = $node->parent ?
            $node->parent->ancestorsAndSelf->last()->full_path.'/' :
            '';

        $path = sprintf(
            'private/vaults/%u/%s/%s',
            auth()->user()->id,
            $node->vault->name,
            $relativePath,
        );

        if ($includeSelf) {
            $path .= $node->name.($node->is_file ? '.'.$node->extension : '');
        }

        return $path;
    }
}
