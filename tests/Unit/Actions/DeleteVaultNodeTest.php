<?php

declare(strict_types=1);

use App\Actions\DeleteVaultNode;
use App\Models\User;

it('deletes a vault without a folder in the disk', function (): void {
    $user = User::factory()->hasVaults(1)->create();
    $vault = $user->vaults()->first();
    $node = $vault->nodes()->create([
        'is_file' => false,
        'name' => fake()->words(3, true),
    ]);

    expect($vault->nodes()->count())->toBe(1);

    new DeleteVaultNode()->handle($node);

    expect($vault->nodes()->count())->toBe(0);
});
