<?php

namespace App\Security;

use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class TrustedDeviceService
{
    public const COOKIE = 'fuyutsuki_trusted_device';

    public function recognize(Request $request, User $user): ?TrustedDevice
    {
        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return null;
        }

        return TrustedDevice::where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')->where('expires_at', '>', now())->first();
    }

    public function register(Request $request, User $user): void
    {
        $settings = app(SettingsService::class);
        if (! $settings->get('authentication.trusted_devices_enabled')
            || ($settings->get('authentication.mfa_requirement') === 'required'
                && ! $user->hasEnabledTwoFactorAuthentication())) {
            return;
        }
        if ($device = $this->recognize($request, $user)) {
            $device->update(['last_seen_at' => now()]);

            return;
        }

        $token = bin2hex(random_bytes(32));
        $days = (int) $settings->get('authentication.trusted_device_days');
        TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'name' => substr((string) $request->userAgent(), 0, 255) ?: 'Unknown browser',
            'first_seen_at' => now(), 'last_seen_at' => now(), 'expires_at' => now()->addDays($days),
        ]);
        Cookie::queue(cookie(self::COOKIE, $token, $days * 1440, '/', null, true, true, false, 'lax'));
        activity('security')->event('trusted-device.registered')->causedBy($user)->performedOn($user)->log('trusted-device.registered');
    }
}
