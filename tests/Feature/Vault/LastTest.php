<?php

declare(strict_types=1);

use App\Livewire\Vault\Last;
use App\Models\User;
use Livewire\Livewire;

it('redirects to list of vaults', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Last::class)
        ->assertRedirect(route('vaults.index'));
});

it('redirects to last opened vault', function (): void {
    $user = User::factory()->hasVaults(2)->create();
    $vault = $user->vaults()->first();
    $vault->update(['opened_at' => now()]);

    Livewire::actingAs($user)
        ->test(Last::class)
        ->assertRedirect(route('vaults.show', ['vault' => $vault]));
});
