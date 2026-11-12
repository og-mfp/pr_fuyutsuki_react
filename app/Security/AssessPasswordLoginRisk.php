<?php

namespace App\Security;

use App\Events\HighRiskAuthenticationDetected;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;

class AssessPasswordLoginRisk extends RedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        if (! app(SettingsService::class)->get('authentication.mfa_enabled')) {
            $this->validateCredentials($request);

            return $next($request);
        }

        return parent::handle($request, $next);
    }

    protected function validateCredentials($request)
    {
        $user = parent::validateCredentials($request);
        if (! $user instanceof User) {
            return $user;
        }

        $settings = app(SettingsService::class);
        $counters = app(RiskCounterService::class);
        $cooldown = $counters->key('cooldown', (string) $request->ip().'|'.$user->id);
        $recovery = $request->session()->pull('risk.recovery');
        if (is_array($recovery) && ($recovery['user_id'] ?? null) === $user->id
            && ($recovery['expires_at'] ?? 0) >= time()) {
            RateLimiter::clear($cooldown);
            activity('security')->event('login.risk.recovered')->performedOn($user)->causedByAnonymous()->log('login.risk.recovered');

            return $user;
        }
        if ($settings->get('authentication.risk_mode') === 'enforce'
            && $settings->get('authentication.risk_step_up')
            && RateLimiter::tooManyAttempts($cooldown, 1)) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }
        $decision = app(LoginRiskEvaluator::class)->evaluate($request, $user);
        if ($decision->level === 'low') {
            return $user;
        }

        $key = $counters->key('risk-audit', (string) $request->ip().'|'.$user->id.'|'.$decision->level);
        if (! RateLimiter::tooManyAttempts($key, 1)) {
            RateLimiter::hit($key, 600);
            activity('security')->event('login.risk.'.$decision->level)
                ->withProperties(['level' => $decision->level, 'score' => $decision->score, 'signals' => $decision->signals])
                ->performedOn($user)->causedByAnonymous()->log('login.risk.'.$decision->level);
            if ($decision->level === 'high') {
                HighRiskAuthenticationDetected::dispatch($user, $decision->score, (string) $request->ip());
            }
        }

        if ($settings->get('authentication.risk_mode') !== 'enforce'
            || ! $settings->get('authentication.risk_step_up')) {
            return $user;
        }

        $hasFactor = $settings->get('authentication.mfa_enabled')
            && $user->hasEnabledTwoFactorAuthentication();
        if ($decision->level === 'high' || ! $hasFactor) {
            RateLimiter::hit($cooldown, (int) config('authentication-risk.cooldown_seconds.'.$decision->level, 60));
            activity('security')->event('login.risk.restricted')->performedOn($user)->causedByAnonymous()
                ->withProperties(['level' => $decision->level])->log('login.risk.restricted');
            // shortcut: unenrolled accounts use temporary denial and password recovery; add another verified factor before offering immediate access.
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        activity('security')->event('login.risk.challenge')->performedOn($user)->causedByAnonymous()
            ->withProperties(['level' => $decision->level, 'score' => $decision->score])->log('login.risk.challenge');
        $request->session()->put('risk.challenge', ['user_id' => $user->id, 'expires_at' => time() + 300]);

        return $user;
    }
}
