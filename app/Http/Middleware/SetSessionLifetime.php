<?php

namespace App\Http\Middleware;

use App\Enums\InstallationStatus;
use App\Services\InstallationService;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->is('login')) {
            $status = app(InstallationService::class)->status();
            if (in_array($status, [InstallationStatus::NotInstalled, InstallationStatus::Unknown], true)) {
                return redirect()->route('setup.create');
            }
            abort_if($status === InstallationStatus::Inconsistent, 503, 'Installation status cannot be verified.');
        }

        if ($request->isMethod('GET') && $request->is('/', 'setup')
            && app(InstallationService::class)->status() === InstallationStatus::Unknown) {
            $request->attributes->set('setup_database_unavailable', true);
            $driver = config('session.driver');
            $cookie = config('session.cookie');
            config(['session.driver' => 'array', 'session.cookie' => $cookie.'_setup']);
            app('session')->forgetDrivers();

            try {
                return $next($request);
            } finally {
                config(['session.driver' => $driver, 'session.cookie' => $cookie]);
                app('session')->forgetDrivers();
            }
        }

        try {
            config(['session.lifetime' => app(SettingsService::class)->get('authentication.session_idle_timeout_minutes')]);
        } catch (Throwable) {
            if (! $request->attributes->get('setup_database_unavailable')) {
                abort(503, 'Authentication settings cannot be verified.');
            }
        }

        return $next($request);
    }
}
