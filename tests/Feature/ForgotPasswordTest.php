<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Models\User;
use Livewire\Livewire;

it('returns a successful response', function (): void {
    Livewire::test(ForgotPassword::class)
        ->assertStatus(200);
});

it('sends a password reset link', function (): void {
    $user = User::factory()->create();

    Livewire::test(ForgotPassword::class)
        ->set('form.email', $user->email)
        ->call('send')
        ->assertSee('We have emailed your password reset link');
});

it('fails sending a password reset link', function (): void {
    Livewire::test(ForgotPassword::class)
        ->set('form.email', 'invalid@email.com')
        ->call('send')
        ->assertSee('We can\'t find a user with that email address');
});
