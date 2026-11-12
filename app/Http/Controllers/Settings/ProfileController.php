<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PersonalInformationUpdateRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\AdminAccessGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'status' => $request->session()->get('status'),
            'personalInformation' => [
                'full_name' => $user->full_name ?? '',
                'date_of_birth' => $user->date_of_birth ? Carbon::parse($user->date_of_birth)->toDateString() : '',
                'address' => $user->address ?? '',
                'primary_phone' => $user->primary_phone ?? '',
                'secondary_phone' => $user->secondary_phone ?? '',
            ],
        ]);
    }

    public function editLocalization(Request $request): Response
    {
        return Inertia::render('settings/Localization', [
            'localization' => $request->user()->only(['language', 'timezone', 'date_format', 'time_format', 'number_format', 'currency', 'currency_display']),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function updateLocalization(Request $request): RedirectResponse
    {
        $request->user()->update($request->validate([
            'language' => ['required', 'in:en,id'],
            'timezone' => ['required', 'timezone'],
            'date_format' => ['required', 'in:Y-m-d,d/m/Y,m/d/Y'],
            'time_format' => ['required', 'in:H:i,h:i A'],
            'number_format' => ['required', Rule::in(['1,234.56', '1.234,56', '1 234,56'])],
            'currency' => ['required', 'string', 'size:3', 'alpha', 'uppercase'],
            'currency_display' => ['required', 'in:symbol,code'],
        ]));

        return to_route('localization.edit');
    }

    public function updatePersonalInformation(PersonalInformationUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        activity('users')->event('profile.personal-information-updated')
            ->causedBy($request->user())->performedOn($request->user())
            ->log('profile.personal-information-updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personal information updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['current_password']);
        $request->user()->fill($data);

        $emailChanged = $request->user()->isDirty('email');
        if ($emailChanged) {
            $request->user()->email_verified_at = null;
            $request->user()->setRememberToken(Str::random(60));
        }

        $request->user()->save();
        if ($emailChanged && config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request, AdminAccessGuard $guard): RedirectResponse
    {
        $user = $request->user();
        $avatarPath = null;

        DB::transaction(function () use ($user, $guard, &$avatarPath) {
            $guard->lockAssignments();
            $user->refresh();
            $guard->ensureNotOwner($user);
            if ($user->hasPermission('admin.access')) {
                $guard->ensureAnotherAdministrator($user);
            }
            $avatarPath = $user->avatar_path;
            Auth::logout();
            $user->delete();
        });

        if ($avatarPath) {
            Storage::disk('local')->delete($avatarPath);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
