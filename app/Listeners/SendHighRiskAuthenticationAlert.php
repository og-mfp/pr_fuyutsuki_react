<?php

namespace App\Listeners;

use App\Events\HighRiskAuthenticationDetected;
use App\Models\User;
use App\Notifications\HighRiskAuthenticationNotification;
use App\Security\RiskCounterService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

class SendHighRiskAuthenticationAlert
{
    public function handle(HighRiskAuthenticationDetected $event): void
    {
        if (! app(SettingsService::class)->get('notifications.security_enabled')) {
            return;
        }
        $mailer = config('mail.default');
        if (in_array(config("mail.mailers.{$mailer}.transport"), ['log', 'array', 'null'], true)) {
            return;
        }

        $key = app(RiskCounterService::class)->key('high-risk-alert', $event->user->id.'|'.$event->ip);
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return;
        }
        RateLimiter::hit($key, 600);

        $owner = User::find(DB::table('installation_metadata')->where('id', 1)->value('owner_id'));
        Notification::send($owner ? collect([$owner]) : collect(), new HighRiskAuthenticationNotification($event->user->id, $event->score, $event->ip));
    }
}
