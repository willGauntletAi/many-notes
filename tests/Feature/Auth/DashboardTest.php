<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Index;
use Livewire\Livewire;

it('redirects guests to login page', function (): void {
    $this->get('/')
        ->assertRedirect(route('login'));
});

it('redirects users to vaults page', function (): void {
    Livewire::test(Index::class)
        ->assertRedirect(route('vaults.index'));
});
