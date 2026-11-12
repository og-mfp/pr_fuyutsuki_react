<?php

namespace App\Listeners;

use App\Models\User;
use App\Security\RiskCounterService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;

class GuardRiskChallenge
{
    public function handle(object $event): void
    {
        $challenge = request()->session()->get('risk.challenge');
        if (! is_array($challenge)) {
            return;
        }

        if ($event instanceof TwoFactorAuthenticationFailed) {
            $key = app(RiskCounterService::class)->key('challenge-failed', request()->session()->getId());
            if (! RateLimiter::tooManyAttempts($key, 3)) {
                RateLimiter::hit($key, 300);
                $log = activity('security')->event('login.risk.challenge-failed')->causedByAnonymous();
                $userId = $challenge['user_id'] ?? null;
                if (is_int($userId) && ($user = User::find($userId))) {
                    $log->performedOn($user);
                }
                $log->log('login.risk.challenge-failed');
            }

            return;
        }

        if ($event instanceof ValidTwoFactorAuthenticationCodeProvided) {
            request()->session()->forget('risk.challenge');
            if (($challenge['user_id'] ?? null) !== $event->user->id || ($challenge['expires_at'] ?? 0) < time()) {
                request()->session()->forget(['login.id', 'login.remember']);
                throw ValidationException::withMessages(['code' => 'The challenge has expired. Sign in again.']);
            }
            activity('security')->event('login.risk.challenge-succeeded')->performedOn($event->user)->causedByAnonymous()->log('login.risk.challenge-succeeded');
        }
    }
}
