<?php

use App\Http\Controllers\Settings\ProfileAvatarController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityChallengeController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\TrustedDeviceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('settings/security-challenges', [SecurityChallengeController::class, 'store'])->name('security-challenges.store');
    Route::post('settings/security-challenges/{challenge}/confirm', [SecurityChallengeController::class, 'confirm'])->name('security-challenges.confirm');
    // Keep the redirect methods without generating an unused RedirectController action.
    Route::any('settings', fn () => redirect('/settings/profile'));

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('settings/profile/avatar', [ProfileAvatarController::class, 'show'])->name('profile.avatar.show');
    Route::post('settings/profile/avatar', [ProfileAvatarController::class, 'store'])->middleware('throttle:5,1')->name('profile.avatar.store');
    Route::delete('settings/profile/avatar', [ProfileAvatarController::class, 'destroy'])->middleware('throttle:5,1')->name('profile.avatar.destroy');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->middleware('throttle:6,1')->name('profile.update');
    Route::get('settings/localization', [ProfileController::class, 'editLocalization'])->name('localization.edit');
    Route::patch('settings/localization', [ProfileController::class, 'updateLocalization'])->name('localization.update');
    Route::patch('settings/profile/personal-information', [ProfileController::class, 'updatePersonalInformation'])->name('profile.personal-information.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->middleware('throttle:6,1')->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');
    Route::delete('settings/security/trusted-devices/{device}', [TrustedDeviceController::class, 'destroy'])
        ->name('security.trusted-devices.destroy');
    Route::delete('settings/security/trusted-devices', [TrustedDeviceController::class, 'destroyAll'])
        ->name('security.trusted-devices.destroy-all');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
