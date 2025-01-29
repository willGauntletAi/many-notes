<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('returns a successful response', function (): void {
    Livewire::test(Login::class)
        ->assertStatus(200);
});

it('successfully authenticates user', function (): void {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('send')
        ->assertRedirect(route('vaults.last'));
});

it('gets rate limited', function (): void {
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('form.email', 'test@example.com')
            ->set('form.password', 'password')
            ->call('send');
    }

    Livewire::test(Login::class)
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'password')
        ->call('send')
        ->assertHasErrors(['form.email']);
});
