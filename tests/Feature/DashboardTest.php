<?php

declare(strict_types=1);

use Livewire\Livewire;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Auth\Login;

it('redirects guests to login page', function (): void {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

it('redirects users to vaults page', function (): void {
    Livewire::test(DashboardIndex::class)
        ->assertRedirect('/vaults');
});
