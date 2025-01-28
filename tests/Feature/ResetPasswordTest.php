<?php

declare(strict_types=1);

use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

it('returns a successful response', function (): void {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
        ->assertStatus(200);
});

it('resets the password', function (): void {
    $user = User::factory()->create();
    $token = Password::getRepository()->create($user);
    $newPassword = 'new-password';

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
        ->set('form.email', $user->email)
        ->set('form.password', $newPassword)
        ->set('form.password_confirmation', $newPassword)
        ->call('send')
        ->assertRedirect(route('login'));
});

it('fails resetting the password', function (): void {
    $user = User::factory()->create();
    Password::getRepository()->create($user);
    $newPassword = 'new-password';

    Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => 'invalid'])
        ->set('form.email', $user->email)
        ->set('form.password', $newPassword)
        ->set('form.password_confirmation', $newPassword)
        ->call('send')
        ->assertSee('This password reset token is invalid');
});
