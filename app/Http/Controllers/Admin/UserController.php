<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAccessGuard;
use App\Services\AuditRelationships;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class UserController extends Controller
{
    use ProfileValidationRules;

    public function show(User $user): Response
    {
        Gate::authorize('viewAny', User::class);
        $user->load(['groups.roles.permissions:id,name,slug']);
        $user->loadCount(['trustedDevices']);

        return Inertia::render('admin/UserDetail', [
            'user' => $user->only(['id', 'name', 'email', 'email_verified_at', 'frozen_at', 'created_at', 'full_name', 'date_of_birth', 'primary_phone', 'secondary_phone']),
            'groups' => $user->groups->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'slug' => $group->slug,
                'roles' => $group->roles->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permissions' => $role->permissions->map(fn (Permission $permission) => ['id' => $permission->id, 'name' => $permission->name, 'slug' => $permission->slug])->all(),
                ])->all(),
            ])->all(),
            'permissionSlugs' => $user->permissionSlugs(),
            'trustedDeviceCount' => $user->trusted_devices_count,
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'group_id' => ['nullable', 'integer', Rule::exists('groups', 'id')],
            'frozen' => ['nullable', Rule::in(['all', 'frozen', 'active'])],
            'verification' => ['nullable', Rule::in(['all', 'verified', 'unverified'])],
            'sort' => ['nullable', Rule::in(['name', 'email', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
        $filters = [
            'search' => trim($filters['search'] ?? ''),
            'group_id' => $filters['group_id'] ?? '',
            'frozen' => $filters['frozen'] ?? 'all',
            'verification' => $filters['verification'] ?? 'all',
            'sort' => $filters['sort'] ?? 'name',
            'direction' => $filters['direction'] ?? 'asc',
            'per_page' => (int) ($filters['per_page'] ?? 25),
        ];

        return Inertia::render('admin/Users', [
            'users' => fn () => User::select(['id', 'name', 'email', 'email_verified_at', 'frozen_at', 'created_at'])
                ->with('groups:id,name,slug')
                ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%')))
                ->when($filters['group_id'] !== '', fn ($query) => $query->whereHas('groups', fn ($groups) => $groups->whereKey($filters['group_id'])))
                ->when($filters['frozen'] === 'frozen', fn ($query) => $query->whereNotNull('frozen_at'))
                ->when($filters['frozen'] === 'active', fn ($query) => $query->whereNull('frozen_at'))
                ->when($filters['verification'] === 'verified', fn ($query) => $query->whereNotNull('email_verified_at'))
                ->when($filters['verification'] === 'unverified', fn ($query) => $query->whereNull('email_verified_at'))
                ->addSelect([
                    'latest_security_event' => Activity::select('event')->whereColumn('subject_id', 'users.id')
                        ->where('subject_type', User::class)->where('log_name', 'security')->orderByDesc('id')->limit(1),
                    'latest_security_at' => Activity::select('created_at')->whereColumn('subject_id', 'users.id')
                        ->where('subject_type', User::class)->where('log_name', 'security')->orderByDesc('id')->limit(1),
                ])
                ->orderBy($filters['sort'], $filters['direction'])
                ->paginate($filters['per_page'])->withQueryString(),
            'filters' => $filters,
            'groups' => function () use ($request) {
                $actorPermissions = $request->user()->permissionSlugs();

                return Group::with('roles.permissions:id,slug')->orderBy('name')->get(['id', 'name', 'slug'])->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'assignable' => $group->slug !== 'owner'
                        && $group->roles->flatMap->permissions->pluck('slug')->unique()->diff($actorPermissions)->isEmpty(),
                ]);
            },
            'passwordRules' => fn () => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(Request $request, AdminAccessGuard $guard): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $data = $request->validate([
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'groups' => ['array'],
            'groups.*' => ['integer', 'distinct', Rule::exists('groups', 'id')],
        ]);
        abort_if(in_array('owner', Group::whereIn('id', array_map('intval', $data['groups'] ?? []))->pluck('slug')->all(), true), 403);
        DB::transaction(function () use ($request, $data, $guard) {
            $guard->lockAssignments();
            $request->user()->refresh();
            Gate::authorize('access-admin');
            Gate::authorize('create', User::class);
            $groups = array_map('intval', $data['groups'] ?? [Group::where('slug', 'std-usr')->firstOrFail()->id]);
            $this->authorizeGroups($request, $groups, $guard);
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->groups()->sync($groups);
            AuditRelationships::record($user, 'groups', [], $user->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all());
        });

        return back();
    }

    public function update(Request $request, User $user, AdminAccessGuard $guard): RedirectResponse
    {
        $guard->ensureNotOwner($user);
        Gate::authorize('update', $user);
        $data = $request->validate([
            ...$this->profileRules($user->id),
            'groups' => ['array'],
            'groups.*' => ['integer', 'distinct', Rule::exists('groups', 'id')],
            'groups_present' => ['sometimes', 'boolean'],
        ]);
        DB::transaction(function () use ($request, $user, $data, $guard) {
            $guard->lockAssignments();
            $request->user()->refresh();
            $user->refresh();
            Gate::authorize('access-admin');
            $guard->ensureNotOwner($user);
            Gate::authorize('update', $user);
            if ($data['email'] !== $user->email) {
                abort_unless($guard->isOwner($request->user()), 403);
                Gate::authorize('resetPassword', $user);
                $request->validate(['current_password' => ['required', 'current_password']]);
            }
            $currentIds = $user->groups()->pluck('groups.id')->all();
            $ids = array_map('intval', array_key_exists('groups', $data) || ($data['groups_present'] ?? false)
                ? ($data['groups'] ?? []) : $currentIds);
            $changedIds = array_values(array_unique([...array_diff($ids, $currentIds), ...array_diff($currentIds, $ids)]));
            $this->authorizeGroups($request, $changedIds, $guard);
            if ($user->hasPermission('admin.access') && ! Group::whereIn('id', $ids)->whereHas('roles.permissions', fn ($q) => $q->where('slug', 'admin.access'))->exists()) {
                if ($request->user()->is($user)) {
                    throw ValidationException::withMessages(['groups' => 'You cannot remove your own administrator access.']);
                }
                $guard->ensureAnotherAdministrator($user);
            }
            $user->fill(['name' => $data['name'], 'email' => $data['email']]);
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();
            $user->groups()->sync($ids);
            AuditRelationships::record($user, 'groups', array_map('intval', $currentIds), $ids);
        });

        return back();
    }

    public function destroy(User $user, AdminAccessGuard $guard): RedirectResponse
    {
        $guard->ensureNotOwner($user);
        Gate::authorize('delete', $user);
        $avatarPath = null;
        DB::transaction(function () use ($user, $guard, &$avatarPath) {
            $guard->lockAssignments();
            Auth::user()->refresh();
            $user->refresh();
            Gate::authorize('access-admin');
            $guard->ensureNotOwner($user);
            Gate::authorize('delete', $user);
            if ($user->hasPermission('admin.access')) {
                $guard->ensureAnotherAdministrator($user);
            }
            $avatarPath = $user->avatar_path;
            $user->delete();
        });

        if ($avatarPath) {
            Storage::disk('local')->delete($avatarPath);
        }

        return back();
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);
        $status = PasswordBroker::broker(config('fortify.passwords'))->sendResetLink(['email' => $user->email]);

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['password_reset' => __($status)]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password reset link sent.']);

        return back();
    }

    public function sendVerificationEmail(User $user, AdminAccessGuard $guard): RedirectResponse
    {
        $guard->ensureNotOwner($user);
        Gate::authorize('update', $user);
        abort_if($user->hasVerifiedEmail(), 409);
        $user->sendEmailVerificationNotification();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Verification email sent.']);

        return back();
    }

    public function freeze(User $user, AdminAccessGuard $guard): RedirectResponse
    {
        $guard->ensureNotOwner($user);
        Gate::authorize('freeze', $user);
        DB::transaction(function () use ($user, $guard) {
            $guard->lockAssignments();
            Auth::user()->refresh();
            $user->refresh();
            Gate::authorize('access-admin');
            $guard->ensureNotOwner($user);
            Gate::authorize('freeze', $user);
            if ($user->frozen_at) {
                return;
            }
            if ($user->hasPermission('admin.access')) {
                $guard->ensureAnotherAdministrator($user);
            }
            $user->frozen_at = now();
            $user->setRememberToken(Str::random(60));
            $user->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)->delete();
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account frozen.']);

        return back();
    }

    public function unfreeze(User $user, AdminAccessGuard $guard): RedirectResponse
    {
        $guard->ensureNotOwner($user);
        Gate::authorize('unfreeze', $user);
        DB::transaction(function () use ($user, $guard) {
            $guard->lockAssignments();
            Auth::user()->refresh();
            $user->refresh();
            Gate::authorize('access-admin');
            $guard->ensureNotOwner($user);
            Gate::authorize('unfreeze', $user);
            $user->forceFill(['frozen_at' => null])->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account unfrozen.']);

        return back();
    }

    /** @param array<int> $ids */
    private function authorizeGroups(Request $request, array $ids, AdminAccessGuard $guard): void
    {
        if ($ids === []) {
            return;
        }
        Gate::authorize('assign', Permission::class);
        abort_if(Group::whereIn('id', $ids)->where('slug', 'owner')->exists(), 403);
        $guard->ensureCanAssign($request->user(), Permission::whereHas('roles.group', fn ($query) => $query->whereIn('groups.id', $ids))->pluck('slug'));
    }
}
