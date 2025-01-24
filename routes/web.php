<?php

use App\Livewire\Auth\Login;
use App\Enums\OAuthProviders;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\OAuthLogin;
use Illuminate\Container\Container;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\ForgotPassword;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Livewire\Auth\OAuthLoginCallback;
use App\Livewire\Vault\Last as VaultLast;
use App\Livewire\Vault\Show as VaultShow;
use App\Actions\GetAvailableOAuthProviders;
use App\Livewire\Vault\Index as VaultIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardIndex::class)->name('dashboard.index');

    Route::prefix('vaults')->group(function (): void {
        Route::get('/', VaultIndex::class)->name('vaults.index');
        Route::get('/last', VaultLast::class)->name('vaults.last');
        Route::get('/{vault}', VaultShow::class)->name('vaults.show');
    });

    Route::get('files/{vault}', [FileController::class, 'show'])->name('files.show');
});

Route::middleware(['guest', 'throttle'])->group(function (): void {
    Route::get('register', Register::class)->name('register');
    Route::get('login', Login::class)->name('login');
    Route::get('forgot-password', ForgotPassword::class)->name('forgot.password');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::prefix('oauth')->group(function (): void {
        $providers = implode('|', array_map(
            fn ($provider) => $provider->value,
            new GetAvailableOAuthProviders()->handle(),
        ));

        if ($providers !== '') {
            Route::get('/{provider}', OAuthLogin::class)->where('provider', $providers);
            Route::get('/{provider}/callback', OAuthLoginCallback::class)->where('provider', $providers);
        }
    });
});
