<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Actions\CreateVaultNode;
use App\Actions\ProcessVaultNodeLinks;
use App\Actions\ProcessVaultNodeTags;
use App\Models\Tag;
use App\Models\User;
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

it('may have links', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $folderName = fake()->words(3, true);
    $firstNodeName = fake()->words(3, true);
    $secondNodeName = fake()->words(3, true);
    $folderNode = new CreateVaultNode()->handle($vault, [
        'name' => $folderName,
        'is_file' => false,
    ]);
    $firstNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => $firstNodeName,
        'extension' => 'md',
        'content' => '[link](/' . $folderName . '/' . $secondNodeName . '.md)'
            . ' [link](' . $folderName . '/' . $secondNodeName . '.md)',
    ]);
    $secondNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'parent_id' => $folderNode->id,
        'name' => $secondNodeName,
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    new ProcessVaultNodeLinks()->handle($firstNode);

    expect($firstNode->links()->get())->toHaveCount(2)
        ->each->toBeInstanceOf(VaultNode::class);
    expect($firstNode->backlinks()->get())->toHaveCount(0)
        ->each->toBeInstanceOf(VaultNode::class);
    expect($secondNode->backlinks()->get())->toHaveCount(2)
        ->each->toBeInstanceOf(VaultNode::class);
    expect($secondNode->links()->get())->toHaveCount(0)
        ->each->toBeInstanceOf(VaultNode::class);
});

it('may have tags', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $firstNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => fake()->words(3, true),
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    $secondNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => fake()->words(3, true),
        'extension' => 'md',
        'content' => fake()->paragraph() . ' #test',
    ]);
    new ProcessVaultNodeTags()->handle($secondNode);

    expect($firstNode->tags()->count())->toBe(0);
    expect($secondNode->tags()->get())->toHaveCount(1)
        ->each->toBeInstanceOf(Tag::class);
});
