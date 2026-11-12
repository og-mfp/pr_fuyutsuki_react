<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAccessGuard;
use App\Services\AuditRelationships;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Group::class);

        return Inertia::render('admin/Groups', [
            'groups' => Group::with(Gate::allows('viewAny', User::class)
                ? ['roles.permissions:id,name,slug', 'users:id,name']
                : ['roles.permissions:id,name,slug'])->orderBy('name')->get()
                ->each(fn (Group $group) => $group->setRelation('permissions', $group->roles->flatMap->permissions->unique('id')->values())),
            'permissions' => Permission::orderBy('slug')->get(['id', 'name', 'slug']),
        ]);
    }

    public function show(Group $group): Response
    {
        Gate::authorize('viewAny', Group::class);
        $group->load(Gate::allows('viewAny', User::class)
            ? ['roles.permissions:id,name,slug', 'users:id,name']
            : ['roles.permissions:id,name,slug']);

        return Inertia::render('admin/GroupDetail', [
            'group' => $group,
            'permissions' => Permission::orderBy('slug')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Group::class);
        $data = $request->validate($this->rules());
        Group::create($data)->roles()->create(['name' => 'Default', 'slug' => 'default']);

        return back();
    }

    public function update(Request $request, Group $group, AdminAccessGuard $guard): RedirectResponse
    {
        Gate::authorize('update', $group);
        $guard->ensureCanManageGroup($group);
        $data = $request->validate($this->rules($group));
        if (in_array($group->slug, ['owner', 'std-usr'], true) && $data['slug'] !== $group->slug) {
            throw ValidationException::withMessages(['slug' => 'Default group slugs are reserved.']);
        }
        $group->update($data);

        return back();
    }

    public function destroy(Request $request, Group $group, AdminAccessGuard $guard): RedirectResponse
    {
        Gate::authorize('delete', $group);
        $guard->ensureCanManageGroup($group);
        DB::transaction(function () use ($request, $group, $guard) {
            $guard->lockAssignments();
            $request->user()->refresh();
            $group->refresh();
            Gate::authorize('access-admin');
            Gate::authorize('delete', $group);
            $guard->ensureCanManageGroup($group);
            $guard->ensureCanAssign($request->user(), $group->roles()->with('permissions')->get()->flatMap->permissions->pluck('slug'));
            if ($group->roles()->whereHas('permissions', fn ($q) => $q->where('slug', 'admin.access'))->exists() && $group->users()->exists()) {
                $guard->ensureAnotherAdministrator(excludedGroup: $group);
            }
            $group->delete();
        });

        return back();
    }

    public function storeRole(Request $request, Group $group, AdminAccessGuard $guard): RedirectResponse
    {
        Gate::authorize('assign', Permission::class);
        $guard->ensureCanManageGroup($group);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('rbac_roles')->where('group_id', $group->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $group, $guard, $data) {
            $guard->lockAssignments();
            $request->user()->refresh();
            $group->refresh();
            Gate::authorize('access-admin');
            Gate::authorize('assign', Permission::class);
            $guard->ensureCanManageGroup($group);
            $group->roles()->create($data);
        });

        return back();
    }

    public function syncRolePermissions(Request $request, Group $group, Role $role, AdminAccessGuard $guard): RedirectResponse
    {
        Gate::authorize('assign', Permission::class);
        $guard->ensureCanManageGroup($group);
        abort_unless($role->group_id === $group->id, 404);
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
        ]);
        DB::transaction(function () use ($request, $group, $role, $guard, $data) {
            $guard->lockAssignments();
            $request->user()->refresh();
            $group->refresh();
            $role->refresh();
            Gate::authorize('access-admin');
            Gate::authorize('assign', Permission::class);
            $guard->ensureCanManageGroup($group);
            abort_unless($role->group_id === $group->id, 404);
            $selected = Permission::whereIn('id', $data['permissions'] ?? [])->get(['id', 'slug']);
            $current = $role->permissions()->pluck('slug');
            $changed = $current->diff($selected->pluck('slug'))->merge($selected->pluck('slug')->diff($current));
            $guard->ensureCanAssign($request->user(), $changed);
            abort_if(! $guard->isOwner($request->user())
                && $changed->intersect(['admin.access', 'permissions.assign'])->isNotEmpty(), 403);
            if ($current->contains('admin.access') && ! $selected->contains('slug', 'admin.access') && $group->users()->exists()) {
                $guard->ensureAnotherAdministrator(excludedGroup: $group);
            }
            $previousIds = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
            $role->permissions()->sync($selected->pluck('id'));
            if (! $request->user()->hasPermission('admin.access')) {
                throw ValidationException::withMessages(['permissions' => 'You cannot remove your own administrator access.']);
            }
            AuditRelationships::record($role, 'permissions', $previousIds, $selected->pluck('id')->map(fn ($id) => (int) $id)->all());
        });

        return back();
    }

    public function destroyRole(Request $request, Group $group, Role $role, AdminAccessGuard $guard): RedirectResponse
    {
        Gate::authorize('assign', Permission::class);
        $guard->ensureCanManageGroup($group);
        abort_unless($role->group_id === $group->id, 404);
        abort_if($role->slug === 'default', 403);
        DB::transaction(function () use ($request, $group, $role, $guard) {
            $guard->lockAssignments();
            $request->user()->refresh();
            $group->refresh();
            $role->refresh();
            Gate::authorize('access-admin');
            Gate::authorize('assign', Permission::class);
            $guard->ensureCanManageGroup($group);
            abort_unless($role->group_id === $group->id, 404);
            abort_if($role->slug === 'default', 403);
            $permissions = $role->permissions()->pluck('slug');
            $guard->ensureCanAssign($request->user(), $permissions);
            abort_if(! $guard->isOwner($request->user()) && $permissions->intersect(['admin.access', 'permissions.assign'])->isNotEmpty(), 403);
            if ($permissions->contains('admin.access') && $group->users()->exists()) {
                $guard->ensureAnotherAdministrator(excludedGroup: $group);
            }
            $role->delete();
            if (! $request->user()->hasPermission('admin.access')) {
                throw ValidationException::withMessages(['roles' => 'You cannot remove your own administrator access.']);
            }
        });

        return back();
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(?Group $group = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('groups', 'slug')->ignore($group)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
