<?php

declare(strict_types=1);

use App\Actions\GetAvailableOAuthProviders;
use App\Http\Controllers\FileController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\OAuthLogin;
use App\Livewire\Auth\OAuthLoginCallback;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Vault\Index as VaultIndex;
use App\Livewire\Vault\Last as VaultLast;
use App\Livewire\Vault\Show as VaultShow;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardIndex::class)->name('dashboard.index');

    Route::prefix('vaults')->group(function (): void {
        Route::get('/', VaultIndex::class)->name('vaults.index');
        Route::get('/last', VaultLast::class)->name('vaults.last');
        Route::get('/{vault}', VaultShow::class)->name('vaults.show');
    });

    Route::get('files/{vault}', [FileController::class, 'show'])->name('files.show');
    
    // Transcription routes
    Route::prefix('transcription')->group(function (): void {
        Route::post('/file', [App\Http\Controllers\TranscriptionController::class, 'transcribeFile'])->name('transcription.file');
        Route::post('/base64', [App\Http\Controllers\TranscriptionController::class, 'transcribeBase64'])->name('transcription.base64');
    });
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
