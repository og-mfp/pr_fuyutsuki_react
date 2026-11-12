<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DiagEvent;
use App\Models\User;
use App\Services\SettingsRegistry;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Tests\TestCase;

class ApplicationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_bulk_load_once_and_reuse_values_within_a_request(): void
    {
        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains(strtolower($query->sql), 'from "app_settings"')) {
                $queries++;
            }
        });

        $settings = app(SettingsService::class);
        $general = $settings->category('general');
        $this->assertSame($general['display_name'], $settings->get('general.display_name'));
        $this->assertSame(1, $queries);
    }

    public function test_security_setting_reads_ignore_stale_persistent_cache(): void
    {
        AppSetting::updateOrCreate(['key' => 'authentication.authentication_lockdown'], ['value' => true]);
        Cache::put('app-setting:authentication.authentication_lockdown', false);

        $this->assertTrue(app(SettingsService::class)->get('authentication.authentication_lockdown'));
    }

    public function test_session_settings_failure_returns_service_unavailable(): void
    {
        Route::middleware('web')->get('/test-settings-failure', fn () => 'unreachable');
        $this->mock(SettingsService::class)->shouldReceive('get')->andReturnUsing(function (string $key) {
            if ($key === 'authentication.session_idle_timeout_minutes') {
                throw new \RuntimeException('settings unavailable');
            }

            return app(SettingsRegistry::class)->definition($key)['default'];
        });

        $this->get('/test-settings-failure')->assertServiceUnavailable();
    }

    public function test_unexpected_production_errors_render_a_generic_inertia_page(): void
    {
        config(['app.debug' => false]);
        Route::get('/test-failing-request', fn () => throw new \RuntimeException('private exception detail'));
        Log::spy();

        $response = $this->get('/test-failing-request')
            ->assertStatus(500)
            ->assertHeader('X-Error-Reference')
            ->assertInertia(fn ($page) => $page->component('errors/Index')->where('status', 500)->where('errorCode', 'FUY-SYS-000')->where('referenceId', fn ($id) => is_string($id) && Str::isUuid($id)))
            ->assertDontSee('private exception detail');

        $event = DiagEvent::query()->firstOrFail();
        $this->assertSame('FUY-SYS-000', $event->error_code);
        $this->assertSame(500, $event->http_status);
        $this->assertSame('An unexpected server error occurred.', $event->summary);
        $this->assertSame($event->reference_id, $response->headers->get('X-Error-Reference'));
        Log::shouldHaveReceived('error')->once()->with('Unhandled application exception.', \Mockery::on(
            fn (array $context) => $context['reference_id'] === $event->reference_id && $context['error_code'] === $event->error_code,
        ));
    }

    public function test_error_reporting_does_not_record_validation_or_csrf_workflows(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->post('/test-validation-error', fn (Request $request) => $request->validate(['name' => ['required']]));
        $this->from('/')->post('/test-validation-error')->assertSessionHasErrors('name');
        $this->assertDatabaseCount('diag_event', 0);
    }

    public function test_unexpected_errors_keep_local_debug_details(): void
    {
        config(['app.debug' => true]);
        Route::get('/test-failing-request', fn () => throw new \RuntimeException('local exception detail'));

        $this->get('/test-failing-request')
            ->assertStatus(500)
            ->assertSee('local exception detail');
    }

    public function test_local_error_preview_renders_the_custom_419_page(): void
    {
        config(['app.debug' => true]);
        Route::get('/test-local-error-preview', fn () => abort(419))->name('admin.ui.error-preview');

        $this->get('/test-local-error-preview')
            ->assertStatus(419)
            ->assertInertia(fn ($page) => $page->component('errors/Index')->where('status', 419));
    }

    public function test_failed_test_email_is_logged_and_shows_a_generic_flash_message(): void
    {
        $this->actingAs($this->installationOwner());
        $exception = new \RuntimeException('SMTP password or host detail');
        Mail::shouldReceive('raw')->once()->andThrow($exception);
        Log::spy();

        $this->post(route('admin.configuration.test-notification'), ['email' => 'recipient@example.com'])
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'Test email could not be sent. Check the mail configuration and try again.')
            ->assertDontSee('SMTP password or host detail');

        Log::shouldHaveReceived('error')->once()->with('Test email delivery failed.', \Mockery::on(
            fn (array $context) => $context['exception'] === $exception,
        ));
    }

    public function test_smtp_settings_are_protected_and_applied_to_mail(): void
    {
        $owner = $this->installationOwner();
        $this->actingAs($owner)->put(route('admin.configuration.update', 'smtp'), [
            'enabled' => '1', 'host' => 'smtp.example.com', 'port' => 465, 'scheme' => 'smtps',
            'username' => 'mailer', 'password' => 'secret-value', 'from_address' => 'mail@example.com',
        ])->assertSessionHasErrors('current_password');
        $this->put(route('admin.configuration.update', 'smtp'), [
            'enabled' => '1', 'host' => 'smtp.example.com', 'port' => 465, 'scheme' => 'smtps',
            'username' => 'mailer', 'password' => 'secret-value', 'from_address' => 'mail@example.com', 'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->assertNotSame('secret-value', AppSetting::where('key', 'smtp.password')->firstOrFail()->value);
        $this->assertSame('secret-value', app(SettingsService::class)->get('smtp.password'));
        $this->assertStringNotContainsString('secret-value', DB::table('activity_log')->where('log_name', 'configuration')->get()->toJson());
        $this->get(route('admin.configuration.index'))->assertOk()->assertDontSee('secret-value');
        app('mail.manager');
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('secret-value', config('mail.mailers.smtp.password'));
        $this->put(route('admin.configuration.update', 'smtp'), ['port' => 70000, 'current_password' => 'password'])->assertSessionHasErrors('port');
        $this->put(route('admin.configuration.update', 'smtp'), ['clear_password' => '1', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('', app(SettingsService::class)->get('smtp.password'));
    }

    public function test_configuration_requires_protected_role_even_with_admin_access(): void
    {
        $this->get(route('admin.configuration.index'))->assertRedirect(route('login'));
        $normal = User::factory()->create();
        $this->actingAs($normal)->get(route('admin.configuration.index'))->assertForbidden();
        $normal->groups()->attach($this->privilegedUser()->groups()->firstOrFail());
        $this->get(route('admin.configuration.index'))->assertForbidden();
        $super = $this->installationOwner();
        $this->actingAs($super)->get(route('admin.configuration.index'))->assertOk();
    }

    public function test_registered_values_validate_persist_audit_and_invalidate_cache(): void
    {
        $super = $this->installationOwner();
        $settings = app(SettingsService::class);
        $this->assertSame(config('app.name'), $settings->get('general.display_name'));
        $this->actingAs($super)->put(route('admin.configuration.update', 'general'), ['display_name' => 'Fuyutsuki'])->assertRedirect();
        $this->assertSame('Fuyutsuki', $settings->get('general.display_name'));
        $this->assertDatabaseHas('app_settings', ['key' => 'general.display_name', 'updated_by' => $super->id]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'configuration', 'description' => 'setting changed']);
        $this->put(route('admin.configuration.update', 'general'), ['display_name' => str_repeat('x', 101)])->assertSessionHasErrors('display_name');
        $this->put(route('admin.configuration.update', 'general'), ['app_key' => 'bad'])->assertSessionHasErrors('app_key');
        $this->assertSame('Fuyutsuki', $settings->get('general.display_name'));
    }

    public function test_localization_preferences_are_per_user_and_validate_timezones(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('localization.edit'))
            ->assertInertia(fn ($page) => $page->where('localization.language', 'en')->has('timezones'));

        $this->patch(route('localization.update'), [
            'language' => 'id', 'timezone' => 'Asia/Jakarta', 'date_format' => 'd/m/Y',
            'time_format' => 'H:i', 'number_format' => '1.234,56', 'currency' => 'IDR',
            'currency_display' => 'code',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Asia/Jakarta', $user->fresh()->timezone);
        $other = User::factory()->create();
        $this->assertSame('UTC', $other->timezone);
        $this->patch(route('localization.update'), ['timezone' => 'Not/A_Timezone'])
            ->assertSessionHasErrors('timezone');
    }

    public function test_registration_role_cannot_grant_privileges(): void
    {
        $super = $this->installationOwner();
        $this->actingAs($super)->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.configuration.update', 'users'), ['default_role' => 'owner', 'current_password' => 'password'])
            ->assertSessionHasErrors('default_role');
        $this->assertSame('std-usr', app(SettingsService::class)->get('users.default_role'));
    }

    public function test_blank_optional_setting_saves_empty_default(): void
    {
        $super = $this->installationOwner();
        $settings = app(SettingsService::class);

        $this->actingAs($super)->put(route('admin.configuration.update', 'general'), ['description' => ''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('', $settings->get('general.description'));
        $this->actingAs($super)->put(route('admin.configuration.update', 'general'), ['description' => 'Existing description'])->assertRedirect();
        $this->put(route('admin.configuration.update', 'general'), ['description' => ''])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('', AppSetting::where('key', 'general.description')->firstOrFail()->value);
        $this->assertSame('', $settings->get('general.description'));
    }

    public function test_sensitive_updates_require_password_every_time_and_initialization_is_idempotent(): void
    {
        $super = $this->installationOwner();
        $this->actingAs($super)->put(route('admin.configuration.update', 'users'), ['registration_mode' => 'administrator_created'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('admin.configuration.update', 'users'), ['registration_mode' => 'administrator_created', 'current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('admin.configuration.update', 'users'), ['registration_mode' => 'administrator_created', 'current_password' => 'password'])
            ->assertRedirect();
        $this->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.configuration.update', 'users'), ['registration_mode' => 'public'])
            ->assertSessionHasErrors('current_password');
        $settings = app(SettingsService::class);
        $settings->initialize();
        $count = AppSetting::count();
        $settings->initialize();
        $this->assertSame($count, AppSetting::count());
        $this->assertSame('administrator_created', $settings->get('users.registration_mode'));
    }

    public function test_authentication_policies_are_validated_and_enforced(): void
    {
        $super = $this->installationOwner();
        $normal = User::factory()->unverified()->create();
        $this->actingAs($super)->put(route('admin.configuration.update', 'authentication'), [
            'mfa_enabled' => '0', 'mfa_requirement' => 'required', 'current_password' => 'password',
        ])->assertSessionHasErrors('mfa_requirement');

        $this->put(route('admin.configuration.update', 'authentication'), [
            'password_min_length' => 14, 'password_reset_expiration_minutes' => 15,
            'login_max_attempts' => 2, 'account_lockout_minutes' => 10,
            'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $settings = app(SettingsService::class);
        $this->assertSame(14, $settings->get('authentication.password_min_length'));
        $this->actingAs($super)->put(route('admin.configuration.update', 'users'), [
            'email_confirmation_required' => '1', 'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($settings->get('users.email_confirmation_required'));
        Password::broker();
        $this->assertSame(15, config('auth.passwords.users.expire'));
        $limits = RateLimiter::limiter('login')(Request::create('/login', 'POST', ['email' => 'a@example.com']));
        $this->assertSame(2, $limits[0]->maxAttempts);
        $this->assertSame(600, $limits[0]->decaySeconds);
        $this->assertSame(30, $limits[1]->maxAttempts);
        $this->assertTrue(Validator::make(['password' => 'shortpassword'], ['password' => PasswordRule::default()])->fails());
        $this->actingAs($normal)->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $this->actingAs($super)->put(route('admin.configuration.update', 'authentication'), [
            'mfa_requirement' => 'required',
            'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->actingAs($super)->put(route('admin.configuration.update', 'users'), [
            'email_confirmation_required' => '0', 'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->actingAs($normal)->get(route('dashboard'))->assertRedirect(route('security.edit'));
    }

    public function test_emergency_actions_spare_owner(): void
    {
        $super = $this->installationOwner();
        $normal = User::factory()->create(['remember_token' => 'old-token']);
        $normal->groups()->attach($this->privilegedUser()->groups()->firstOrFail());
        $this->actingAs($normal)->post(route('admin.configuration.emergency'), [
            'action' => 'lockdown', 'reason' => 'Security incident', 'current_password' => 'password',
        ])->assertForbidden();
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            ['id' => 'super-session', 'user_id' => $super->id, 'payload' => 'data', 'last_activity' => time()],
            ['id' => 'normal-session', 'user_id' => $normal->id, 'payload' => 'data', 'last_activity' => time()],
        ]);
        $this->actingAs($super)->post(route('admin.configuration.emergency'), [
            'action' => 'logout', 'current_password' => 'wrong',
        ])->assertRedirect();
        $this->assertDatabaseHas('sessions', ['id' => 'normal-session']);
        $this->post(route('admin.configuration.emergency'), [
            'action' => 'logout', 'current_password' => 'password',
        ])->assertRedirect();
        $this->assertDatabaseHas('sessions', ['id' => 'super-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'normal-session']);
        $this->assertNull($normal->fresh()->remember_token);

        DB::table('sessions')->insert([
            'id' => 'normal-session-again', 'user_id' => $normal->id, 'payload' => 'data', 'last_activity' => time(),
        ]);
        $normal->forceFill(['remember_token' => 'another-token'])->save();
        $this->post(route('admin.configuration.emergency'), [
            'action' => 'lockdown', 'current_password' => 'password',
        ])->assertRedirect();
        $this->assertFalse(app(SettingsService::class)->get('authentication.authentication_lockdown'));
        $this->assertDatabaseHas('sessions', ['id' => 'normal-session-again']);
        $this->post(route('admin.configuration.emergency'), [
            'action' => 'lockdown', 'reason' => 'Security incident', 'current_password' => 'password',
        ])->assertRedirect();
        $this->assertTrue(app(SettingsService::class)->get('authentication.authentication_lockdown'));
        $this->assertSame('Security incident', app(SettingsService::class)->get('authentication.lockdown_reason'));
        $this->assertDatabaseHas('sessions', ['id' => 'super-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'normal-session-again']);
        $this->assertNull($normal->fresh()->remember_token);
        $this->actingAs($normal)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => $normal->email, 'password' => 'password']);
        $this->assertGuest();
        $this->get(route('login'))->assertInertia(fn ($page) => $page->where('lockdownReason', 'Security incident'));
        $this->actingAs($super)->get(route('dashboard'))->assertOk();
        $this->post(route('admin.configuration.emergency'), [
            'action' => 'unlock', 'current_password' => 'password',
        ])->assertRedirect();
        $this->assertFalse(app(SettingsService::class)->get('authentication.authentication_lockdown'));
        $this->assertSame('', app(SettingsService::class)->get('authentication.lockdown_reason'));
        $this->post(route('logout'));
        $this->get(route('login'))->assertInertia(fn ($page) => $page->where('lockdownReason', null));
    }

    public function test_mfa_can_be_disabled_without_deleting_user_secrets_and_reauthentication_can_expire(): void
    {
        $super = $this->installationOwner();
        $enrolled = User::factory()->withTwoFactor()->create();
        $this->actingAs($super)->put(route('admin.configuration.update', 'authentication'), [
            'mfa_enabled' => '0', 'reauthentication_minutes' => 15, 'current_password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->withSession(['auth.password_confirmed_at' => time()])
            ->put(route('admin.configuration.update', 'authentication'), ['login_max_attempts' => 3])
            ->assertSessionHasNoErrors();
        $this->assertSame(3, app(SettingsService::class)->get('authentication.login_max_attempts'));
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $enrolled->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($enrolled);
        $this->assertNotNull($enrolled->fresh()->two_factor_secret);
    }
}
