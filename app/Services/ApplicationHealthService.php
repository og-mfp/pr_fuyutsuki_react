<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApplicationHealthService
{
    /** @return array{application: string, database: string, cache: string, queue: array{driver: string, backend_status: string, worker_status: string}, installation_owner: string, maintenance: array{active: bool|null, driver: string, store: string, reason: string|null, activated_at: null, actor: null}} */
    public function snapshot(): array
    {
        $maintenance = $this->maintenanceStatus();

        try {
            DB::select('select 1');
            $database = 'available';
        } catch (Throwable) {
            $database = 'unavailable';
        }

        try {
            Cache::store()->get('health-check-read-only');
            $cache = 'readable';
        } catch (Throwable) {
            $cache = 'unavailable';
        }

        return [
            'application' => match ($maintenance['active']) {
                true => 'maintenance', false => 'available', default => 'unknown'
            },
            'database' => $database,
            'cache' => $cache,
            'queue' => $this->queueStatus(),
            'installation_owner' => rescue(fn () => app(InstallationIntegrityService::class)->ownerIsConsistent() ? 'consistent' : 'compromised', 'unknown', report: false),
            'maintenance' => $maintenance,
        ];
    }

    /** @return array{driver: string, backend_status: string, worker_status: string} */
    private function queueStatus(): array
    {
        $driver = (string) config('queue.default');
        if (in_array($driver, ['sync', 'deferred', 'background', 'null'], true)) {
            return ['driver' => $driver, 'backend_status' => 'not_applicable', 'worker_status' => 'not_applicable'];
        }

        if ($driver !== 'database') {
            return ['driver' => $driver, 'backend_status' => 'not_verified', 'worker_status' => 'not_verified'];
        }

        try {
            $connection = config('queue.connections.database.connection');
            DB::connection(is_string($connection) && $connection !== '' ? $connection : null)->select('select 1');

            return ['driver' => $driver, 'backend_status' => 'reachable', 'worker_status' => 'not_verified'];
        } catch (Throwable) {
            return ['driver' => $driver, 'backend_status' => 'unreachable', 'worker_status' => 'not_verified'];
        }
    }

    /** @return array{active: bool|null, driver: string, store: string, reason: string|null, activated_at: null, actor: null} */
    public function maintenanceStatus(): array
    {
        $payload = [];
        try {
            $mode = app()->maintenanceMode();
            $active = $mode->active();
            $payload = $active ? $mode->data() : [];
        } catch (Throwable) {
            $active = null;
        }

        return [
            'active' => $active,
            'driver' => config('app.maintenance.driver'),
            'store' => config('app.maintenance.store') ?: config('cache.default'),
            'reason' => is_string($payload['reason'] ?? null) ? $payload['reason'] : null,
            'activated_at' => null,
            'actor' => null,
        ];
    }
}
