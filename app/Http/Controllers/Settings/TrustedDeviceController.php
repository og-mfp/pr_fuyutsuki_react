<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Security\TrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class TrustedDeviceController extends Controller
{
    public function destroy(Request $request, int $device): RedirectResponse
    {
        $owned = $request->user()->trustedDevices()->findOrFail($device);
        $current = app(TrustedDeviceService::class)->recognize($request, $request->user())?->is($owned);
        $owned->update(['revoked_at' => now()]);
        if ($current) {
            Cookie::queue(Cookie::forget(TrustedDeviceService::COOKIE));
        }
        activity('security')->event('trusted-device.revoked')->causedBy($request->user())->performedOn($request->user())->log('trusted-device.revoked');

        return back();
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $request->user()->trustedDevices()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        Cookie::queue(Cookie::forget(TrustedDeviceService::COOKIE));
        activity('security')->event('trusted-device.revoked-all')->causedBy($request->user())->performedOn($request->user())->log('trusted-device.revoked-all');

        return back();
    }
}
