<?php

declare(strict_types=1);

use App\Actions\GetAvailableOAuthProviders;
use App\Enums\OAuthProviders;
use App\Livewire\Auth\OAuthLogin;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;

it('redirects to the provider url', function (): void {
    Socialite::shouldReceive('driver->redirect->getTargetUrl')->andReturn('https://github.com/login/oauth/authorize');
    $availableProviders = Mockery::mock(new GetAvailableOAuthProviders());
    $availableProviders->shouldReceive('handle')->andReturn([OAuthProviders::GitHub]);

    Livewire::test(OAuthLogin::class, ['provider' => 'github'])
        ->assertRedirect('https://github.com/login/oauth/authorize');
});

it('fails redirecting to the provider url', function (): void {
    Socialite::shouldReceive('driver->redirect->getTargetUrl')->andThrowExceptions([new Exception()]);
    $availableProviders = Mockery::mock(new GetAvailableOAuthProviders());
    $availableProviders->shouldReceive('handle')->andReturn([OAuthProviders::GitHub]);

    Livewire::test(OAuthLogin::class, ['provider' => 'github'])
        ->assertStatus(404);
});
