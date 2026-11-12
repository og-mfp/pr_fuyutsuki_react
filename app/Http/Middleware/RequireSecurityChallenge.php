<?php

namespace App\Http\Middleware;

use App\Models\SecurityChallenge;
use App\Models\User;
use App\Services\SecurityChallengeService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RequireSecurityChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        $service = app(SecurityChallengeService::class);
        $action = $request->route()?->getName();
        $user = $request->user();
        if ($action === 'two-factor.confirm') {
            return $this->confirmTwoFactorSetup($request, $next, $service, $user);
        }
        if (! $user || ! is_string($action) || ! isset(SecurityChallengeService::ACTIONS[$action])) {
            return $next($request);
        }
        if ($action === 'profile.update' && $request->input('email') === $user->email) {
            return $next($request);
        }
        abort_if($user->security_recovery_required !== null, 403);
        $parameter = match ($action) {
            'passkey.destroy' => $request->route('passkey'),
            'security.trusted-devices.destroy' => $request->route('device'),
            default => null,
        };
        $target = $parameter instanceof Model ? (string) $parameter->getKey() : ($parameter === null ? null : (string) $parameter);
        $payloadHash = $service->payloadHash($action, $request->all());
        $previousEmail = $user->email;
        $completed = false;
        $response = DB::transaction(function () use ($request, $next, $service, $user, $action, $target, $payloadHash, &$challenge, &$completed, &$previousEmail) {
            $challenge = SecurityChallenge::whereKey($request->session()->get('security_challenge'))->lockForUpdate()->first();
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->refresh();
            $previousEmail = $user->email;
            abort_unless($challenge && $challenge->user_id === $user->id && $challenge->status === 'approved'
                && ! $user->security_recovery_required
                && $challenge->expires_at->isFuture() && $challenge->action === $action
                && $challenge->target === $target
                && hash_equals($challenge->session_hash, $service->hash($request->session()->getId()))
                && ($payloadHash === null || ($challenge->payload_hash !== null && hash_equals($challenge->payload_hash, $payloadHash))), 403);
            $challenge->update(['status' => 'consumed']);
            $request->session()->forget('security_challenge');
            $request->attributes->set('security_step_up', true);
            $response = $next($request);
            $completed = $response->getStatusCode() < 400 && ! $request->session()->has('errors');
            if ($completed) {
                if ($action === 'two-factor.enable') {
                    $user->refresh();
                    if ($user->hasEnabledTwoFactorAuthentication()) {
                        $challenge->update(['status' => 'completed']);
                    } else {
                        $challenge->update(['status' => 'approved']);
                        $request->session()->put('security.two_factor_setup', [
                            'user_id' => $user->id,
                            'challenge_id' => $challenge->id,
                            'expires_at' => $challenge->expires_at->timestamp,
                        ]);
                        $completed = false;
                    }
                } else {
                    $challenge->update(['status' => 'completed']);
                }
                if ($action === 'two-factor.disable') {
                    $request->session()->forget('security.two_factor_setup');
                }
            }

            return $response;
        });
        if ($completed && $action !== 'two-factor.recovery-codes') {
            $service->completed($challenge, $user->fresh(), $previousEmail);
        }

        return $response;
    }

    private function confirmTwoFactorSetup(Request $request, Closure $next, SecurityChallengeService $service, ?User $user): Response
    {
        $setup = $request->session()->get('security.two_factor_setup');
        abort_unless($user && is_array($setup) && ($setup['user_id'] ?? null) === $user->id
            && ($setup['expires_at'] ?? 0) >= time() && ! $user->security_recovery_required, 403);

        $key = $service->hash('two-factor-setup-failures|'.$user->id);
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429);
        $completed = false;
        $previousEmail = $user->email;
        $challenge = null;

        try {
            $response = DB::transaction(function () use ($request, $next, $service, $user, $setup, &$completed, &$challenge, &$previousEmail) {
                $challenge = SecurityChallenge::whereKey($setup['challenge_id'] ?? null)->lockForUpdate()->first();
                abort_unless($challenge && $challenge->user_id === $user->id && $challenge->action === 'two-factor.enable'
                    && $challenge->status === 'approved' && $challenge->expires_at->isFuture()
                    && hash_equals($challenge->session_hash, $service->hash($request->session()->getId())), 403);
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $user->refresh();
                $previousEmail = $user->email;
                abort_if($user->hasEnabledTwoFactorAuthentication(), 403);

                $request->attributes->set('security_step_up', true);
                $response = $next($request);
                $completed = $response->getStatusCode() < 400 && ! $request->session()->has('errors');
                if ($completed && $user->fresh()->hasEnabledTwoFactorAuthentication()) {
                    $challenge->update(['status' => 'completed']);
                    $request->session()->forget('security.two_factor_setup');
                } else {
                    $completed = false;
                }

                return $response;
            });
        } catch (ValidationException $exception) {
            RateLimiter::hit($key, 600);

            throw $exception;
        }

        if ($completed && $challenge) {
            RateLimiter::clear($key);
            $service->completed($challenge, $user->fresh(), $previousEmail);
        }

        return $response;
    }
}
