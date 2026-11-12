<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    private const SUBJECTS = ['user' => User::class, 'group' => Group::class, 'permission' => Permission::class];

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'actor' => ['nullable', 'integer', 'min:1'],
            'event' => ['nullable', 'string', 'max:80', 'regex:/^[a-z.]+$/'],
            'risk_level' => ['nullable', Rule::in(['medium', 'high'])],
            'category' => ['nullable', Rule::in(['users', 'security', 'authorization', 'system'])],
            'subject' => ['nullable', Rule::in(array_keys(self::SUBJECTS))],
            'subject_id' => ['nullable', 'integer', 'min:1', 'required_with:subject'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    /** @param array<string, mixed> $filters
     * @return Builder<Activity>
     */
    private function query(array $filters): Builder
    {
        return Activity::query()->with('causer:id,name')->when($filters['from'] ?? null,
            fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['actor'] ?? null, fn ($q, $id) => $q->where('causer_type', User::class)->where('causer_id', $id))
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['risk_level'] ?? null, fn ($q, $level) => $q->whereJsonContains('properties->level', $level))
            ->when($filters['category'] ?? null, fn ($q, $name) => $q->where('log_name', $name))
            ->when($filters['subject'] ?? null, fn ($q, $type) => $q->where('subject_type', self::SUBJECTS[$type]))
            ->when($filters['subject_id'] ?? null, fn ($q, $id) => $q->where('subject_id', $id))
            ->orderByDesc('id');
    }

    /** @return array<string, mixed> */
    private function safe(Activity $activity): array
    {
        $props = $activity->properties;
        $allowed = match ($activity->subject_type) {
            User::class => ['name', 'email', 'email_verified_at', 'frozen_at'],
            Group::class, Permission::class => ['name', 'slug', 'description'],
            default => [],
        };
        $old = array_intersect_key((array) $props->get('old', []), array_flip($allowed));
        $new = array_intersect_key((array) $props->get('attributes', []), array_flip($allowed));
        if ($activity->log_name === 'authorization' && in_array($props->get('relation'), ['groups', 'permissions'], true)) {
            $old = ['ids' => array_values(array_filter((array) $props->get('old', []), 'is_int'))];
            $new = ['ids' => array_values(array_filter((array) $props->get('attributes', []), 'is_int'))];
        }

        return [
            'id' => $activity->id,
            'timestamp' => $activity->created_at?->toIso8601String(),
            'actor' => $activity->causer instanceof User ? ['id' => $activity->causer->id, 'name' => $activity->causer->name] : null,
            'event' => $activity->event,
            'category' => $activity->log_name,
            'subject' => array_search($activity->subject_type, self::SUBJECTS, true) ?: null,
            'subject_id' => $activity->subject_id,
            'ip' => $activity->log_name === 'security' ? $props->get('ip') : null,
            'risk_level' => $activity->log_name === 'security' ? $props->get('level') : null,
            'risk_score' => $activity->log_name === 'security' ? $props->get('score') : null,
            'risk_signals' => $activity->log_name === 'security' ? $props->get('signals', []) : [],
            'old' => $old,
            'new' => $new,
        ];
    }

    public function index(Request $request): Response
    {
        Gate::authorize('audit.view');
        $filters = $this->filters($request);
        $activities = $this->query($filters)->paginate(25)->withQueryString();
        $activities->through(fn (Activity $activity) => $this->safe($activity));

        return Inertia::render('admin/ActivityLogs', ['activities' => $activities, 'filters' => $filters,
            'canExport' => $request->user()->hasPermission('audit.export')]);
    }

    /** @return array<string, mixed> */
    public function show(Activity $activity): array
    {
        Gate::authorize('audit.view');
        $activity->load('causer:id,name');

        return $this->safe($activity);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('audit.export');
        $query = $this->query($this->filters($request));
        $maxId = (clone $query)->max('id');
        $query->where('id', '<=', $maxId ?? 0);

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Could not open CSV output.');
            }
            fputcsv($output, ['Timestamp', 'Actor ID', 'Actor', 'Event', 'Category', 'Subject', 'Subject ID', 'IP', 'Before', 'After']);
            foreach ($query->lazyByIdDesc(500, 'id', 'id') as $activity) {
                $row = $this->safe($activity);
                $actor = $row['actor']['name'] ?? '';
                if (preg_match('/^[=+@\-\t\r]/', $actor)) {
                    $actor = "'".$actor;
                }
                fputcsv($output, [$row['timestamp'], $row['actor']['id'] ?? '', $actor,
                    $row['event'], $row['category'], $row['subject'], $row['subject_id'], $row['ip'],
                    json_encode($row['old']), json_encode($row['new'])]);
            }
            fclose($output);
        }, 'activity-logs.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
