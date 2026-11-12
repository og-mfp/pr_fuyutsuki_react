<?php

namespace App\Security;

use Illuminate\Http\Request;
use Laravel\Fortify\LoginRateLimiter;

class SecureLoginRateLimiter extends LoginRateLimiter
{
    protected function throttleKey(Request $request): string
    {
        $counters = app(RiskCounterService::class);

        return $counters->key('fortify-login', $counters->identity($request).'|'.$request->ip());
    }
}
