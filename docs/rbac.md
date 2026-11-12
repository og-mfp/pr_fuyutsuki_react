> **React port note:** Backend details below are adapted from the Svelte project and checked against the corresponding server implementation. React UI parity and deployment behavior have not been verified end to end; test the relevant flows in your environment.

# Authorization in Fuyutsuki

Fortify handles login, registration, password reset, email verification, two-factor authentication, passkeys, and sessions. It does not decide administrative access. Laravel Gates and Policies do that using users, groups, roles, permissions, and their relationship tables. Users may belong to several groups; permissions on the roles of those groups combine for authorization. Frozen users do not pass `hasPermission`. Only permissions in the code-owned catalog count.

Permissions are code-owned capabilities named `resource.action`. Define a new module in `config/permissions.php`, for example:

```php
'projects' => ['projects.view', 'projects.create', 'projects.update', 'projects.delete'],
```

Run `php artisan permissions:sync` after deployment. The command inserts and updates definitions, and reports stale rows without deleting them. Run `php artisan db:seed --class=RbacSeeder` to ensure the Owner group has all current catalog permissions. First-time setup records the Owner and grants its initial privileged membership; the seeder does not create accounts or credentials.

Create `App\Policies\ProjectPolicy` with methods such as:

```php
public function update(User $user, Project $project): bool
{
    return $user->hasPermission('projects.update');
}
```

Use `Gate::authorize('update', $project)` in the controller. For capabilities outside a model policy, define a Gate in `AppServiceProvider`, as `access-admin` does. Place admin routes behind `auth`, `verified`, and `can:access-admin`; still authorize each controller action with its policy. The `admin.access` capability opens the area, while each resource permission controls its own action.

In React, `resources/js/pages/admin/Users.tsx` checks `auth.user.permissions.includes(permission)` to show actions. This only controls display; the Laravel endpoint must authorize the request. An administrative module needs a catalog entry, policy, authorized routes and controller, and a permission-aware navigation link.

The native migration preserves previous Spatie tables for audit and rollback. It copies all role memberships. Roles with the old `manage access` permission receive the application catalog; direct grants become membership in a `legacy-direct-admin` group. Legacy custom permission names have no automatic mapping to code-owned capabilities and remain in the preserved tables. Review them before deployment if the installation used custom permissions.

Default groups: `owner` (the single identity recorded in installation metadata, with all catalog permissions) and `std-usr` (Standard User, with no administrative permissions). Registration and administrative creation without explicit groups assign Standard User. The owner cannot be assigned or changed through ordinary user or group management. Recover the owner password through the interactive `php artisan owner:recover <email>` console procedure. Custom groups may grant permissions within the actor's own permissions.

Existing administrator groups and their assignments are preserved when ownership is bound; the application no longer treats their names as default roles. The ownership migration cannot be rolled back after installation because that would remove the recorded owner.

The React port also has group roles and role-permission routes; inspect `Group`, `Role`, `GroupController` and `GroupPolicy` when extending authorization rather than assuming group permissions alone describe every assignment.
