<?php

declare(strict_types=1);

use App\Models\Vault;
use App\Models\VaultNode;

test('to array', function (): void {
    $node = VaultNode::factory()->create()->refresh();

    expect(array_keys($node->toArray()))
        ->toBe([
            'id',
            'vault_id',
            'parent_id',
            'is_file',
            'name',
            'extension',
            'content',
            'created_at',
            'updated_at',
            'vault',
            'parent',
        ]);
});

it('belongs to a vault', function (): void {
    $node = VaultNode::factory()->create();

    expect($node->vault)->toBeInstanceOf(Vault::class);
});

it('may have childs', function (): void {
    $node = VaultNode::factory()->hasChilds(3)->create();

    expect($node->childs)->toHaveCount(3)
        ->each->toBeInstanceOf(VaultNode::class);
});
