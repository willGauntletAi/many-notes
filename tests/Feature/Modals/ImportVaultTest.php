<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Livewire\Modals\ImportVault;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/*beforeEach(function (): void {
    $this->var = 2;
});*/

it('opens the modal', function (): void {
    $user = User::factory()->create()->first();

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->assertSet('show', false)
        ->call('open')
        ->assertSet('show', true);
});

it('imports a zip file', function (): void {
    $user = User::factory()->create()->first();
    $file = UploadedFile::fake()->create('test.zip');

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->set('file', $file)
        ->assertSet('show', false);
});

it('handles name collisions when importing a vault with an existing name', function (): void {
    $user = User::factory()->create()->first();
    $vaultName = fake()->words(3, true);
    new CreateVault()->handle($user, [
        'name' => $vaultName,
    ]);
    $file = UploadedFile::fake()->create($vaultName . '.zip');

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->call('open')
        ->set('file', $file);

    $vaults = $user->vaults()->get();
    expect($vaults->count())->toBe(2);
    expect($vaults->get(0)->name)->toBe($vaultName);
    expect($vaults->get(1)->name)->toBe($vaultName . '-1');
});

it('handles name collisions when importing a vault with a name existing in multiple vaults', function (): void {
    $user = User::factory()->create()->first();
    $vaultName = fake()->words(3, true);
    new CreateVault()->handle($user, ['name' => $vaultName]);
    new CreateVault()->handle($user, ['name' => $vaultName]);
    $file = UploadedFile::fake()->create($vaultName . '.zip');

    Livewire::actingAs($user)
        ->test(ImportVault::class)
        ->call('open')
        ->set('file', $file);

    $vaults = $user->vaults()->get();
    expect($vaults->count())->toBe(3);
    expect($vaults->get(0)->name)->toBe($vaultName);
    expect($vaults->get(1)->name)->toBe($vaultName . '-1');
    expect($vaults->get(2)->name)->toBe($vaultName . '-2');
});
