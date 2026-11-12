<?php

namespace App\Console\Commands;

use App\Services\MaintenanceManagementService;
use Illuminate\Console\Command;

class MaintenanceStatus extends Command
{
    protected $signature = 'maintenance:status';

    protected $description = 'Show native Laravel maintenance status';

    public function handle(): int
    {
        try {
            try {
                $state = app(MaintenanceManagementService::class)->inspect();
                if ($state['integrity_state'] === 'unknown') {
                    $this->error('Maintenance status unavailable: inspection failed.');

                    return self::FAILURE;
                }
                $reconciliation = $state['reconciliation'];
                $nativeEvent = $state['native_event'];
                $this->table(['Property', 'Value'], [
                    ['Actual Laravel state', $state['actual_active'] === null ? 'unknown' : ($state['actual_active'] ? 'active' : 'inactive')],
                    ['Reconciliation state', $state['state']],
                    ['Unresolved native incidents', $state['unresolved_incidents']],
                    ['Next native incident', $state['incident'] ? $state['incident']->command.' at '.$state['incident']->observed_at.' (event #'.$state['incident']->id.')' : 'none'],
                    ['Acknowledged', $state['acknowledged'] ? 'yes' : 'no'],
                    ['Native operation ID', $state['native_operation_id'] ?? 'unknown'],
                    ['Native reason', $state['native_reason'] ?? 'unknown'],
                    ['Registered record', $state['operation'] ? '#'.$state['operation']->id.' '.$state['operation']->title : 'none'],
                    ['Scheduled window', $state['operation'] ? $state['operation']->scheduled_start_at.' to '.$state['operation']->scheduled_end_at : 'unknown'],
                    ['Registered in-progress IDs', $state['registered_operation_ids'] ? implode(', ', $state['registered_operation_ids']) : 'none'],
                    ['Last reconciliation', $reconciliation->observed_at ?? 'none'],
                    ['Explanation', $reconciliation->explanation ?? 'none'],
                    ['Last native Artisan command', $nativeEvent ? $nativeEvent->command.' (exit '.$nativeEvent->exit_code.') at '.$nativeEvent->observed_at : 'none'],
                    ['Native command observed state', $nativeEvent ? ($nativeEvent->actual_active === null ? 'unknown' : ($nativeEvent->actual_active ? 'active' : 'inactive')) : 'unknown'],
                    ['Driver', config('app.maintenance.driver')],
                    ['Store', config('app.maintenance.store') ?: config('cache.default')],
                ]);
                if ($state['integrity_state'] === 'inconsistent' || $state['unresolved_incidents'] > 0) {
                    $this->warn('Maintenance evidence needs review.');
                }
            } catch (\Throwable $e) {
                $active = app()->maintenanceMode()->active();
                $this->table(['Property', 'Value'], [['Actual Laravel state', $active ? 'active' : 'inactive'], ['Registry', 'unavailable'], ['Consistency', 'unknown']]);
                $this->error('Reconciliation status unavailable: '.$e->getMessage());

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Maintenance status unavailable: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
