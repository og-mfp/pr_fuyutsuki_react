<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Permission::class);
        $catalog = Config::array('permissions');
        $permissions = Permission::with('roles.group:id,name')->orderBy('slug')->get()
            ->each(fn (Permission $permission) => $permission->setRelation('groups', $permission->roles->pluck('group')->filter()->unique('id')->values()));

        return Inertia::render('admin/Permissions', [
            'catalog' => $catalog,
            'permissions' => $permissions,
            'stale' => $permissions->pluck('slug')->diff(Permission::catalogSlugs())->values(),
        ]);
    }
}
