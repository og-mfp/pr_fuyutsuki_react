<?php

namespace App\Security;

readonly class RiskDecision
{
    /** @param list<string> $signals */
    public function __construct(public int $score, public string $level, public array $signals) {}
}
