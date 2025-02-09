<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Vault;

final readonly class ProcessVaultLinks
{
    public function handle(Vault $vault): void
    {
        $nodes = $vault->nodes()->where('is_file', true)->where('extension', 'md')->get();

        foreach ($nodes as $node) {
            new ProcessVaultNodeLinks()->handle($node);
        }
    }
}
