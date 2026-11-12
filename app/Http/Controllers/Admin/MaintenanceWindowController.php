<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagEvent;
use App\Models\MaintenanceWindow;
use App\Services\ApplicationHealthService;
use App\Services\MaintenanceManagementService;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceWindowController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-system-settings');
        $timezone = $request->user()->timezone;
        $dateTimeFormat = $request->user()->date_format.' '.$request->user()->time_format.' T';
        $search = $request->string('search')->toString();
        $sort = $request->query('sort', 'schedule');
        $direction = $request->query('direction', 'desc');
        $sortable = ['id' => 'id', 'title' => 'title', 'reason' => 'reason', 'schedule' => 'scheduled_start_at', 'status' => 'status', 'reference' => 'deployment_ref'];
        $sort = is_string($sort) && isset($sortable[$sort]) ? $sort : 'schedule';
        $direction = $direction === 'asc' ? 'asc' : 'desc';
        $windows = MaintenanceWindow::with(['creator:id,name', 'starter:id,name', 'completer:id,name'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('title', 'ilike', "%{$search}%")->orWhere('type', 'ilike', "%{$search}%")->orWhere('reason', 'ilike', "%{$search}%")->orWhere('status', 'ilike', "%{$search}%")))
            ->orderBy($sortable[$sort], $direction)->paginate(15)->withQueryString()->through(function (MaintenanceWindow $window) use ($timezone, $dateTimeFormat) {
                return [...$window->toArray(),
                    'start_local' => CarbonImmutable::parse($window->scheduled_start_at)->timezone($timezone)->format('Y-m-d\\TH:i'),
                    'end_local' => CarbonImmutable::parse($window->scheduled_end_at)->timezone($timezone)->format('Y-m-d\\TH:i'),
                    'scheduled_display' => CarbonImmutable::parse($window->scheduled_start_at)->timezone($timezone)->format($dateTimeFormat).' – '.CarbonImmutable::parse($window->scheduled_end_at)->timezone($timezone)->format($dateTimeFormat),
                    'actual_display' => ($window->started_at?->timezone($timezone)->format($dateTimeFormat) ?? '—').' – '.($window->ended_at?->timezone($timezone)->format($dateTimeFormat) ?? '—'),
                    'recovery_display' => $window->recovery_observed_at?->timezone($timezone)->format($dateTimeFormat),
                    'closed_display' => $window->closed_at?->timezone($timezone)->format($dateTimeFormat),
                ];
            });
        $mode = app(ApplicationHealthService::class)->maintenanceStatus();
        $state = app(MaintenanceManagementService::class)->inspect();
        $reconciliation = $state['reconciliation'];
        $nativeEvent = $state['native_event'];
        $activeReason = $state['operation']->reason ?? $mode['reason'];
        $next = MaintenanceWindow::where('status', 'scheduled')->where('scheduled_start_at', '>', now())->orderBy('scheduled_start_at')->first(['title', 'scheduled_start_at']);

        return Inertia::render('admin/Maintenance', [
            'health' => app(ApplicationHealthService::class)->snapshot(),
            'diagnosticEvents' => DiagEvent::query()->latest('occured_at')->limit(25)->get([
                'reference_id', 'error_code', 'http_status', 'severity', 'route_name',
                'exception_class', 'summary', 'user_id', 'occured_at',
            ])->map(fn (DiagEvent $event) => [
                ...$event->only(['reference_id', 'error_code', 'http_status', 'severity', 'route_name', 'exception_class', 'summary', 'user_id']),
                'occured_at' => $event->occured_at->timezone($timezone)->format($dateTimeFormat),
            ]),
            'settings' => app(SettingsService::class)->category('health_maintenance'),
            'windows' => $windows,
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'upcoming' => MaintenanceWindow::where('status', 'scheduled')->where('scheduled_start_at', '>', now())->count(),
            'active' => $mode['active'],
            'activeReason' => $activeReason,
            'maintenanceState' => $state['state'],
            'maintenanceConsistent' => $state['consistent'],
            'maintenanceAcknowledged' => $state['acknowledged'],
            'unresolvedIncidents' => $state['unresolved_incidents'],
            'incompleteOperations' => $state['registered_operation_ids'],
            'nextIncident' => $state['incident'] ? ['command' => $state['incident']->command, 'observed_at' => $state['incident']->observed_at->timezone($timezone)->format($dateTimeFormat)] : null,
            'lastNativeEvent' => $nativeEvent ? [
                'command' => $nativeEvent->command,
                'exit_code' => $nativeEvent->exit_code,
                'actual_active' => $nativeEvent->actual_active,
                'observed_at' => $nativeEvent->observed_at->timezone($timezone)->format($dateTimeFormat),
            ] : null,
            'lastReconciliation' => $reconciliation ? [
                'explanation' => $reconciliation->explanation,
                'observed_at' => $reconciliation->observed_at->timezone($timezone)->format($dateTimeFormat),
                'operator' => $reconciliation->operator_identifier ?? $reconciliation->reconciler->name ?? 'CLI operator not identified',
            ] : null,
            'next' => $next ? ['title' => $next->title, 'scheduled_start_at' => $next->scheduled_start_at->timezone($timezone)->format($dateTimeFormat)] : null,
            'lastCompleted' => MaintenanceWindow::where('status', 'completed')->latest('ended_at')->first()?->ended_at?->timezone($timezone)->format($dateTimeFormat),
            'historyCount' => MaintenanceWindow::whereIn('status', ['completed', 'cancelled', 'failed'])->count(),
        ]);
    }

    public function store(Request $request, MaintenanceManagementService $service): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $request->validate(['current_password' => ['required', 'current_password']]);
        $service->create($this->validated($request), $request->user(), $request->user()->timezone);

        return back();
    }

    public function update(Request $request, MaintenanceWindow $window, MaintenanceManagementService $service): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $request->validate(['current_password' => ['required', 'current_password']]);
        $service->update($window, $this->validated($request), $request->user(), $request->user()->timezone);

        return back();
    }

    public function destroy(Request $request, MaintenanceWindow $window, MaintenanceManagementService $service): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $request->validate(['current_password' => ['required', 'current_password']]);
        $service->cancel($window, $request->user());

        return back();
    }

    public function reconcile(Request $request, MaintenanceManagementService $service): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $data = $request->validate([
            'explanation' => ['required', 'string', 'max:4000'],
            'current_password' => ['required', 'current_password'],
        ]);
        $service->reconcile($data['explanation'], $request->user());

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'type' => ['required', 'in:infrastructure,deployment,security,database,other'],
            'reason' => ['required', 'string', 'max:2000'], 'public_message' => ['required', 'string', 'max:2000'],
            'scheduled_start_at' => ['required', 'date'], 'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'deployment_ref' => ['nullable', 'string', 'max:160'], 'operator_notes' => ['nullable', 'string', 'max:4000'], 'published' => ['sometimes', 'boolean'],
        ]);
        $data['published'] = $request->boolean('published');

        return $data;
    }
}
