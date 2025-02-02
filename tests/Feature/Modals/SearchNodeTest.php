<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Actions\CreateVaultNode;
use App\Livewire\Modals\SearchNode;
use App\Models\User;

it('opens the modal', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle(
        $user,
        ['name' => fake()->words(3, true)],
    );

    Livewire::actingAs($user)
        ->test(SearchNode::class, ['vault' => $vault])
        ->assertSet('show', false)
        ->call('open')
        ->assertSet('show', true);
});

it('searches for a node', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, ['name' => fake()->words(3, true)]);
    $firstNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => 'First note',
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    $secondNode = new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => 'Second note',
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);

    Livewire::actingAs($user)
        ->test(SearchNode::class, ['vault' => $vault])
        ->call('open')
        ->assertCount('nodes', 2)
        ->set('search', 'first')
        ->assertCount('nodes', 1);
});
