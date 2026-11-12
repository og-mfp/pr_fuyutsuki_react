<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SecurityChallenge;
use App\Services\SecurityChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SecurityChallengeController extends Controller
{
    public function store(Request $request, SecurityChallengeService $service): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(array_keys(SecurityChallengeService::ACTIONS))],
            'target' => [Rule::requiredIf(fn () => in_array($request->input('action'), SecurityChallengeService::TARGET_ACTIONS, true)), 'nullable', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
            'payload.name' => ['required_if:action,profile.update', 'string', 'max:255'],
            'payload.email' => ['required_if:action,profile.update', 'string', 'max:255'],
            'payload.password' => ['required_if:action,user-password.update', 'string', Password::default(), 'confirmed', 'max:4096'],
            'payload.password_confirmation' => ['required_if:action,user-password.update', 'string', 'max:4096'],
            'payload.current_password' => ['required_if:action,user-password.update', 'string', 'max:4096'],
        ], [], ['payload.password' => 'password']);
        $user = $request->user();
        abort_if($user->security_recovery_required !== null, 403);
        if (! $user->hasEnabledTwoFactorAuthentication() && $service->emailCodeLocked($user)) {
            throw ValidationException::withMessages(['action' => 'Too many unsuccessful security challenges. Try again later.']);
        }
        $rateKey = $service->hash('step-up-start|'.$user->id);
        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            throw ValidationException::withMessages(['action' => 'Too many security challenges. Try again later.']);
        }
        RateLimiter::hit($rateKey, 60);
        $code = $user->hasEnabledTwoFactorAuthentication() ? null : (string) random_int(100000, 999999);
        $challenge = SecurityChallenge::create([
            'user_id' => $user->id,
            'action' => $data['action'],
            'notification_email' => $user->email,
            'target' => $data['target'] ?? null,
            'payload_hash' => $service->payloadHash($data['action'], $data['payload'] ?? []),
            'session_hash' => $service->hash($request->session()->getId()),
            'code_hash' => $code ? $service->hash($code) : null,
            'expires_at' => now()->addMinutes(10),
            'report_expires_at' => now()->addDays(30),
        ]);
        if ($code) {
            try {
                $service->sendChallenge($challenge, $user, $code);
            } catch (Throwable $exception) {
                $challenge->update(['status' => 'canceled', 'code_hash' => null]);
                report($exception);
                throw ValidationException::withMessages(['action' => 'The confirmation email could not be sent.']);
            }
        }

        return response()->json(['id' => $challenge->id, 'method' => $code ? 'email' : 'mfa']);
    }

    public function confirm(Request $request, SecurityChallenge $challenge, SecurityChallengeService $service): \Illuminate\Http\Response
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:100'], 'recovery_code' => ['nullable', 'string', 'max:100']]);
        $service->confirm($challenge, $request, $data['code'] ?? null, $data['recovery_code'] ?? null);

        return response()->noContent();
    }

    public function showReport(string $id, string $token, SecurityChallengeService $service): Response
    {
        $challenge = SecurityChallenge::findOrFail($id);
        abort_unless(hash_equals($service->hash('report|'.$challenge->id.'|'.$challenge->user_id), $token), 404);
        abort_unless($service->reportable($challenge), 410);

        return Inertia::render('auth/ReportSecurityChange', [
            'id' => $challenge->id, 'token' => $token,
            'action' => SecurityChallengeService::ACTIONS[$challenge->action],
        ]);
    }

    public function report(string $id, string $token, SecurityChallengeService $service): RedirectResponse
    {
        $challenge = SecurityChallenge::findOrFail($id);
        abort_unless(hash_equals($service->hash('report|'.$challenge->id.'|'.$challenge->user_id), $token), 404);
        $service->report($challenge);

        return redirect()->route('login')->with('status', 'The security activity was reported. Use account recovery or contact an administrator.');
    }
}
