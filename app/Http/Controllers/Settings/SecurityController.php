<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $passwordRule = Password::defaults();
        $passwordRequirements = $passwordRule->appliedRules();
        $props = [
            'passwordConfirmed' => true,
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication()
                && app(SettingsService::class)->get('authentication.mfa_enabled'),
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => Features::canManagePasskeys()
                ? $request->user()
                    ->passkeys()
                    ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
                    ->latest()
                    ->get()
                    ->map(fn ($passkey) => [
                        'id' => $passkey->id,
                        'name' => $passkey->name,
                        'authenticator' => $passkey->authenticator,
                        'created_at_diff' => $passkey->created_at->diffForHumans(),
                        'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
                    ])
                    ->values()
                    ->all()
                : [],
            'passwordRules' => $passwordRule->toPasswordRulesString(),
            'passwordRequirements' => array_intersect_key($passwordRequirements, array_flip([
                'min', 'max', 'mixedCase', 'letters', 'numbers', 'symbols', 'uncompromised',
            ])),
            'trustedDevices' => $request->user()->trustedDevices()->whereNull('revoked_at')
                ->where('expires_at', '>', now())->latest('last_seen_at')->get()
                ->map(fn ($device) => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'first_seen_at' => $device->first_seen_at->toDateString(),
                    'last_seen_at' => $device->last_seen_at->toDateString(),
                ])->all(),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('settings/security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['password' => $request->password]);
        $request->user()->setRememberToken(Str::random(60));
        $request->user()->save();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        activity('security')->event('password.changed')->causedBy($request->user())
            ->performedOn($request->user())->log('password.changed');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
