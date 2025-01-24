<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VaultNode;

final class GetUrlFromVaultNode
{
    public function handle(VaultNode $node): string
    {
        $path = $node->ancestorsAndSelf()->get()->last()->full_path;

        return '/files/'.$node->vault_id.'?path='.$path.'.'.$node->extension;
    }
}
