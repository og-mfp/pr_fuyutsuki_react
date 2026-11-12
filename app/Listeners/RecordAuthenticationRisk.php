<?php

namespace App\Listeners;

use App\Models\User;
use App\Security\RiskCounterService;
use App\Security\TrustedDeviceService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

class RecordAuthenticationRisk
{
    public function handle(object $event): void
    {
        if ($event instanceof Failed && request()->routeIs('login.store')) {
            app(RiskCounterService::class)->recordFailure(request());
        }
        if ($event instanceof Login && $event->user instanceof User
            && request()->routeIs('login.store', 'two-factor.login.store', 'passkey.login')) {
            app(TrustedDeviceService::class)->register(request(), $event->user);
        }
    }
}
