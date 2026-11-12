<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceWindow;
use App\Services\AdminAccessGuard;
use App\Services\ApplicationHealthService;
use App\Services\MaintenanceManagementService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Throwable;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $maintenanceWarning = false;
        try {
            if ($user && ! $request->session()->get('maintenance_warning_shown') && app(AdminAccessGuard::class)->isOwner($user)) {
                $state = app(MaintenanceManagementService::class)->inspect();
                $maintenanceWarning = ! $state['consistent'] && ! $state['acknowledged'];
                if ($maintenanceWarning) {
                    $request->session()->put('maintenance_warning_shown', true);
                }
            }
        } catch (Throwable) {
            // An unavailable source cannot establish an inconsistency.
        }
        try {
            $settings = app(SettingsService::class);
            $general = $settings->category('general');
            $sharedSettings = $settings->getMany([
                'health_maintenance.announcement_lead_hours',
                'users.registration_mode',
                'authentication.authentication_lockdown',
            ]);
            $lead = (int) $sharedSettings['health_maintenance.announcement_lead_hours'];
            $announcements = MaintenanceWindow::where('status', 'scheduled')->where('published', true)
                ->whereBetween('scheduled_start_at', [now(), now()->addHours($lead)])
                ->orderBy('scheduled_start_at')->get(['id', 'title', 'public_message', 'scheduled_start_at', 'scheduled_end_at']);
            $name = $general['display_name'];
            $branding = $general;
            $canRegister = $sharedSettings['users.registration_mode'] === 'public'
                && ! $sharedSettings['authentication.authentication_lockdown'];
        } catch (Throwable) {
            $announcements = collect();
            $name = config('app.name');
            $branding = [];
            $canRegister = false;
        }

        return [
            ...parent::share($request),
            'name' => $name,
            'appVersion' => config('app.version'),
            'maintenanceActive' => app(ApplicationHealthService::class)->maintenanceStatus()['active'],
            'maintenanceWarning' => $maintenanceWarning,
            'maintenanceAnnouncements' => $announcements,
            'branding' => $branding,
            'canRegister' => $canRegister,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_path ? route('profile.avatar.show') : null,
                    'localization' => $user->only(['language', 'timezone', 'date_format', 'time_format', 'number_format', 'currency', 'currency_display']),
                    'email_verified_at' => $user->email_verified_at,
                    'two_factor_enabled' => (bool) $user->two_factor_confirmed_at,
                    'groups' => $user->groups()->pluck('slug'),
                    'permissions' => $user->permissionSlugs(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
