<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class HighRiskAuthenticationDetected
{
    use Dispatchable;

    public function __construct(public User $user, public int $score, public string $ip) {}
}
