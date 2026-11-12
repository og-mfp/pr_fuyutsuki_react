<?php

namespace App\Providers;

use App\Events\HighRiskAuthenticationDetected;
use App\Listeners\AuditAuthentication;
use App\Listeners\GuardRiskChallenge;
use App\Listeners\MarkRiskRecovery;
use App\Listeners\RecordAuthenticationRisk;
use App\Listeners\RejectFrozenLogin;
use App\Listeners\SendHighRiskAuthenticationAlert;
use App\Models\User;
use App\Services\AdminAccessGuard;
use App\Services\MaintenanceManagementService;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        if ($proxies = config('authentication-risk.trusted_proxies')) {
            TrustProxies::at($proxies);
        }
        app()->resolving('mail.manager', function () {
            $settings = app(SettingsService::class);
            if ($settings->get('smtp.enabled')) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.url' => null,
                    'mail.mailers.smtp.host' => $settings->get('smtp.host'),
                    'mail.mailers.smtp.port' => $settings->get('smtp.port'),
                    'mail.mailers.smtp.scheme' => $settings->get('smtp.scheme'),
                    'mail.mailers.smtp.username' => $settings->get('smtp.username') ?: null,
                    'mail.mailers.smtp.password' => $settings->get('smtp.password'),
                    'mail.from.address' => $settings->get('smtp.from_address'),
                ]);
            }
        });
        app()->resolving('mailer', function ($mailer) {
            $settings = app(SettingsService::class);
            $mailer->alwaysFrom(config('mail.from.address'), $settings->get('notifications.sender_name'));
            $replyTo = $settings->get('notifications.reply_to');
            if ($replyTo) {
                $mailer->alwaysReplyTo($replyTo);
            }
        });
        Gate::define('manage-system-settings', fn (User $user) => ! $user->frozen_at && app(AdminAccessGuard::class)->isOwner($user));
        Gate::define('access-admin', fn (User $user) => $user->hasPermission('admin.access'));
        Gate::define('audit.view', fn (User $user) => $user->hasPermission('audit.view'));
        Gate::define('audit.export', fn (User $user) => $user->hasPermission('audit.export'));
        Gate::define('audit.manage-retention', fn (User $user) => $user->hasPermission('audit.manage-retention'));
        Event::listen(Login::class, RejectFrozenLogin::class);
        Event::listen(Login::class, function (Login $event): void {
            if (request()->hasSession() && $event->user instanceof User) {
                request()->session()->put('auth.maintenance_session_version', (int) $event->user->maintenance_session_version);
            }
        });
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command === 'up' && app()->maintenanceMode()->active()) {
                app(MaintenanceManagementService::class)->invalidateLoginSessions();
            }
        });
        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            $nativeMaintenanceCommand = in_array($event->command, ['down', 'up'], true);
            if (! $nativeMaintenanceCommand && ! str_starts_with($event->command, 'maintenance:')) {
                return;
            }

            try {
                $options = $event->input->getOptions();
                $operator = $options['operator'] ?? null;
                $operator = is_string($operator) && trim($operator) !== '' ? trim($operator) : null;
                $osUser = null;
                if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
                    $account = posix_getpwuid(posix_geteuid());
                    $osUser = is_array($account) ? $account['name'] : null;
                }
                $sshConnection = getenv('SSH_CONNECTION') ?: getenv('SSH_CLIENT');
                $sourceIp = is_string($sshConnection) ? strtok(trim($sshConnection), ' ') : false;
                $sourceIp = is_string($sourceIp) && filter_var($sourceIp, FILTER_VALIDATE_IP) ? $sourceIp : null;
                $context = [
                    'command' => $event->command,
                    'exit_code' => $event->exitCode,
                    'source' => 'cli',
                    'operator_identifier' => $operator,
                    'os_user' => $osUser,
                    'source_ip' => $sourceIp,
                ];

                if ($nativeMaintenanceCommand) {
                    app(MaintenanceManagementService::class)->recordNativeCommand($event->command, $event->exitCode, $context);
                } else {
                    Log::notice('Maintenance Artisan command finished', $context);
                }
            } catch (\Throwable $exception) {
                error_log('Maintenance Artisan command logging failed: '.$exception->getMessage());
            }
        });
        Event::listen(Login::class, RecordAuthenticationRisk::class);
        Event::listen(Failed::class, RecordAuthenticationRisk::class);
        Event::listen(TwoFactorAuthenticationFailed::class, GuardRiskChallenge::class);
        Event::listen(ValidTwoFactorAuthenticationCodeProvided::class, GuardRiskChallenge::class);
        Event::listen(PasswordReset::class, MarkRiskRecovery::class);
        Event::listen(HighRiskAuthenticationDetected::class, SendHighRiskAuthenticationAlert::class);
        foreach ([Login::class, Logout::class, Failed::class, Lockout::class, PasswordReset::class,
            TwoFactorAuthenticationEnabled::class, TwoFactorAuthenticationConfirmed::class,
            TwoFactorAuthenticationDisabled::class] as $event) {
            Event::listen($event, AuditAuthentication::class);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function (): Password {
            $settings = app(SettingsService::class);
            $rule = Password::min((int) $settings->get('authentication.password_min_length'));

            return $settings->get('authentication.password_policy') === 'strong'
                ? $rule->mixedCase()->letters()->numbers()->symbols()->uncompromised()
                : $rule;
        });
    }
}
