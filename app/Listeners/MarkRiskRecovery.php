<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

class MarkRiskRecovery
{
    public function handle(PasswordReset $event): void
    {
        if (request()->routeIs('password.update') && $event->user instanceof User) {
            request()->session()->put('risk.recovery', [
                'user_id' => $event->user->id,
                'expires_at' => time() + 600,
            ]);
        }
    }
}
