<?php

namespace App\Console\Commands;

use App\Models\MaintenanceWindow;
use App\Services\MaintenanceManagementService;
use Illuminate\Console\Command;

class MaintenanceBegin extends Command
{
    protected $signature = 'maintenance:begin {--window= : Scheduled maintenance window ID} {--emergency : Create emergency operation} {--reason= : Required internal reason} {--override : Confirm early or late planned execution} {--operator= : Required CLI operator identifier} {--with-secret : Generate a maintenance bypass URL}';

    protected $description = 'Activate Laravel maintenance mode for a registered operation';

    public function handle(MaintenanceManagementService $service): int
    {
        try {
            $emergency = (bool) $this->option('emergency');
            $windowOption = $this->option('window');
            $windowId = filter_var($windowOption, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($windowOption !== null && $windowId === false) {
                throw new \InvalidArgumentException('Maintenance window ID must be a positive integer.');
            }
            $window = $windowId !== false ? MaintenanceWindow::find($windowId) : null;
            if ($windowId !== false && ! $window) {
                throw new \InvalidArgumentException("Maintenance window {$windowId} not found. Create a schedule in Admin → Configuration → Health & Maintenance and use its ID.");
            }
            if ((! $window && ! $emergency) || ($window && $emergency)) {
                throw new \InvalidArgumentException('Supply --window or --emergency.');
            }
            if ($window && ($window->scheduled_start_at->isFuture() || $window->scheduled_end_at->isPast()) && ! $this->option('override')) {
                throw new \InvalidArgumentException('Outside scheduled window; pass --override to confirm.');
            }
            $reason = $this->option('reason');
            $operator = trim((string) $this->option('operator'));
            if ($operator === '') {
                throw new \InvalidArgumentException('Operator identifier is required.');
            }
            if (mb_strlen($operator) > 160) {
                throw new \InvalidArgumentException('Operator identifier must not exceed 160 characters.');
            }
            $withSecret = (bool) $this->option('with-secret');
            $service->begin($window, (is_string($reason) ? $reason : '') ?: ($window->reason ?? ''), $emergency, $operator, (bool) $this->option('override'), $withSecret);
            $this->info('Maintenance mode activated.');
            if ($withSecret) {
                $this->line('Bypass URL: '.rtrim(config('app.url'), '/').'/'.app()->maintenanceMode()->data()['secret']);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
