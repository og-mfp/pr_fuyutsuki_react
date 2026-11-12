<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RestrictConcurrentSessions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && config('session.driver') === 'database'
            && app(SettingsService::class)->get('authentication.concurrent_sessions')) {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        }

        return $next($request);
    }
}
