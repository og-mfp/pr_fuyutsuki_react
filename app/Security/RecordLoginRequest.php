<?php

namespace App\Security;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RecordLoginRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        RateLimiter::hit(app(RiskCounterService::class)->key('ip-volume', (string) $request->ip()),
            (int) app(SettingsService::class)->get('authentication.risk_window_minutes') * 60);

        return $next($request);
    }
}
