<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;
use SocialiteProviders\Authentik\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('authentik', Provider::class);
        });
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('keycloak', Provider::class);
        });
    }
}
