<?php

namespace App\Http\Middleware;

use App\Models\SecurityChallenge;
use App\Services\SecurityChallengeService;
use Closure;
use Illuminate\Auth\Middleware\RequirePassword;

class RequirePasswordUnlessSecurityChallenge extends RequirePassword
{
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null)
    {
        if ($request->attributes->get('security_step_up') === true) {
            return $next($request);
        }

        if ($request->routeIs('passkey.registration-options') && $request->user()) {
            $challenge = SecurityChallenge::whereKey($request->session()->get('security_challenge'))->first();
            if ($challenge && $challenge->user_id === $request->user()->id && $challenge->action === 'passkey.store'
                && $challenge->status === 'approved' && $challenge->expires_at->isFuture()
                && ! $request->user()->security_recovery_required
                && hash_equals($challenge->session_hash, app(SecurityChallengeService::class)->hash($request->session()->getId()))) {
                return $next($request);
            }
        }
        $setup = $request->session()->get('security.two_factor_setup');
        if ($request->routeIs('two-factor.qr-code', 'two-factor.secret-key') && is_array($setup)
            && $request->user() && $setup['user_id'] === $request->user()->id && $setup['expires_at'] >= time()
            && ! $request->user()->hasEnabledTwoFactorAuthentication() && ! $request->user()->security_recovery_required) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute, $passwordTimeoutSeconds);
    }
}
