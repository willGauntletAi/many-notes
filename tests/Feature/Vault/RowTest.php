<?php

declare(strict_types=1);

use App\Actions\CreateVault;
use App\Actions\GetPathFromUser;
use App\Livewire\Vault\Row;
use App\Models\User;
use Livewire\Livewire;

it('updates the vault', function (): void {
    $user = User::factory()->create()->first();
    $vault = new CreateVault()->handle($user, [
        'name' => fake()->words(3, true),
    ]);
    $newName = fake()->words(3, true);

    Livewire::actingAs($user)
        ->test(Row::class, ['vault' => $vault])
        ->set('form.name', $newName)
        ->call('update');
    expect($user->vaults()->first()->name)->toBe($newName);

    $path = new GetPathFromUser()->handle($user) . $newName;
    expect(Storage::disk('local')->path($path))->toBeDirectory();
});
