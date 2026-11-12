<?php

namespace App\Security;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RiskCounterService
{
    public function key(string $scope, string $value): string
    {
        return 'risk:'.$scope.':'.hash_hmac('sha256', $value, (string) config('app.key'));
    }

    public function identity(Request $request): string
    {
        return Str::lower(trim((string) $request->input('email')));
    }

    public function failures(string $scope, string $value): int
    {
        return RateLimiter::attempts($this->key($scope, $value));
    }

    public function recordFailure(Request $request): void
    {
        $identity = $this->identity($request);
        $ip = (string) $request->ip();
        $window = (int) app(SettingsService::class)->get('authentication.risk_window_minutes') * 60;

        RateLimiter::hit($this->key('account-failures', $identity), $window);
        RateLimiter::hit($this->key('ip-failures', $ip), $window);

        $marker = $this->key('target', $ip.'|'.$identity);
        if (Cache::add($marker, true, $window)) {
            RateLimiter::hit($this->key('ip-targets', $ip), $window);
        }
    }
}
