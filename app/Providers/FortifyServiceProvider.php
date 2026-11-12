<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\MaintenanceWindow;
use App\Security\AssessPasswordLoginRisk;
use App\Security\RecordLoginRequest;
use App\Security\RiskCounterService;
use App\Security\SecureLoginRateLimiter;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginRateLimiter::class, SecureLoginRateLimiter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->app->beforeResolving('auth.password', function () {
            config(['auth.passwords.users.expire' => app(SettingsService::class)->get('authentication.password_reset_expiration_minutes')]);
        });
        Fortify::authenticateThrough(fn () => array_filter([
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
            RecordLoginRequest::class,
            AssessPasswordLoginRisk::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            $settings = app(SettingsService::class);

            return Inertia::render('auth/login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'status' => $request->session()->get('status'),
                'lockdownReason' => $settings->get('authentication.authentication_lockdown')
                    ? ($settings->get('authentication.lockdown_reason') ?: 'Authentication is temporarily locked.')
                    : null,
                'maintenanceAnnouncements' => rescue(fn () => MaintenanceWindow::where('status', 'scheduled')->where('published', true)->whereBetween('scheduled_start_at', [now(), now()->addHours((int) $settings->get('health_maintenance.announcement_lead_hours'))])->orderBy('scheduled_start_at')->get(['id', 'title', 'public_message', 'scheduled_start_at', 'scheduled_end_at']), collect(), report: false),
                'timezone' => config('app.timezone'),
            ]);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => app(SettingsService::class)->get('users.registration_mode') === 'public'
            && ! app(SettingsService::class)->get('authentication.authentication_lockdown')
            ? Inertia::render('auth/register', ['passwordRules' => Password::defaults()->toPasswordRulesString()])
            : abort(404));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('password-reset-link', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('password-reset-submit', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(app(RiskCounterService::class)->key('two-factor', (string) $request->session()->get('login.id')));
        });

        RateLimiter::for('login', function (Request $request) {
            $counters = app(RiskCounterService::class);
            $identity = $counters->identity($request);
            $ip = (string) $request->ip();
            $settings = app(SettingsService::class);

            return [
                Limit::perMinute((int) $settings->get('authentication.login_max_attempts'),
                    (int) $settings->get('authentication.account_lockout_minutes'))
                    ->by($counters->key('login-identity-ip', $identity.'|'.$ip)),
                Limit::perMinute((int) $settings->get('authentication.ip_login_threshold'))
                    ->by($counters->key('login-ip', $ip)),
            ];
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credential = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                app(RiskCounterService::class)->key('passkey', (is_string($credential) ? $credential : $request->session()->getId()).'|'.$request->ip()),
            );
        });
    }
}
