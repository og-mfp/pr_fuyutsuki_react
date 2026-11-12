<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class AuditAuthentication
{
    public function handle(object $event): void
    {
        $name = match (true) {
            $event instanceof Login => 'login',
            $event instanceof Logout => 'logout',
            $event instanceof Failed => 'login.failed',
            $event instanceof Lockout => 'login.locked',
            $event instanceof PasswordReset => 'password.reset',
            $event instanceof TwoFactorAuthenticationEnabled => 'two-factor.enabled',
            $event instanceof TwoFactorAuthenticationConfirmed => 'two-factor.confirmed',
            $event instanceof TwoFactorAuthenticationDisabled => 'two-factor.disabled',
            default => null,
        };
        if ($name === null) {
            return;
        }

        $ip = request()->ip();
        if (! is_string($ip) || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = null;
        }
        if ($event instanceof Failed) {
            $key = 'audit-failed:'.($ip ?? 'unknown');
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return;
            }
            RateLimiter::hit($key, 60);
        }

        $user = $event->user ?? null;
        $logger = activity('security')->event($name)->withProperties($ip ? ['ip' => $ip] : []);
        if ($event instanceof Failed || $event instanceof Lockout) {
            $logger->causedByAnonymous();
        } elseif ($user instanceof User) {
            $logger->causedBy($user)->performedOn($user);
        }
        $logger->log($name);
    }
}
