<?php

namespace App\Services;

use App\Models\SecurityChallenge;
use App\Models\User;
use App\Notifications\QueuedEmailVerification;
use App\Notifications\SecurityChangeNotification;
use App\Notifications\SecurityIncidentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Throwable;

class SecurityChallengeService
{
    public const ACTIONS = [
        'profile.update' => 'Email address changed',
        'user-password.update' => 'Password changed',
        'two-factor.enable' => 'Two-factor setup started',
        'two-factor.disable' => 'Two-factor authentication disabled',
        'two-factor.regenerate-recovery-codes' => 'Recovery codes regenerated',
        'two-factor.recovery-codes' => 'Recovery codes viewed',
        'passkey.store' => 'Passkey added',
        'passkey.destroy' => 'Passkey removed',
        'security.trusted-devices.destroy' => 'Trusted device revoked',
        'security.trusted-devices.destroy-all' => 'All trusted devices revoked',
    ];

    public const TARGET_ACTIONS = ['passkey.destroy', 'security.trusted-devices.destroy'];

    public function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    /** @param array<string, mixed> $payload */
    public function payloadHash(string $action, array $payload): ?string
    {
        $fields = match ($action) {
            'profile.update' => ['name', 'email'],
            'user-password.update' => ['current_password', 'password', 'password_confirmation'],
            default => [],
        };
        if (! $fields) {
            return null;
        }
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = (string) ($payload[$field] ?? '');
        }

        return $this->hash(json_encode($values, JSON_THROW_ON_ERROR));
    }

    public function reportUrl(SecurityChallenge $challenge): string
    {
        return route('security-challenges.report.show', [
            'id' => $challenge->id,
            'token' => $this->hash('report|'.$challenge->id.'|'.$challenge->user_id),
        ]);
    }

    public function emailCodeLocked(User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->emailFailureKey($user), 5);
    }

    private function emailFailureKey(User $user): string
    {
        return $this->hash('step-up-email-user|'.$user->id);
    }

    public function reportable(SecurityChallenge $challenge): bool
    {
        return in_array($challenge->status, ['pending', 'approved', 'completed'], true)
            && $challenge->report_expires_at->isFuture();
    }

    public function sendChallenge(SecurityChallenge $challenge, User $user, string $code): void
    {
        Notification::route('mail', $user->email)->notify(new SecurityChangeNotification(
            self::ACTIONS[$challenge->action], $this->reportUrl($challenge), $code,
        ));
    }

    public function confirm(SecurityChallenge $challenge, Request $request, ?string $code, ?string $recoveryCode): void
    {
        $failure = DB::transaction(function () use ($challenge, $request, $code, $recoveryCode) {
            $challenge = SecurityChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($challenge->user_id !== $user->id || $challenge->status !== 'pending'
                || $challenge->expires_at->isPast() || $challenge->attempts >= 5
                || ! hash_equals($challenge->session_hash, $this->hash($request->session()->getId()))
                || $user->security_recovery_required) {
                return 'This security challenge is no longer valid.';
            }

            $valid = false;
            if ($challenge->code_hash !== null && ! $user->hasEnabledTwoFactorAuthentication()) {
                if ($this->emailCodeLocked($user)) {
                    return 'Too many unsuccessful security challenges. Try again later.';
                }
                $valid = is_string($code) && hash_equals($challenge->code_hash, $this->hash($code));
                if (! $valid) {
                    RateLimiter::hit($this->emailFailureKey($user), 600);
                } else {
                    RateLimiter::clear($this->emailFailureKey($user));
                }
            } elseif ($challenge->code_hash === null && $user->hasEnabledTwoFactorAuthentication()) {
                $key = $this->hash('step-up-failures|'.$user->id);
                if (RateLimiter::tooManyAttempts($key, 5)) {
                    return 'Too many unsuccessful security challenges. Try again later.';
                }
                if ($code) {
                    $valid = app(TwoFactorAuthenticationProvider::class)->verify(
                        Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code,
                    );
                } elseif ($recoveryCode) {
                    foreach ($user->recoveryCodes() as $stored) {
                        if (hash_equals($stored, $recoveryCode)) {
                            $user->replaceRecoveryCode($stored);
                            $valid = true;
                            break;
                        }
                    }
                }
                if (! $valid) {
                    RateLimiter::hit($key, 600);
                } else {
                    RateLimiter::clear($key);
                }
            }
            if (! $valid) {
                $challenge->increment('attempts');

                return 'The security code is invalid.';
            }
            $challenge->update(['status' => 'approved', 'code_hash' => null]);

            return null;
        });
        if ($failure !== null) {
            throw ValidationException::withMessages(['code' => $failure]);
        }
        $request->session()->put('security_challenge', $challenge->id);
    }

    public function completed(SecurityChallenge $challenge, User $user, string $previousEmail): void
    {
        $action = $challenge->action === 'two-factor.enable'
            ? 'Two-factor authentication enabled'
            : self::ACTIONS[$challenge->action];
        try {
            Notification::route('mail', $previousEmail)->notify(new SecurityChangeNotification(
                $action, $this->reportUrl($challenge),
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
        if ($challenge->action === 'profile.update' && $previousEmail !== $user->email) {
            try {
                $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes((int) config('auth.verification.expire', 60)), [
                    'id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification()),
                ]);
                Notification::route('mail', $user->email)->notify(new QueuedEmailVerification($url));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function report(SecurityChallenge $challenge): void
    {
        $user = DB::transaction(function () use ($challenge) {
            $challenge = SecurityChallenge::whereKey($challenge->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->reportable($challenge), 410);
            $user = User::whereKey($challenge->user_id)->lockForUpdate()->firstOrFail();
            $assisted = str_starts_with($challenge->action, 'two-factor.')
                || str_starts_with($challenge->action, 'passkey.')
                || ($challenge->action === 'profile.update' && $challenge->status === 'completed')
                || app(AdminAccessGuard::class)->isOwner($user)
                || $user->security_recovery_required === 'assisted';
            $challenge->update(['status' => 'reported', 'code_hash' => null]);
            SecurityChallenge::where('user_id', $user->id)->whereIn('status', ['pending', 'approved'])
                ->update(['status' => 'canceled', 'code_hash' => null]);
            $user->forceFill([
                'security_recovery_required' => $assisted ? 'assisted' : 'password',
                'maintenance_session_version' => $user->maintenance_session_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            $user->trustedDevices()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            activity('security')->event('security-change.reported-high-risk')->performedOn($user)->causedByAnonymous()
                ->withProperties(['action' => $challenge->action, 'recovery' => $user->security_recovery_required])
                ->log('security-change.reported-high-risk');

            return $user;
        });

        try {
            Notification::route('mail', $challenge->notification_email)->notify(new SecurityIncidentNotification($user->id, self::ACTIONS[$challenge->action]));
        } catch (Throwable $exception) {
            report($exception);
        }
        try {
            $admins = User::whereKeyNot($user->id)->whereNull('frozen_at')
                ->whereHas('groups.roles.permissions', fn ($query) => $query->where('slug', 'admin.access'))->get();
            if ($admins->isEmpty()) {
                $ownerId = DB::table('installation_metadata')->where('id', 1)->value('owner_id');
                $admins = User::whereKeyNot($user->id)->whereKey($ownerId)->whereNull('frozen_at')->get();
            }
            Notification::send($admins, new SecurityIncidentNotification($user->id, self::ACTIONS[$challenge->action], true));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
