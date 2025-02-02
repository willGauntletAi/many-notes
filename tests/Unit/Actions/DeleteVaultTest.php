<?php

declare(strict_types=1);

use App\Actions\DeleteVault;
use App\Models\User;

/*
it('throw an exception', function (): void {
    $user = User::factory()->hasVaults(1)->create();
    $vault = $user->vaults()->first();

    //DB::shouldReceive('beginTransaction')
    //    ->andThrow(new Exception());
    Mockery::mock(new Vault())
        ->makePartial()
        ->shouldReceive('nodes')
        ->andThrow(new Exception());

    //expect(new DeleteVault()->handle($vault))
    //    ->toThrow(Exception::class);
});
*/

it('deletes a vault without a folder in the disk', function (): void {
    $user = User::factory()->hasVaults(1)->create();
    $vault = $user->vaults()->first();

    expect($user->vaults()->count())->toBe(1);

    new DeleteVault()->handle($vault);

    expect($user->vaults()->count())->toBe(0);
});
