<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Services\SettingsRegistry;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function index(SettingsRegistry $registry, SettingsService $settings): Response
    {
        Gate::authorize('manage-system-settings');
        $categories = [];
        foreach ($registry->all() as $definition) {
            if ($definition['category'] !== 'health_maintenance') {
                $categories[$definition['category']] = true;
            }
        }
        $values = [];
        foreach (array_keys($categories) as $category) {
            if ($category === 'smtp') {
                $values[$category] = [];
                foreach ($registry->all() as $key => $definition) {
                    if ($definition['category'] === 'smtp' && $key !== 'smtp.password') {
                        $values[$category][substr($key, 5)] = $settings->get($key);
                    }
                }
            } else {
                $values[$category] = $settings->category($category);
            }
        }

        return Inertia::render('admin/Configuration', [
            'settings' => $values,
            'defaultRoleOptions' => Group::where('slug', '!=', 'owner')
                ->whereDoesntHave('roles.permissions')
                ->orderBy('name')
                ->get(['name', 'slug']),
            'minimumPasswordLength' => app()->isProduction() ? 12 : 8,
        ]);
    }

    public function update(Request $request, string $category, SettingsRegistry $registry, SettingsService $settings): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $definitions = array_filter($registry->all(), fn ($definition) => $definition['category'] === $category);
        abort_if($definitions === [], 404);
        $allowed = array_map(fn ($key) => substr($key, strlen($category) + 1), array_keys($definitions));
        if ($category === 'authentication') {
            $allowed = array_values(array_diff($allowed, ['authentication_lockdown', 'lockdown_reason']));
        }
        $removeFields = $category === 'general' ? ['remove_logo', 'remove_favicon'] : ($category === 'smtp' ? ['clear_password'] : []);
        $unknown = array_diff(array_keys($request->except('_token', '_method', 'current_password')), [...$allowed, ...$removeFields]);
        if ($unknown !== []) {
            throw ValidationException::withMessages([reset($unknown) => 'Unknown setting.']);
        }
        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'clear_password' => ['sometimes', 'boolean'],
        ]);
        $values = $request->only($allowed);
        if ($category === 'smtp') {
            if ($request->boolean('clear_password')) {
                $values['password'] = '';
            } elseif (($values['password'] ?? null) === null || $values['password'] === '') {
                unset($values['password']);
            }
        }
        $removedImages = [];
        $attachmentDiskName = config('filesystems.attachment_disk', 'public');
        $attachmentDisk = Storage::disk($attachmentDiskName);
        foreach (['logo', 'favicon'] as $image) {
            if ($request->hasFile($image)) {
                $path = $request->file($image)->store('branding', $attachmentDiskName);
                if ($path === false) {
                    throw ValidationException::withMessages([$image => 'Unable to store the image.']);
                }
                $values[$image] = $attachmentDisk->url($path);
            } elseif ($category === 'general' && $request->boolean('remove_'.$image)) {
                $values[$image] = '';
                $removedImages[] = $settings->get('general.'.$image);
            } elseif (isset($values[$image]) && is_string($values[$image])) {
                unset($values[$image]);
            }
        }
        if ($values === []) {
            throw ValidationException::withMessages(['settings' => 'Select a setting to update.']);
        }
        if ($category === 'authentication') {
            if ($request->boolean('concurrent_sessions') && config('session.driver') !== 'database') {
                throw ValidationException::withMessages(['concurrent_sessions' => 'Concurrent-session restriction requires database sessions.']);
            }
            $mfaEnabled = $request->has('mfa_enabled') ? $request->boolean('mfa_enabled') : $settings->get('authentication.mfa_enabled');
            $mfaRequirement = $values['mfa_requirement'] ?? $settings->get('authentication.mfa_requirement');
            if (! $mfaEnabled && $mfaRequirement === 'required') {
                throw ValidationException::withMessages(['mfa_requirement' => 'Required MFA needs MFA to be enabled.']);
            }
        }
        if (collect(array_keys($values))->contains(fn ($key) => $registry->definition($category.'.'.$key)['sensitive'])) {
            $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
            $minutes = (int) $settings->get('authentication.reauthentication_minutes');
            if ($minutes === 0 || $confirmedAt < time() - $minutes * 60) {
                $request->validate(['current_password' => ['required', 'current_password']]);
                $request->session()->put('auth.password_confirmed_at', time());
            }
        }
        $settings->update($category, $values, $request->user());
        foreach ($removedImages as $url) {
            $prefix = rtrim($attachmentDisk->url(''), '/').'/';
            if (str_starts_with((string) $url, $prefix)) {
                $path = substr((string) $url, strlen($prefix));
                if (preg_match('#^branding/[^/]+$#', $path)) {
                    $attachmentDisk->delete($path);
                }
            }
        }

        return back();
    }

    public function emergency(Request $request, SettingsService $settings): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $data = $request->validate([
            'action' => ['required', 'in:logout,lockdown,unlock'],
            'current_password' => ['required', 'current_password'],
            'reason' => ['required_if:action,lockdown', 'prohibited_unless:action,lockdown', 'string', 'max:500'],
        ]);
        if (in_array($data['action'], ['logout', 'lockdown'], true)) {
            // shortcut: bulk revocation uses database sessions; add per-user session versions for other drivers.
            if (config('session.driver') !== 'database') {
                throw ValidationException::withMessages(['action' => 'Logging out all accounts requires database sessions.']);
            }
            $ownerIds = DB::table('installation_metadata')->where('id', 1)->pluck('owner_id');
            DB::transaction(function () use ($ownerIds, $data, $settings, $request) {
                if ($data['action'] === 'lockdown') {
                    $settings->update('authentication', [
                        'authentication_lockdown' => true,
                        'lockdown_reason' => $data['reason'],
                    ], $request->user());
                }
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->whereNotNull('user_id')->whereNotIn('user_id', $ownerIds)->delete();
                User::whereNotIn('id', $ownerIds)->update(['remember_token' => null]);
            });
            activity('security')->causedBy($request->user())->log('all non-owner sessions revoked'.($data['action'] === 'lockdown' ? ' for lockdown' : ''));
        } else {
            $settings->update('authentication', ['authentication_lockdown' => false, 'lockdown_reason' => ''], $request->user());
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => match ($data['action']) {
            'logout' => 'Other accounts have been logged out.',
            'lockdown' => 'Authentication lockdown is active.',
            'unlock' => 'Authentication lockdown has ended.',
            default => throw new \LogicException('Invalid emergency action.'),
        }]);

        return back();
    }

    public function testNotification(Request $request, SettingsService $settings): RedirectResponse
    {
        Gate::authorize('manage-system-settings');
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            Mail::raw($settings->get('notifications.template_body'), function ($message) use ($data, $settings) {
                $message->to($data['email'])->subject($settings->get('notifications.template_subject'));
            });
        } catch (\Throwable $exception) {
            Log::error('Test email delivery failed.', ['exception' => $exception]);
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Test email could not be sent. Check the mail configuration and try again.']);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Test notification sent.']);

        return back();
    }
}
