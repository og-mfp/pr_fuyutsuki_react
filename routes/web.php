<?php

use App\Enums\InstallationStatus;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ConfigurationController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\MaintenanceWindowController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FirstTimeSetupController;
use App\Http\Controllers\Settings\SecurityChallengeController;
use App\Services\InstallationService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::get('/', function (InstallationService $installation) {
    $status = $installation->status();
    if (in_array($status, [InstallationStatus::NotInstalled, InstallationStatus::Unknown], true)) {
        return redirect()->route('setup.create');
    }
    abort_if($status !== InstallationStatus::Installed, 503, 'Installation status cannot be verified.');

    return redirect()->route('login');
})->name('home');

Route::get('setup', [FirstTimeSetupController::class, 'create'])->name('setup.create');
Route::post('setup', [FirstTimeSetupController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('setup.store');

Route::post('register', [RegisteredUserController::class, 'store'])
    ->middleware(['web', 'throttle:registration', 'guest:web'])
    ->name('register.store');

Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['web', 'throttle:password-reset-link', 'guest:web'])
    ->name('password.email');

Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['web', 'throttle:password-reset-submit', 'guest:web'])
    ->name('password.update');

Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware('auth')
    ->name('verification.send');

Route::get('security-challenges/report/{id}/{token}', [SecurityChallengeController::class, 'showReport'])->name('security-challenges.report.show');
Route::post('security-challenges/report/{id}/{token}', [SecurityChallengeController::class, 'report'])->name('security-challenges.report');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    $catalogJson = file_get_contents(resource_path('ui-catalog.json'));
    if ($catalogJson === false) {
        throw new RuntimeException('Unable to read the UI catalog.');
    }
    /** @var array<int, array<string, mixed>> $catalogEntries */
    $catalogEntries = json_decode($catalogJson, true, 512, JSON_THROW_ON_ERROR);
    $catalog = collect($catalogEntries)->keyBy('slug');

    Route::get('ui/{component?}', function (?string $component = null) use ($catalog) {
        abort_if($component !== null && ! $catalog->has($component), 404);

        return redirect($component === null ? '/admin/ui' : '/admin/ui/'.$component);
    })->middleware('can:access-admin')->name('ui.index');

    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () use ($catalog) {
        Route::get('ui', fn () => Inertia::render('ui/Index', ['components' => $catalog->values()]))->name('ui.index');
        Route::get('ui/{component}', function (string $component) use ($catalog) {
            abort_unless($catalog->has($component), 404);
            $entry = $catalog->get($component);

            return Inertia::render($entry['page'], ['component' => $entry]);
        })->name('ui.show');
        Route::get('ui/error-preview/{status}', function (int $status) {
            abort_unless(app()->isLocal() && in_array($status, [403, 404, 419, 429, 500, 503], true), 404);
            abort($status);
        })->name('ui.error-preview');
        Route::inertia('/', 'admin/Index')->name('index');
        Route::get('configuration', [ConfigurationController::class, 'index'])->middleware('can:manage-system-settings')->name('configuration.index');
        Route::put('configuration/{category}', [ConfigurationController::class, 'update'])->middleware(['can:manage-system-settings', 'throttle:6,1'])->name('configuration.update');
        Route::get('maintenance', [MaintenanceWindowController::class, 'index'])->middleware('can:manage-system-settings')->name('maintenance.index');
        Route::post('maintenance', [MaintenanceWindowController::class, 'store'])->middleware(['can:manage-system-settings', 'throttle:10,1'])->name('maintenance.store');
        Route::put('maintenance/{window}', [MaintenanceWindowController::class, 'update'])->middleware(['can:manage-system-settings', 'throttle:10,1'])->name('maintenance.update');
        Route::delete('maintenance/{window}', [MaintenanceWindowController::class, 'destroy'])->middleware(['can:manage-system-settings', 'throttle:5,1'])->name('maintenance.destroy');
        Route::post('maintenance/reconcile', [MaintenanceWindowController::class, 'reconcile'])->middleware(['can:manage-system-settings', 'throttle:5,1'])->name('maintenance.reconcile');
        Route::post('configuration/test-notification', [ConfigurationController::class, 'testNotification'])
            ->middleware('can:manage-system-settings')->middleware('throttle:5,1')->name('configuration.test-notification');
        Route::post('configuration/emergency', [ConfigurationController::class, 'emergency'])
            ->middleware(['can:manage-system-settings', 'throttle:5,1'])->name('configuration.emergency');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])
            ->middleware('throttle:6,1')->name('users.password-reset');
        Route::post('users/{user}/verify-email', [UserController::class, 'sendVerificationEmail'])
            ->middleware('throttle:6,1')->name('users.verify-email');
        Route::put('users/{user}/freeze', [UserController::class, 'freeze'])->name('users.freeze');
        Route::delete('users/{user}/freeze', [UserController::class, 'unfreeze'])->name('users.unfreeze');
        Route::resource('groups', GroupController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('groups/{group}/roles', [GroupController::class, 'storeRole'])->name('groups.roles.store');
        Route::put('groups/{group}/roles/{role}/permissions', [GroupController::class, 'syncRolePermissions'])->name('groups.roles.permissions');
        Route::delete('groups/{group}/roles/{role}', [GroupController::class, 'destroyRole'])->name('groups.roles.destroy');
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activity}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    });
});

require __DIR__.'/settings.php';
