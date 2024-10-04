<?php

namespace App\Actions;

use App\Models\VaultNode;

class GetUrlFromVaultNode
{
    public function handle(VaultNode $node): string
    {
        $path = $node->ancestorsAndSelf()->get()->last()->full_path;

        $url = '/files/' . $node->vault_id . '?path=' . $path . '.' . $node->extension;

        return $url;
    }
}
