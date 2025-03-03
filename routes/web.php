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

    // Chat routes
    Route::get('/vaults/{vault}/chats', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::post('/vaults/{vault}/chats', [App\Http\Controllers\ChatController::class, 'store'])->name('chat.store');
    Route::get('/vaults/{vault}/chats/{chat}', [App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
    Route::put('/vaults/{vault}/chats/{chat}', [App\Http\Controllers\ChatController::class, 'update'])->name('chat.update');
    Route::delete('/vaults/{vault}/chats/{chat}', [App\Http\Controllers\ChatController::class, 'destroy'])->name('chat.destroy');
    Route::post('/vaults/{vault}/chats/{chat}/messages', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send-message');
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
