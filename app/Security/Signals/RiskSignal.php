<?php

namespace App\Security\Signals;

use App\Models\User;
use Illuminate\Http\Request;

interface RiskSignal
{
    public function name(): string;

    public function score(Request $request, User $user): int;
}
