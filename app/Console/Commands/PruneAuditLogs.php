<?php

namespace App\Console\Commands;

use App\Models\TrustedDevice;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Prune expired activity records while retaining security events longer';

    public function handle(): int
    {
        $days = max(1, (int) config('activitylog.delete_records_older_than_days'));
        $securityDays = max($days, (int) config('activitylog.security_retention_days'));
        $count = Activity::where('log_name', '!=', 'security')->where('created_at', '<', now()->subDays($days))->delete();
        $count += Activity::where('log_name', 'security')->where('created_at', '<', now()->subDays($securityDays))->delete();
        TrustedDevice::where('expires_at', '<', now())
            ->orWhere('revoked_at', '<', now()->subDays(30))->delete();
        $this->info("Pruned {$count} activity records.");

        return self::SUCCESS;
    }
}
