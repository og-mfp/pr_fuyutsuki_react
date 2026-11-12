<?php

use App\Http\Middleware\ExpireRiskChallenge;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RejectFrozenSession;
use App\Http\Middleware\RejectRevokedMaintenanceSession;
use App\Http\Middleware\RequireConfiguredEmailVerification;
use App\Http\Middleware\RequirePasswordUnlessSecurityChallenge;
use App\Http\Middleware\RequireSecurityChallenge;
use App\Http\Middleware\RestrictConcurrentSessions;
use App\Http\Middleware\SetSessionLifetime;
use App\Http\Middleware\SetUserLocale;
use App\Services\DiagnosticEventService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->alias(['verified' => RequireConfiguredEmailVerification::class, 'password.confirm' => RequirePasswordUnlessSecurityChallenge::class]);

        $middleware->web(prepend: [SetSessionLifetime::class], append: [
            AuthenticateSession::class,
            RejectRevokedMaintenanceSession::class,
            ExpireRiskChallenge::class,
            RejectFrozenSession::class,
            RequireSecurityChallenge::class,
            RestrictConcurrentSessions::class,
            SetUserLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception): bool {
            if ($exception instanceof HttpExceptionInterface
                || $exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException) {
                return false;
            }

            $request = request();
            $status = $exception instanceof QueryException ? 500 : 500;
            $diagnostic = app(DiagnosticEventService::class)->record($request, $exception, $status);
            $request->attributes->set('diagnostic_error', $diagnostic);

            return false;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function ($response, Throwable $exception, Request $request) {
            if ($response->getStatusCode() === 403 && $request->is('admin/*')) {
                Log::warning('Admin RBAC denial.', [
                    'user_id' => $request->user()?->id,
                    'route' => $request->route()?->getName(),
                ]);
            }

            $status = $response->getStatusCode();
            $diagnostic = $request->attributes->get('diagnostic_error', []);
            if ($status >= 400 && is_array($diagnostic) && isset($diagnostic['reference_id'])) {
                $response->headers->set('X-Error-Reference', $diagnostic['reference_id']);
            }
            $isErrorPreview = $request->routeIs('admin.ui.error-preview');
            if (config('app.debug') && ! $isErrorPreview) {
                return $response;
            }

            if (! $isErrorPreview && ($status === 419 || $status === 422 || in_array($status, [401, 403], true))) {
                return $response;
            }

            if (! $request->is('api/*') && ! $request->expectsJson() && $status >= 400) {
                try {
                    $page = Inertia::render('errors/Index', [
                        'status' => $status,
                        'errorCode' => $diagnostic['error_code'] ?? null,
                        'referenceId' => $diagnostic['reference_id'] ?? null,
                    ])->toResponse($request);
                    $page->setStatusCode($status);
                    if (is_array($diagnostic) && isset($diagnostic['reference_id'])) {
                        $page->headers->set('X-Error-Reference', $diagnostic['reference_id']);
                    }

                    return $page;
                } catch (Throwable) {
                    return response()->view('errors.minimal', [], $status);
                }
            }

            return $response;
        });
    })->create();
