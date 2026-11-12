<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ExpireRiskChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('two-factor.login.store') && is_array($challenge = $request->session()->get('risk.challenge'))
            && (($challenge['expires_at'] ?? 0) < time()
                || ($challenge['user_id'] ?? null) !== $request->session()->get('login.id'))) {
            $request->session()->forget(['login.id', 'login.remember', 'risk.challenge']);
            throw ValidationException::withMessages(['code' => 'The challenge has expired. Sign in again.']);
        }

        return $next($request);
    }
}
