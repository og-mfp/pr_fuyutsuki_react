<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Security\LoginRiskEvaluator;
use App\Security\RiskCounterService;
use App\Security\TrustedDeviceService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationRiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_failures_across_ips_raise_account_risk_without_permanent_lockout(): void
    {
        $user = User::factory()->create();
        $counters = app(RiskCounterService::class);
        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.$i"])
                ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->assertSame(10, $counters->failures('account-failures', $user->email));
        $this->assertSame(1, $counters->failures('ip-failures', '192.0.2.1'));
        $this->assertSame(25, app(LoginRiskEvaluator::class)->evaluate(
            Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '198.51.100.9']), $user,
        )->score);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_spraying_tracks_distinct_accounts_and_shared_ip_allows_normal_logins(): void
    {
        $counters = app(RiskCounterService::class);
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login.store'), ['email' => "target$i@example.com", 'password' => 'wrong']);
        }
        $this->assertSame(5, $counters->failures('ip-targets', '127.0.0.1'));
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->post(route('login.store'), ['email' => $first->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($first);
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $second->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($second);
    }

    public function test_ip_request_limit_covers_different_unknown_accounts(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post(route('login.store'), ['email' => "unknown$i@example.com", 'password' => 'wrong'])
                ->assertRedirect();
        }
        $this->post(route('login.store'), ['email' => 'another@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_enforcement_denies_unenrolled_medium_risk_before_session(): void
    {
        $user = User::factory()->create();
        app(SettingsService::class)->update('authentication', ['risk_mode' => 'enforce', 'risk_step_up' => true], $user);
        $request = Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']);
        for ($i = 0; $i < 10; $i++) {
            app(RiskCounterService::class)->recordFailure($request);
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        app(SettingsService::class)->update('authentication', ['risk_mode' => 'monitor'], $user);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_password_reset_recovers_unenrolled_risky_login_once(): void
    {
        $user = User::factory()->create();
        app(SettingsService::class)->update('authentication', ['risk_mode' => 'enforce', 'risk_step_up' => true], $user);
        $request = Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']);
        for ($i = 0; $i < 10; $i++) {
            app(RiskCounterService::class)->recordFailure($request);
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => Password::broker()->createToken($user),
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password-123'])
            ->assertSessionMissing('risk.recovery');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_log', ['event' => 'login.risk.recovered']);
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_enforcement_keeps_fortify_two_factor_challenge_for_suspicious_login(): void
    {
        $user = User::factory()->withTwoFactor()->create();
        app(SettingsService::class)->update('authentication', ['risk_mode' => 'enforce', 'risk_step_up' => true], $user);
        $request = Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']);
        for ($i = 0; $i < 10; $i++) {
            app(RiskCounterService::class)->recordFailure($request);
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->withSession(['risk.challenge' => ['user_id' => $user->id, 'expires_at' => time() - 1]])
            ->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertContains('recovery-code-1', $user->fresh()->recoveryCodes());
    }

    public function test_completed_risk_challenge_creates_session_and_trusted_device(): void
    {
        $user = User::factory()->withTwoFactor()->create();
        app(SettingsService::class)->update('authentication', ['risk_mode' => 'enforce', 'risk_step_up' => true], $user);
        $request = Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']);
        for ($i = 0; $i < 10; $i++) {
            app(RiskCounterService::class)->recordFailure($request);
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1'])
            ->assertSessionMissing('risk.challenge');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $user->trustedDevices()->count());
        $this->assertDatabaseHas('activity_log', ['event' => 'login.risk.challenge-succeeded']);
    }

    public function test_high_risk_source_is_temporarily_restricted_even_with_mfa(): void
    {
        $user = User::factory()->withTwoFactor()->create();
        app(SettingsService::class)->update('authentication', ['risk_mode' => 'enforce', 'risk_step_up' => true], $user);
        $counters = app(RiskCounterService::class);
        for ($i = 0; $i < 5; $i++) {
            $counters->recordFailure(Request::create('/login', 'POST', ['email' => "target$i@example.com"], server: ['REMOTE_ADDR' => '127.0.0.1']));
        }
        for ($i = 0; $i < 10; $i++) {
            $counters->recordFailure(Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']));
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_log', ['event' => 'login.risk.restricted']);
    }

    public function test_risk_enforcement_still_applies_when_mfa_is_disabled(): void
    {
        $user = User::factory()->create();
        app(SettingsService::class)->update('authentication', [
            'mfa_enabled' => false,
            'risk_mode' => 'enforce',
            'risk_step_up' => true,
        ], $user);
        $request = Request::create('/login', 'POST', ['email' => $user->email], server: ['REMOTE_ADDR' => '127.0.0.1']);
        for ($i = 0; $i < 10; $i++) {
            app(RiskCounterService::class)->recordFailure($request);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('activity_log', ['event' => 'login.risk.restricted']);
    }

    public function test_device_revoke_is_scoped_to_owner_and_audited(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $device = $owner->trustedDevices()->create([
            'token_hash' => hash('sha256', 'secret-token'), 'name' => 'Browser',
            'first_seen_at' => now(), 'last_seen_at' => now(), 'expires_at' => now()->addDay(),
        ]);
        $this->authorizeSecurityChange($other, 'security.trusted-devices.destroy', target: (string) $device->id);
        $this->actingAs($other)->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('security.trusted-devices.destroy', $device))->assertNotFound();
        $this->authorizeSecurityChange($owner, 'security.trusted-devices.destroy', target: (string) $device->id);
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('security.trusted-devices.destroy', $device))->assertRedirect();
        $this->assertNotNull($device->fresh()->revoked_at);
        $this->assertDatabaseHas('activity_log', ['event' => 'trusted-device.revoked']);
    }

    public function test_unknown_accounts_share_the_same_generic_failure(): void
    {
        $known = User::factory()->create();
        $knownFailure = $this->post(route('login.store'), ['email' => $known->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');
        $unknownFailure = $this->post(route('login.store'), ['email' => 'absent@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email')->getSession()->get('errors')->first('email');
        $this->assertSame($knownFailure, $unknownFailure);
    }

    public function test_trusted_device_tokens_expire_revoke_and_reject_malformed_values(): void
    {
        $user = User::factory()->create();
        $token = bin2hex(random_bytes(32));
        $device = $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token), 'name' => 'Browser',
            'first_seen_at' => now(), 'last_seen_at' => now(), 'expires_at' => now()->addDay(),
        ]);
        $service = app(TrustedDeviceService::class);
        $request = Request::create('/login', 'POST', cookies: [TrustedDeviceService::COOKIE => $token]);
        $this->assertTrue($service->recognize($request, $user)->is($device));
        $this->assertNull($service->recognize(Request::create('/login', 'POST', cookies: [TrustedDeviceService::COOKIE => 'spoof']), $user));
        $device->update(['expires_at' => now()->subSecond()]);
        $this->assertNull($service->recognize($request, $user));
        $device->update(['expires_at' => now()->addDay(), 'revoked_at' => now()]);
        $this->assertNull($service->recognize($request, $user));
    }

    public function test_risk_threshold_boundaries_are_deterministic(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST', ['email' => $user->email]);
        foreach ([29 => 'low', 30 => 'medium', 69 => 'medium', 70 => 'high'] as $points => $level) {
            config(['authentication-risk.weights.unknown_device' => $points]);
            $decision = app(LoginRiskEvaluator::class)->evaluate($request, $user);
            $this->assertSame($points, $decision->score);
            $this->assertSame($level, $decision->level);
        }
        app(SettingsService::class)->update('authentication', ['trusted_devices_enabled' => false], $user);
        $this->assertSame(0, app(LoginRiskEvaluator::class)->evaluate($request, $user)->score);
    }

    public function test_daily_prune_removes_expired_trusted_devices(): void
    {
        $user = User::factory()->create();
        $device = $user->trustedDevices()->create([
            'token_hash' => hash('sha256', 'expired-token'), 'name' => 'Old browser',
            'first_seen_at' => now()->subDays(40), 'last_seen_at' => now()->subDays(40),
            'expires_at' => now()->subDay(),
        ]);
        $this->artisan('audit:prune')->assertSuccessful();
        $this->assertDatabaseMissing('trusted_devices', ['id' => $device->id]);
    }
}
