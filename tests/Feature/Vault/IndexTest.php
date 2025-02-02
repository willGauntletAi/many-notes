<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Actions\CreateVaultNode;
use App\Actions\GetPathFromUser;
use App\Actions\GetPathFromVault;
use App\Livewire\Vault\Index;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('only lists the user\'s vaults', function (): void {
    $user = User::factory(2)->hasVaults(2)->create()->first();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertViewHas('vaults', fn ($vaults): bool => count($vaults) === 2);
});

it('creates a vault', function (): void {
    $user = User::factory()->create();
    $vaultName = fake()->words(3, true);
    expect($user->vaults()->count())->toBe(0);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('form.name', $vaultName)
        ->call('create');
    expect($user->vaults()->count())->toBe(1);

    $relativePath = new GetPathFromUser()->handle($user);
    $absolutePath = Storage::disk('local')->path($relativePath . $vaultName);
    expect($absolutePath)->toBeDirectory();
});

it('exports a vault', function (): void {
    $user = User::factory()->create();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $folderNode = new CreateVaultNode()->handle($vault, [
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);
    new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'parent_id' => $folderNode->id,
        'name' => fake()->words(3, true),
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'name' => fake()->words(3, true),
        'extension' => 'jpg',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('export', $vault)
        ->assertFileDownloaded($vault->name . '.zip');
});

it('fails exporting an empty vault', function (): void {
    $user = User::factory()->hasVaults(1)->create();
    $vault = $user->vaults()->first();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('export', $vault)
        ->assertReturned(null);
});

it('fails exporting a vault with files missing on disk', function (): void {
    $user = User::factory()->hasVaults(1)->create();
    $vault = $user->vaults()->first();
    $vault->nodes()->create([
        'is_file' => true,
        'name' => fake()->words(3, true),
        'extension' => 'md',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('export', $vault)
        ->assertReturned(null);
});

it('deletes a vault', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $folderNode = new CreateVaultNode()->handle($vault, [
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);
    new CreateVaultNode()->handle($vault, [
        'is_file' => true,
        'parent_id' => $folderNode->id,
        'name' => fake()->words(3, true),
        'extension' => 'md',
        'content' => fake()->paragraph(),
    ]);
    expect($user->vaults()->count())->toBe(1);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('delete', $vault)
        ->assertDispatched('toast');
    expect($user->vaults()->count())->toBe(0);

    $relativePath = new GetPathFromVault()->handle($vault);
    $absolutePath = Storage::disk('local')->path($relativePath);
    expect($absolutePath)->not->toBeDirectory();
});
