<?php

declare(strict_types=1);

use App\Livewire\Vault\TreeView;
use App\Models\User;
use Livewire\Livewire;

it('renders the tree view', function (): void {
    $user = User::factory()->hasVaults(1)->create()->first();
    $vault = $user->vaults()->first();

    Livewire::actingAs($user)
        ->test(TreeView::class, ['vault' => $vault])
        ->assertSee('Your vault is empty.');
});
