<?php

namespace App\Security;

use App\Models\User;
use App\Security\Signals\RiskSignal;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class LoginRiskEvaluator
{
    public function __construct(private RiskCounterService $counters, private TrustedDeviceService $devices, private SettingsService $settings) {}

    public function evaluate(Request $request, User $user): RiskDecision
    {
        $signals = [];
        $score = 0;
        $ip = (string) $request->ip();
        $account = $this->counters->identity($request);
        $devicesEnabled = $this->settings->get('authentication.trusted_devices_enabled');
        $device = $devicesEnabled ? $this->devices->recognize($request, $user) : null;

        if ($devicesEnabled && ! $device) {
            $signals[] = 'unknown_device';
            $score += config('authentication-risk.weights.unknown_device');
        }
        if ($this->counters->failures('account-failures', $account) >= (int) $this->settings->get('authentication.account_failure_threshold')) {
            $signals[] = 'account_failures';
            $score += config('authentication-risk.weights.account_failures');
        }
        if ($this->counters->failures('ip-failures', $ip) >= config('authentication-risk.thresholds.source_failures')) {
            $signals[] = 'source_failures';
            $score += config('authentication-risk.weights.source_failures');
        }
        if ($this->counters->failures('ip-targets', $ip) >= config('authentication-risk.thresholds.multiple_accounts')) {
            $signals[] = 'multiple_accounts';
            $score += config('authentication-risk.weights.multiple_accounts');
        }
        if ($this->counters->failures('ip-volume', $ip) >= (int) $this->settings->get('authentication.ip_login_threshold')) {
            $signals[] = 'source_volume';
            $score += config('authentication-risk.weights.source_volume');
        }
        if ($this->settings->get('authentication.browser_signals') && $device
            && $device->name !== substr((string) $request->userAgent(), 0, 255)) {
            $signals[] = 'browser_changed';
            $score += config('authentication-risk.weights.browser_changed');
        }
        $extra = 0;
        foreach (config('authentication-risk.signals', []) as $class) {
            $signal = app($class);
            if (! $signal instanceof RiskSignal) {
                throw new \LogicException('Configured authentication risk signal must implement RiskSignal.');
            }
            $points = max(0, min(20, $signal->score($request, $user)));
            if ($points > 0) {
                $signals[] = $signal->name();
                $extra += $points;
            }
        }
        $score += min(20, $extra);

        $medium = (int) $this->settings->get('authentication.medium_risk_threshold');
        $high = (int) $this->settings->get('authentication.high_risk_threshold');

        $score = min(100, $score);

        return new RiskDecision($score, $score >= $high ? 'high' : ($score >= $medium ? 'medium' : 'low'), $signals);
    }
}
