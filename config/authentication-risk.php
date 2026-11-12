<?php

return [
    'trusted_proxies' => array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))),
    'thresholds' => ['source_failures' => 10, 'multiple_accounts' => 5],
    'cooldown_seconds' => ['medium' => 60, 'high' => 600],
    'weights' => [
        'unknown_device' => 10,
        'account_failures' => 15,
        'source_failures' => 30,
        'multiple_accounts' => 25,
        'source_volume' => 10,
        'browser_changed' => 10,
    ],
    'signals' => [],
];
