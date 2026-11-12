<?php

namespace Tests\Feature\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\SecurityChallenge;
use App\Models\User;
use App\Notifications\QueuedEmailVerification;
use App\Notifications\SecurityChangeNotification;
use App\Notifications\SecurityIncidentNotification;
use App\Services\SecurityChallengeService;
use App\Services\SettingsService;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        session()->start();
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
    }

    public function test_email_change_requires_the_emailed_code_and_notifies_both_addresses(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $oldEmail = $user->email;
        $payload = ['name' => $user->name, 'email' => 'new@example.com'];
        $this->actingAs($user)->patch(route('profile.update'), $payload)->assertForbidden();
        $id = $this->postJson(route('security-challenges.store'), ['action' => 'profile.update', 'payload' => $payload])->assertOk()->json('id');
        $this->assertSame($oldEmail, $user->fresh()->email);
        $code = null;
        Notification::assertSentOnDemand(SecurityChangeNotification::class, function ($notification) use (&$code) {
            $code = $notification->code;

            return $code !== null;
        });
        $this->postJson(route('security-challenges.confirm', $id), ['code' => $code])->assertNoContent();
        $this->patch(route('profile.update'), $payload)->assertRedirect(route('profile.edit'));
        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentOnDemand(QueuedEmailVerification::class);
        Notification::assertSentOnDemand(SecurityChangeNotification::class, fn ($notification, $channels, $notifiable) => $notification->code === null && $notifiable->routes['mail'] === $oldEmail);
        Notification::assertSentOnDemand(QueuedEmailVerification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new@example.com');
        $this->patch(route('profile.update'), ['name' => $user->name, 'email' => 'another@example.com'])->assertForbidden();
    }

    public function test_mfa_recovery_code_authorizes_only_one_bound_password_change(): void
    {
        Notification::fake();
        $user = User::factory()->withTwoFactor()->create();
        $payload = ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->actingAs($user);
        $id = $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        $this->postJson(route('security-challenges.confirm', $id), ['recovery_code' => 'recovery-code-1'])->assertNoContent();
        $this->put(route('user-password.update'), ['password' => 'changed-password', 'password_confirmation' => 'changed-password'])->assertForbidden();
        $this->put(route('user-password.update'), $payload)->assertRedirect();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotContains('recovery-code-1', $user->fresh()->recoveryCodes());
        $this->put(route('user-password.update'), $payload)->assertForbidden();
    }

    public function test_unauthorized_report_cancels_pending_challenge_and_requires_recovery(): void
    {
        Notification::fake();
        $admin = $this->privilegedUser();
        $user = User::factory()->create();
        $payload = ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->actingAs($user);
        $id = $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        $siblingId = $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        $sibling = SecurityChallenge::findOrFail($siblingId);
        $challenge = SecurityChallenge::findOrFail($id);
        $url = app(SecurityChallengeService::class)->reportUrl($challenge);
        $this->get($url)->assertOk();
        $this->assertNull($user->fresh()->security_recovery_required);
        $this->post($url)->assertRedirect(route('login'));
        $this->assertSame('reported', $challenge->fresh()->status);
        $this->assertSame('password', $user->fresh()->security_recovery_required);
        $this->assertSame(1, $user->fresh()->maintenance_session_version);
        $this->assertSame('canceled', $sibling->fresh()->status);
        Notification::assertSentTo($admin, SecurityIncidentNotification::class);
        Notification::assertSentOnDemand(SecurityIncidentNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $user->email);
        $this->post($url)->assertGone();
        $this->post(app(SecurityChallengeService::class)->reportUrl($sibling))->assertGone();
        $this->assertSame(1, $user->fresh()->maintenance_session_version);
        $response = $this->postJson(route('security-challenges.confirm', $siblingId), ['code' => '123456']);
        $this->assertNotSame(204, $response->getStatusCode());
        $this->put(route('user-password.update'), $payload)->assertForbidden();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reported_completed_email_change_requires_assisted_recovery(): void
    {
        $user = User::factory()->create();
        $payload = ['name' => $user->name, 'email' => 'new@example.com'];
        $this->authorizeSecurityChange($user, 'profile.update', $payload);
        $challenge = SecurityChallenge::latest()->firstOrFail();
        $this->patch(route('profile.update'), $payload)->assertRedirect();
        $this->post(app(SecurityChallengeService::class)->reportUrl($challenge))->assertRedirect();
        $this->assertSame('assisted', $user->fresh()->security_recovery_required);

        app(ResetUserPassword::class)->reset($user->fresh(), ['password' => 'different-password', 'password_confirmation' => 'different-password']);
        $this->assertSame('assisted', $user->fresh()->security_recovery_required);
    }

    public function test_expired_and_incorrect_codes_cannot_approve_a_change(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $payload = ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $id = $this->actingAs($user)->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        SecurityChallenge::findOrFail($id)->update(['expires_at' => now()->subMinute()]);
        $this->postJson(route('security-challenges.confirm', $id), ['code' => 'wrong'])->assertUnprocessable();
        $id = $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('security-challenges.confirm', $id), ['code' => 'wrong'])->assertUnprocessable();
        }
        $this->assertSame(5, SecurityChallenge::findOrFail($id)->attempts);
        $this->put(route('user-password.update'), $payload)->assertForbidden();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertUnprocessable();
    }

    public function test_failed_email_codes_share_a_user_limit_across_challenges_without_blocking_other_users(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $payload = ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $id = $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
            $this->postJson(route('security-challenges.confirm', $id), ['code' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertUnprocessable();
        $other = User::factory()->create();
        $otherId = $this->actingAs($other)
            ->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        $otherCode = null;
        Notification::assertSentOnDemand(SecurityChangeNotification::class, function ($notification, $channels, $notifiable) use ($other, &$otherCode) {
            if ($notifiable->routes['mail'] !== $other->email) {
                return false;
            }
            $otherCode = $notification->code;

            return true;
        });
        $this->postJson(route('security-challenges.confirm', $otherId), ['code' => $otherCode])->assertNoContent();
        $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => '192.0.2.4'])
            ->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertUnprocessable();
    }

    public function test_an_expired_challenge_can_still_be_reported_before_its_report_link_expires(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $payload = ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $id = $this->actingAs($user)->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])->assertOk()->json('id');
        $challenge = SecurityChallenge::findOrFail($id);
        $challenge->update(['expires_at' => now()->subMinute()]);
        $url = app(SecurityChallengeService::class)->reportUrl($challenge);
        $this->get($url)->assertOk();
        $this->post($url)->assertRedirect(route('login'));
        $this->assertSame('password', $user->fresh()->security_recovery_required);
    }

    public function test_successful_password_recovery_expires_previous_completed_report_links(): void
    {
        $user = User::factory()->create();
        $payload = ['name' => $user->name, 'email' => 'new@example.com'];
        $this->authorizeSecurityChange($user, 'profile.update', $payload);
        $challenge = SecurityChallenge::latest()->firstOrFail();
        $this->patch(route('profile.update'), $payload)->assertRedirect();
        $url = app(SecurityChallengeService::class)->reportUrl($challenge);
        app(ResetUserPassword::class)->reset($user->fresh(), ['password' => 'different-password', 'password_confirmation' => 'different-password']);
        $this->get($url)->assertGone();
    }

    public function test_security_mutations_reject_requests_without_a_fresh_challenge(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('two-factor.enable'))->assertForbidden();
        $this->post(route('passkey.store'), [])->assertForbidden();
        $this->delete(route('security.trusted-devices.destroy-all'))->assertForbidden();
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_password_rules_are_checked_before_email_or_mfa_challenge_starts(): void
    {
        Notification::fake();
        app(SettingsService::class)->update('authentication', ['password_min_length' => 14], $this->installationOwner());
        $payload = ['current_password' => 'password', 'password' => 'short123', 'password_confirmation' => 'short123'];

        foreach ([User::factory()->create(), User::factory()->withTwoFactor()->create()] as $user) {
            $this->actingAs($user)
                ->postJson(route('security-challenges.store'), ['action' => 'user-password.update', 'payload' => $payload])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('payload.password');
        }

        $this->assertDatabaseCount('security_challenges', 0);
        Notification::assertNothingSent();
    }

    public function test_password_preflight_uses_the_password_label_for_breach_errors(): void
    {
        app(SettingsService::class)->update('authentication', ['password_policy' => 'strong'], $this->installationOwner());
        $this->mock(UncompromisedVerifier::class)->shouldReceive('verify')->once()->andReturn(false);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('security-challenges.store'), [
            'action' => 'user-password.update',
            'payload' => [
                'current_password' => 'password',
                'password' => 'GoodPassword123!',
                'password_confirmation' => 'GoodPassword123!',
            ],
        ])->assertUnprocessable();

        $this->assertSame(
            'The given password has appeared in a data leak. Please choose a different password.',
            $response->json('errors')['payload.password'][0],
        );
    }

    public function test_two_factor_setup_uses_one_email_challenge_and_the_authenticator_code_to_finish(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
        Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]);
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user);

        $id = $this->postJson(route('security-challenges.store'), ['action' => 'two-factor.enable'])
            ->assertOk()->assertJsonPath('method', 'email')->json('id');
        $code = null;
        Notification::assertSentOnDemand(SecurityChangeNotification::class, function ($notification) use (&$code) {
            $code = $notification->code;

            return $code !== null;
        });
        $this->postJson(route('security-challenges.confirm', $id), ['code' => $code])->assertNoContent();
        $this->post(route('two-factor.enable'))->assertRedirect();
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);

        $challenge = SecurityChallenge::findOrFail($id);
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);
        $this->post(route('two-factor.confirm'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertSame('completed', $challenge->fresh()->status);
        Notification::assertSentOnDemandTimes(SecurityChangeNotification::class, 2);
        Notification::assertSentOnDemand(SecurityChangeNotification::class, fn ($notification) => $notification->code === null);
    }
}
