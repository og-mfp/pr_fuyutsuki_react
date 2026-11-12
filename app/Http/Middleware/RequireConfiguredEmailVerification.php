<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireConfiguredEmailVerification
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(SettingsService::class)->get('users.email_confirmation_required')
            && $request->user() && ! $request->user()->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
