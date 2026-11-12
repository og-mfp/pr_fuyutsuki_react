<?php

namespace App\Services;

use App\Models\MaintenanceNativeEvent;
use App\Models\MaintenanceReconciliation;
use App\Models\MaintenanceWindow;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class MaintenanceManagementService
{
    /**
     * @return array{
     *     actual_active: bool|null,
     *     native_operation_id: int|null,
     *     native_reason: string|null,
     *     operation: MaintenanceWindow|null,
     *     registered_operation_ids: list<int>,
     *     consistent: bool,
     *     state: string,
     *     integrity_state: 'healthy'|'inconsistent'|'unknown',
     *     acknowledged: bool,
     *     reconciliation: MaintenanceReconciliation|null,
     *     native_event: MaintenanceNativeEvent|null,
     *     incident: MaintenanceNativeEvent|null,
     *     unresolved_incidents: int,
     *     transition_event: MaintenanceNativeEvent|null
     * }
     */
    public function inspect(): array
    {
        try {
            $mode = app()->maintenanceMode();
            $active = $mode->active();
            $data = $active ? $mode->data() : [];
            $operationId = filter_var($data['operation_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $registeredIds = array_values(MaintenanceWindow::where('status', 'in_progress')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all());
            $nativeEvent = MaintenanceNativeEvent::orderByDesc('id')->first();
            $acknowledgedEventIds = MaintenanceReconciliation::whereNotNull('native_event_id')->pluck('native_event_id')->all();
            $incidents = [];
            $previous = null;
            $transition = null;
            foreach (MaintenanceNativeEvent::where('exit_code', 0)->orderBy('id')->cursor() as $event) {
                if ($event->actual_active === null || $event->registered_operation_ids === null
                    || ($event->command === 'down' && ! $event->actual_active)
                    || ($event->command === 'up' && $event->actual_active)) {
                    continue;
                }
                $changed = $previous === null || $previous->actual_active !== $event->actual_active;
                $mismatch = $event->actual_active
                    ? count($event->registered_operation_ids) !== 1 || $event->native_operation_id !== $event->registered_operation_ids[0]
                    : $event->registered_operation_ids !== [];
                $changedEvidence = $changed || $previous->registered_operation_ids !== $event->registered_operation_ids || $previous->native_operation_id !== $event->native_operation_id;
                if ($changed || ($mismatch && $changedEvidence)) {
                    $transition = $event;
                }
                if (($mismatch && $changedEvidence)
                    || ($changed && $event->command === 'up' && $previous?->command === 'down'
                        && $previous->registered_operation_ids === [] && $event->registered_operation_ids === [])) {
                    if (! in_array($event->id, $acknowledgedEventIds)) {
                        $incidents[] = $event;
                    }
                }
                $previous = $event;
            }
            $consistent = $active
                ? count($registeredIds) === 1 && $operationId === $registeredIds[0]
                : $registeredIds === [];
            $state = $consistent ? 'consistent' : ($active ? ($registeredIds ? 'conflicting_records' : 'untracked_activation') : 'untracked_completion');
            $operation = $operationId ? MaintenanceWindow::find($operationId) : MaintenanceWindow::find($registeredIds[0] ?? 0);
            $nativeReason = is_string($data['reason'] ?? null) ? $data['reason'] : null;
            $last = MaintenanceReconciliation::orderByDesc('id')->first();
            $acknowledged = MaintenanceReconciliation::where('actual_active', $active)
                ->where('native_operation_id', $operationId)->where('native_reason', $nativeReason)
                ->where('native_event_id', $transition?->id)->get()
                ->contains(fn ($record) => $record->registered_operation_ids === $registeredIds);

            return [
                'actual_active' => $active,
                'native_operation_id' => $operationId,
                'native_reason' => $nativeReason,
                'operation' => $operation,
                'registered_operation_ids' => $registeredIds,
                'consistent' => $consistent,
                'state' => $state,
                'integrity_state' => $consistent ? 'healthy' : 'inconsistent',
                'acknowledged' => $acknowledged,
                'reconciliation' => $last,
                'native_event' => $nativeEvent,
                'incident' => $incidents[0] ?? null,
                'unresolved_incidents' => count($incidents),
                'transition_event' => $transition,
            ];
        } catch (Throwable $e) {
            Log::warning('Maintenance integrity inspection unavailable', ['exception' => $e]);

            return [
                'actual_active' => null,
                'native_operation_id' => null,
                'native_reason' => null,
                'operation' => null,
                'registered_operation_ids' => [],
                'consistent' => false,
                'state' => 'unknown',
                'integrity_state' => 'unknown',
                'acknowledged' => false,
                'reconciliation' => null,
                'native_event' => null,
                'incident' => null,
                'unresolved_incidents' => 0,
                'transition_event' => null,
            ];
        }
    }

    /** @param array<string, mixed> $commandContext */
    public function recordNativeCommand(string $command, int $exitCode, array $commandContext = []): void
    {
        $active = null;
        $operationId = null;
        $reason = null;
        $registeredIds = null;
        try {
            $mode = app()->maintenanceMode();
            $active = $mode->active();
            $data = $active ? $mode->data() : [];
            $operationId = filter_var($data['operation_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $reason = is_string($data['reason'] ?? null) ? $data['reason'] : null;
        } catch (Throwable $e) {
            Log::warning('Could not inspect maintenance state after native Artisan command', ['command' => $command, 'exception' => $e]);
        }
        try {
            $registeredIds = MaintenanceWindow::where('status', 'in_progress')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        } catch (Throwable $e) {
            Log::warning('Could not read maintenance operations after native Artisan command', ['command' => $command, 'exception' => $e]);
        }
        try {
            MaintenanceNativeEvent::create([
                'command' => $command,
                'exit_code' => max(0, min(65535, $exitCode)),
                'actual_active' => $active,
                'native_operation_id' => $operationId,
                'native_reason' => $reason,
                'registered_operation_ids' => $registeredIds,
                'observed_at' => now()->utc(),
            ]);
        } catch (Throwable $e) {
            Log::critical('Native Artisan maintenance event was not persisted', ['command' => $command, 'exit_code' => $exitCode, 'actual_active' => $active, 'exception' => $e]);
        }
        Log::notice('Native Artisan maintenance command finished', [
            ...$commandContext,
            'command' => $command,
            'exit_code' => $exitCode,
            'actual_active' => $active,
        ]);
    }

    public function reconcile(string $explanation, ?User $actor = null, ?string $operator = null): MaintenanceReconciliation
    {
        if (trim($explanation) === '') {
            throw new \InvalidArgumentException('Reconciliation explanation is required.');
        }
        $state = $this->inspect();
        if ($state['integrity_state'] === 'unknown') {
            throw new \RuntimeException('Maintenance state is unknown; inspection dependencies are unavailable.');
        }
        if ($state['consistent'] && ! $state['incident']) {
            throw new \RuntimeException('Maintenance state is already consistent; no reconciliation was recorded.');
        }
        $record = MaintenanceReconciliation::create([
            'actual_active' => $state['actual_active'],
            'native_operation_id' => $state['native_operation_id'],
            'native_event_id' => $state['incident']->id ?? $state['transition_event']?->id,
            'native_reason' => $state['native_reason'],
            'registered_operation_ids' => $state['registered_operation_ids'],
            'explanation' => trim($explanation),
            'operator_identifier' => $operator,
            'reconciled_by' => $actor?->id,
            'observed_at' => now()->utc(),
        ]);
        $this->audit('maintenance state reconciled', null, $actor, trim($explanation), $operator);
        Log::notice('Maintenance state reconciliation recorded; native availability state unchanged', [
            'reconciliation_id' => $record->id,
            'actual_active' => $record->actual_active,
            'native_operation_id' => $record->native_operation_id,
            'registered_operation_ids' => $record->registered_operation_ids,
            'reconciled_by' => $record->reconciled_by,
            'operator' => $record->operator_identifier,
        ]);

        return $record;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor, string $timezone = 'UTC'): MaintenanceWindow
    {
        $start = now()->parse($data['scheduled_start_at'], $timezone)->utc();
        $end = now()->parse($data['scheduled_end_at'], $timezone)->utc();
        if ($start->isPast()) {
            throw ValidationException::withMessages(['scheduled_start_at' => 'Start time must be in the future.']);
        }
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['scheduled_end_at' => 'End must be after start.']);
        }
        $this->assertNoConflict($start, $end);
        $window = MaintenanceWindow::create([...$data, 'scheduled_start_at' => $start, 'scheduled_end_at' => $end, 'created_by' => $actor?->id, 'status' => 'scheduled']);
        $this->audit('maintenance scheduled', $window, $actor, $data['reason']);

        return $window;
    }

    /** @param array<string, mixed> $data */
    public function update(MaintenanceWindow $window, array $data, User $actor, string $timezone = 'UTC'): void
    {
        abort_unless($window->status === 'scheduled' && $window->scheduled_start_at->isFuture(), 409);
        $start = now()->parse($data['scheduled_start_at'], $timezone)->utc();
        $end = now()->parse($data['scheduled_end_at'], $timezone)->utc();
        if ($start->isPast()) {
            throw ValidationException::withMessages(['scheduled_start_at' => 'Start time must be in the future.']);
        }
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['scheduled_end_at' => 'End must be after start.']);
        }
        $this->assertNoConflict($start, $end, $window->id);
        $window->update([...$data, 'scheduled_start_at' => $start, 'scheduled_end_at' => $end]);
        $this->audit('maintenance schedule modified', $window, $actor, $data['reason']);
    }

    public function cancel(MaintenanceWindow $window, User $actor): void
    {
        abort_unless($window->status === 'scheduled', 409);
        $window->update(['status' => 'cancelled']);
        $this->audit('maintenance schedule cancelled', $window, $actor, $window->reason);
    }

    public function begin(?MaintenanceWindow $window, string $reason, bool $emergency = false, ?string $operator = null, bool $override = false, bool $withSecret = false): void
    {
        $this->withLock(function () use ($window, $reason, $emergency, $operator, $override, $withSecret) {
            $mode = app()->maintenanceMode();
            if ($mode->active()) {
                throw new \RuntimeException('Laravel maintenance mode is already active.');
            }
            if (trim($reason) === '') {
                throw new \InvalidArgumentException('A maintenance reason is required.');
            }
            if ($window) {
                $window->refresh();
                if ($window->status !== 'scheduled') {
                    throw new \RuntimeException('Window is no longer scheduled.');
                }
                if (! $override && ($window->scheduled_start_at->isFuture() || $window->scheduled_end_at->isPast())) {
                    throw new \RuntimeException('Window is outside its scheduled time; use --override for early or late execution.');
                }
            }
            try {
                $hasActiveRecord = MaintenanceWindow::where('status', 'in_progress')->exists();
            } catch (Throwable $e) {
                if (! $emergency) {
                    throw $e;
                }
                Log::critical('Emergency maintenance history unavailable; activating without database record', ['exception' => $e]);
                $hasActiveRecord = false;
                $window = null;
            }
            if ($hasActiveRecord) {
                throw new \RuntimeException('Another registered maintenance operation is active.');
            }
            if (! $window) {
                try {
                    if (! $emergency) {
                        throw new \RuntimeException('Scheduled maintenance record is unavailable.');
                    }
                    $window = MaintenanceWindow::create(['title' => 'Emergency maintenance', 'type' => 'emergency', 'reason' => $reason, 'public_message' => 'We are performing emergency maintenance. Please try again shortly.', 'scheduled_start_at' => now()->utc(), 'scheduled_end_at' => now()->addHour()->utc(), 'status' => 'scheduled', 'created_by' => null, 'published' => false]);
                } catch (Throwable $e) {
                    if (! $emergency) {
                        throw $e;
                    }
                    Log::critical('Emergency maintenance history unavailable; activating without database record', ['exception' => $e]);
                    $window = null;
                }
            }
            $message = $window->public_message ?? 'We are performing emergency maintenance. Please try again shortly.';
            $completion = $window?->scheduled_end_at->timezone(config('app.timezone'))->format('Y-m-d H:i T') ?? '';
            $heading = 'Scheduled System Maintenance';
            $support = '';
            $brand = config('app.name');
            $retry = 300;
            try {
                $settings = app(SettingsService::class);
                $heading = $settings->get('health_maintenance.heading');
                $support = $settings->get('health_maintenance.support_contact');
                $brand = $settings->get('general.display_name');
                $retry = (int) $settings->get('health_maintenance.retry_after');
            } catch (Throwable $e) {
                Log::warning('Maintenance page settings unavailable; using static defaults', ['exception' => $e]);
            }
            try {
                $html = view('errors.maintenance', ['heading' => $heading, 'description' => $message, 'scheduled_window' => $completion, 'support_contact' => $support, 'brand' => $brand])->render();
            } catch (Throwable $e) {
                Log::error('Maintenance page snapshot rendering failed; using escaped static fallback', ['exception' => $e]);
                $html = '<!doctype html><html><meta charset="utf-8"><title>Maintenance</title><body><main><h1>Scheduled System Maintenance</h1><p>'.e($message).'</p>'.($completion !== '' ? '<p>Estimated completion: '.e($completion).'</p>' : '').'</main></body></html>';
            }
            try {
                $window?->update(['status' => 'in_progress', 'started_at' => now()->utc(), 'started_by' => null, 'started_by_operator' => $operator]);
            } catch (Throwable $e) {
                if (! $emergency) {
                    throw $e;
                } Log::error('Emergency maintenance activation continuing without history update', ['exception' => $e]);
            }
            try {
                app()->instance('maintenance.managed-payload', ['status' => 503, 'retry' => $retry > 0 ? $retry : null, 'template' => $html, 'reason' => $reason, 'operation_id' => $window?->id, 'operator' => $operator]);
                try {
                    $exitCode = Artisan::call('maintenance:managed-down', ['--with-secret' => $withSecret]);
                } finally {
                    app()->forgetInstance('maintenance.managed-payload');
                }
                if ($exitCode !== 0) {
                    throw new \RuntimeException('Laravel maintenance activation command failed: '.trim(Artisan::output()));
                }
                $mode = app()->maintenanceMode();
                if (! $mode->active()) {
                    throw new \RuntimeException('Laravel did not enter maintenance mode.');
                }
            } catch (Throwable $e) {
                try {
                    $mode = app()->maintenanceMode();
                    if (! $mode->active()) {
                        $window?->update(['status' => 'failed']);
                    }
                } catch (Throwable) {
                }
                $this->audit('maintenance activation failed', $window, null, $reason);
                throw $e;
            }
            $this->audit($emergency ? 'emergency maintenance activated' : 'maintenance activated', $window, null, $reason, $operator);
        });
    }

    public function end(string $summary, ?string $operator = null, ?int $windowId = null): void
    {
        if (trim($summary) === '') {
            throw new \InvalidArgumentException('Completion summary is required.');
        }
        $this->withLock(function () use ($summary, $operator, $windowId) {
            $mode = app()->maintenanceMode();
            $active = $mode->active();
            $id = $active ? (filter_var($mode->data()['operation_id'] ?? null, FILTER_VALIDATE_INT) ?: null) : null;
            try {
                if ($active && $windowId !== null && $windowId !== $id) {
                    throw new \InvalidArgumentException('The selected window does not match the active maintenance operation.');
                }
                $candidates = ! $active && $windowId === null ? MaintenanceWindow::where('status', 'in_progress')->limit(2)->get() : collect();
                if ($candidates->count() > 1) {
                    throw new \InvalidArgumentException('Multiple incomplete operations exist; select one with --window.');
                }
                $window = $id || $windowId ? MaintenanceWindow::find($id ?? $windowId) : $candidates->first();
            } catch (Throwable $e) {
                if ($e instanceof \InvalidArgumentException) {
                    throw $e;
                }
                $window = null;
                if ($active) {
                    Log::error('Maintenance record unavailable during recovery; restoring Laravel first', ['operation_id' => $id, 'exception' => $e]);
                } else {
                    throw new \RuntimeException('Maintenance is inactive and completion record could not be loaded.', previous: $e);
                }
            }
            if (! $active && (! $window || $window->status !== 'in_progress')) {
                throw new \RuntimeException('Laravel maintenance mode is not active and no incomplete operation needs recovery.');
            }
            $this->invalidateLoginSessions();
            if ($active) {
                app()->instance('maintenance.managed-payload', []);
                try {
                    $exitCode = Artisan::call('maintenance:managed-up');
                } finally {
                    app()->forgetInstance('maintenance.managed-payload');
                }
                if ($exitCode !== 0) {
                    throw new \RuntimeException('Laravel maintenance recovery command failed: '.trim(Artisan::output()));
                }
                if ($mode->active()) {
                    throw new \RuntimeException('Laravel remained in maintenance mode after recovery.');
                }
            }
            try {
                if ($window && $window->status === 'in_progress') {
                    $observedRecovery = $active ? now()->utc() : ($window->started_at ? MaintenanceNativeEvent::where('command', 'up')->where('exit_code', 0)
                        ->where('actual_active', false)->where('observed_at', '>=', $window->started_at)
                        ->orderBy('id')->get()->first(fn ($event) => in_array($window->id, $event->registered_operation_ids ?? []))?->observed_at : null);
                    $window->update(['status' => 'completed', 'ended_at' => $active ? $observedRecovery : null, 'recovery_observed_at' => $observedRecovery, 'closed_at' => now()->utc(), 'completed_by' => null, 'completed_by_operator' => $operator, 'operator_notes' => trim(($window->operator_notes ? $window->operator_notes."\n" : '').$summary)]);
                }
                $this->audit('maintenance completed', $window, null, $summary, $operator);
            } catch (Throwable $e) {
                Log::error('Maintenance ended but recording completion failed', ['operation_id' => $id, 'exception' => $e]);
                throw $e;
            }
        });
    }

    private function withLock(callable $callback): void
    {
        if (config('app.maintenance.driver') === 'cache') {
            $store = Cache::store(config('app.maintenance.store'))->getStore();
            if (! $store instanceof LockProvider) {
                throw new \RuntimeException('The maintenance cache store does not support locks.');
            }
            $lock = $store->lock('maintenance-operation', 30);
        } else {
            $lock = Cache::lock('maintenance-operation', 30);
        }
        $acquired = $lock->get();
        if (! $acquired) {
            throw new \RuntimeException('Another maintenance operation is running.');
        }
        try {
            $callback();
        } finally {
            $lock->release();
        }
    }

    public function invalidateLoginSessions(): void
    {
        User::query()->update([
            'maintenance_session_version' => DB::raw('maintenance_session_version + 1'),
            'remember_token' => null,
        ]);
    }

    private function assertNoConflict(CarbonInterface $start, CarbonInterface $end, ?int $except = null): void
    {
        if (MaintenanceWindow::whereIn('status', ['scheduled', 'in_progress'])->when($except, fn ($q) => $q->whereKeyNot($except))->where('scheduled_start_at', '<', $end)->where('scheduled_end_at', '>', $start)->exists()) {
            throw ValidationException::withMessages(['scheduled_start_at' => 'This window conflicts with another scheduled operation.']);
        }
    }

    private function audit(string $event, ?MaintenanceWindow $window, ?User $actor, string $reason, ?string $operator = null): void
    {
        try {
            activity('maintenance')->causedBy($actor)->withProperties(['operation_id' => $window?->id, 'reason' => $reason, 'operator' => $operator, 'outcome' => $event])->log($event);
        } catch (Throwable $e) {
            Log::error('Maintenance audit write failed', ['operation_id' => $window?->id, 'event' => $event, 'exception' => $e]);
        }
    }
}
