<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            app()->setLocale($request->user()->language);
        }

        return $next($request);
    }
}
