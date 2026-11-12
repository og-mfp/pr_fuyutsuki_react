<?php

namespace App\Http\Middleware;

use App\Services\AdminAccessGuard;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RejectFrozenSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $settings = app(SettingsService::class);
        if ($user && ($user->frozen_at || ($settings->get('authentication.authentication_lockdown')
            && ! app(AdminAccessGuard::class)->isOwner($user)))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $user->frozen_at
                ? 'This account is frozen. Contact an administrator.'
                : 'Authentication is temporarily locked.']);
        }
        if ($user && $request->routeIs('two-factor.enable', 'two-factor.confirm')
            && ! $settings->get('authentication.mfa_enabled')) {
            abort(403);
        }
        if ($user && $request->routeIs('two-factor.disable')
            && $settings->get('authentication.mfa_requirement') === 'required') {
            abort(403);
        }
        if ($user && $settings->get('authentication.mfa_enabled')
            && $settings->get('authentication.mfa_requirement') === 'required'
            && ! $user->hasEnabledTwoFactorAuthentication()
            && ! $request->routeIs('security.edit', 'password.confirm', 'password.confirmation',
                'password.confirm.store', 'logout', 'verification.*', 'two-factor.*', 'security-challenges.*')) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
