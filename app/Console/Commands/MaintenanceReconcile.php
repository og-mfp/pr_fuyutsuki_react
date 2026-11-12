<?php

namespace App\Console\Commands;

use App\Services\MaintenanceManagementService;
use Illuminate\Console\Command;

class MaintenanceReconcile extends Command
{
    protected $signature = 'maintenance:reconcile {--explanation= : Required explanation for accepting observed state} {--operator= : Required CLI operator identifier}';

    protected $description = 'Record an explanation for inconsistent native maintenance state without changing availability';

    public function handle(MaintenanceManagementService $service): int
    {
        try {
            $explanation = $this->option('explanation');
            $operator = trim((string) $this->option('operator'));
            if ($operator === '') {
                throw new \InvalidArgumentException('Operator identifier is required.');
            }
            if (mb_strlen($operator) > 160) {
                throw new \InvalidArgumentException('Operator identifier must not exceed 160 characters.');
            }
            $record = $service->reconcile($explanation, null, $operator);
            $this->info("Reconciliation #{$record->id} recorded. Native maintenance state was not changed.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
