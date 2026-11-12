<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AdminAccessGuard;
use App\Services\SettingsService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RejectFrozenLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User || (! $event->user->frozen_at && ! $event->user->security_recovery_required
            && (! app(SettingsService::class)->get('authentication.authentication_lockdown')
                || app(AdminAccessGuard::class)->isOwner($event->user)))) {
            return;
        }

        Auth::guard($event->guard)->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        throw ValidationException::withMessages(['email' => $event->user->security_recovery_required
            ? 'This account requires recovery. Reset your password or contact an administrator.'
            : ($event->user->frozen_at ? 'This account is frozen. Contact an administrator.' : 'Authentication is temporarily locked.')]);
    }
}
