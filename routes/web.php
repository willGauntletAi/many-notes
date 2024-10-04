<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\ForgotPassword;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;
use App\Livewire\Vault\Show as VaultShow;
use App\Livewire\Vault\Index as VaultIndex;

Route::middleware('auth')->group(function () {
    Route::get('vaults', VaultIndex::class)->name('vaults.index');
    Route::get('vaults/{vault}', VaultShow::class)->name('vaults.show');

    Route::get('files/{vault}', [FileController::class, 'show'])->name('files.show');
});

Route::middleware('guest')->group(function () {
    Route::get('register', Register::class)->name('register');
    Route::get('login', Login::class)->name('login');
    Route::get('forgot-password', ForgotPassword::class)->name('forgot.password');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
});
