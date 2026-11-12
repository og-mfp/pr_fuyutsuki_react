<?php

namespace App\Console\Commands;

use App\Services\MaintenanceManagementService;
use Illuminate\Console\Command;

class MaintenanceEnd extends Command
{
    protected $signature = 'maintenance:end {--summary= : Required completion summary} {--operator= : Operator identifier} {--window= : Incomplete operation ID to recover}';

    protected $description = 'Restore normal application availability';

    public function handle(MaintenanceManagementService $service): int
    {
        try {
            $summary = $this->option('summary');
            $operator = trim((string) $this->option('operator'));
            if ($operator !== '' && mb_strlen($operator) > 160) {
                throw new \InvalidArgumentException('Operator identifier must not exceed 160 characters.');
            }
            $window = $this->option('window');
            if ($window !== null && (! ctype_digit((string) $window) || (int) $window < 1)) {
                throw new \InvalidArgumentException('Window must be a positive operation ID.');
            }
            $service->end($summary, $operator !== '' ? $operator : null, $window === null ? null : (int) $window);
            $this->info('Maintenance ended.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
