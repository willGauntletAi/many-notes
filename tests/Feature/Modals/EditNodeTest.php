<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Actions\CreateVaultNode;
use App\Livewire\Modals\EditNode;
use App\Models\User;

it('opens the modal', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $node = new CreateVaultNode()->handle($vault, [
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);

    Livewire::actingAs($user)
        ->test(EditNode::class, ['vault' => $vault])
        ->assertSet('show', false)
        ->call('open', $node)
        ->assertSet('show', true);
});

it('updates a node', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $node = new CreateVaultNode()->handle($vault, [
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);
    $newName = fake()->words(4, true);

    Livewire::actingAs($user)
        ->test(EditNode::class, ['vault' => $vault])
        ->call('open', $node)
        ->set('form.name', $newName)
        ->call('edit')
        ->assertSet('show', false);
    expect($vault->nodes()->first()->name)->toBe($newName);
});
