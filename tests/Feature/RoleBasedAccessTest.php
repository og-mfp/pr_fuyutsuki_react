<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Services\AdminAccessGuard;
use App\Services\InstallationIntegrityService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $slugs = []): User
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Test admin', 'slug' => 'test-admin-'.$user->id]);
        $group->defaultRole()->permissions()->sync(Permission::whereIn('slug', ['admin.access', ...$slugs])->pluck('id'));
        $user->groups()->attach($group);

        return $user;
    }

    public function test_admin_entry_requires_authentication_and_admin_access(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_rbac_denials_are_logged_and_remain_forbidden(): void
    {
        $user = User::factory()->create();
        Log::spy();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();

        Log::shouldHaveReceived('warning')->once()->with('Admin RBAC denial.', \Mockery::on(
            fn (array $context) => $context['user_id'] === $user->id && $context['route'] === 'admin.users.index',
        ));
    }

    public function test_user_actions_follow_policy_permissions(): void
    {
        $viewer = $this->admin(['users.view']);
        $this->actingAs($viewer)->get(route('admin.users.index'))->assertOk();
        $this->post(route('admin.users.store'), ['name' => 'Denied'])->assertForbidden();

        $creator = $this->admin(['users.create', 'permissions.assign']);
        $this->actingAs($creator)->post(route('admin.users.store'), [
            'name' => 'New user', 'email' => 'new@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect();
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->put(route('admin.users.update', $user), ['name' => 'Changed', 'email' => $user->email])->assertForbidden();
        $this->delete(route('admin.users.destroy', $user))->assertForbidden();
    }

    public function test_user_manager_cannot_take_over_a_more_privileged_account(): void
    {
        $manager = $this->admin(['users.update']);
        $target = $this->admin(['users.delete']);
        $ordinary = User::factory()->create();

        $this->actingAs($manager)->put(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => 'manager-controlled@example.com',
        ])->assertForbidden();
        $this->put(route('admin.users.update', $ordinary), [
            'name' => $ordinary->name, 'email' => 'manager-controlled@example.com',
        ])->assertForbidden();
        $this->post(route('admin.users.password-reset', $target))->assertForbidden();
        $this->put(route('admin.users.freeze', $target))->assertForbidden();

        $this->assertSame($target->email, $target->fresh()->email);
        $this->assertSame($ordinary->email, $ordinary->fresh()->email);
        $this->assertNull($target->fresh()->frozen_at);
    }

    public function test_frozen_account_retains_assigned_privileges_for_management_checks(): void
    {
        $manager = $this->admin(['users.update', 'users.delete']);
        $target = $this->admin(['groups.delete']);
        $target->forceFill(['frozen_at' => now()])->save();

        $this->actingAs($manager)->delete(route('admin.users.destroy', $target))->assertForbidden();
        $this->delete(route('admin.users.unfreeze', $target))->assertForbidden();
        $this->assertNotNull($target->fresh()->frozen_at);
        $this->assertSame([], $target->fresh()->permissionSlugs());
    }

    public function test_freeze_and_unfreeze_flash_account_status_toasts(): void
    {
        $manager = $this->admin(['users.update']);
        $target = User::factory()->create();
        $this->actingAs($manager);

        $this->put(route('admin.users.freeze', $target))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Account frozen.');
        $this->delete(route('admin.users.unfreeze', $target))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Account unfrozen.');
    }

    public function test_account_actions_recheck_privileges_after_locking(): void
    {
        foreach (['destroy', 'freeze', 'unfreeze'] as $action) {
            $manager = $this->admin(['users.update', 'users.delete']);
            $target = User::factory()->create();
            if ($action === 'unfreeze') {
                $target->forceFill(['frozen_at' => now()])->save();
            }
            $group = Group::create(['name' => 'Restricted '.$target->id, 'slug' => 'restricted-'.$target->id]);
            $group->defaultRole()->permissions()->attach(Permission::where('slug', 'groups.delete')->firstOrFail());
            $guard = \Mockery::mock(AdminAccessGuard::class)->makePartial();
            $guard->shouldReceive('lockAssignments')->once()->andReturnUsing(fn () => $target->groups()->attach($group));
            $this->app->instance(AdminAccessGuard::class, $guard);

            $this->actingAs($manager);
            $response = $action === 'freeze'
                ? $this->put(route('admin.users.freeze', $target))
                : $this->delete(route('admin.users.'.$action, $target));
            $response->assertForbidden();
            $this->assertNotNull($target->fresh());
            $this->assertSame($action === 'unfreeze', $target->fresh()->frozen_at !== null);
        }
    }

    public function test_group_permission_changes_recheck_assignment_authority_after_locking(): void
    {
        $manager = $this->admin(['permissions.assign', 'users.view']);
        $group = Group::create(['name' => 'Readers', 'slug' => 'race-readers']);
        $guard = \Mockery::mock(AdminAccessGuard::class)->makePartial();
        $guard->shouldReceive('lockAssignments')->once()->andReturnUsing(fn () => $manager->groups()->firstOrFail()
            ->defaultRole()->permissions()->detach(Permission::where('slug', 'permissions.assign')->firstOrFail()));
        $this->app->instance(AdminAccessGuard::class, $guard);

        $this->actingAs($manager)->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), [
            'permissions' => [Permission::where('slug', 'users.view')->firstOrFail()->id],
        ])->assertForbidden();
        $this->assertCount(0, $group->fresh()->defaultRole()->permissions);
    }

    public function test_owner_must_confirm_password_to_change_account_email(): void
    {
        $super = $this->installationOwner();
        $target = User::factory()->create();

        $this->actingAs($super)->put(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => 'new@example.com',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame($target->email, $target->fresh()->email);

        $this->put(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => 'new@example.com', 'current_password' => 'password',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('new@example.com', $target->fresh()->email);
        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_user_table_is_paginated_with_server_metadata(): void
    {
        $viewer = $this->admin(['users.view']);
        User::factory()->count(21)->create();

        $this->actingAs($viewer)->get(route('admin.users.index', ['per_page' => 10]))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Users')
                ->has('users.data', 10)
                ->where('users.total', 22)
                ->where('users.current_page', 1)
                ->where('users.last_page', 3)
                ->has('users.data.0.groups'));
    }

    public function test_user_table_search_filters_and_paginates_on_the_server(): void
    {
        $viewer = $this->admin(['users.view']);
        $group = Group::create(['name' => 'Reviewers', 'slug' => 'reviewers']);
        $target = User::factory()->create(['name' => 'Findable Person', 'email' => 'unique-find@example.com', 'email_verified_at' => now()]);
        $target->groups()->attach($group);
        $target->forceFill(['frozen_at' => now()])->save();
        User::factory()->count(12)->create();

        $this->actingAs($viewer)->get(route('admin.users.index', [
            'search' => 'unique-find', 'group_id' => $group->id, 'frozen' => 'frozen',
            'verification' => 'verified', 'per_page' => 10,
        ]))->assertInertia(fn ($page) => $page
            ->where('users.total', 1)
            ->where('users.data.0.id', $target->id)
            ->where('filters.search', 'unique-find'));

        $this->get(route('admin.users.index', ['per_page' => 10, 'sort' => 'email', 'direction' => 'desc']))
            ->assertInertia(fn ($page) => $page
                ->where('users.per_page', 10)
                ->where('users.last_page', 2)
                ->where('filters.sort', 'email'));

        $this->get(route('admin.users.index', ['sort' => 'password']))->assertSessionHasErrors('sort');
        $this->get(route('admin.users.index', ['per_page' => 1000]))->assertSessionHasErrors('per_page');
    }

    public function test_admin_cannot_delete_self_or_final_administrator(): void
    {
        $admin = $this->admin(['users.delete']);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();
        $other = $this->admin(['users.delete']);
        $this->delete(route('admin.users.destroy', $other))->assertRedirect();
        $this->assertModelMissing($other);
    }

    public function test_only_permission_assigners_can_change_group_permissions(): void
    {
        $admin = $this->admin(['groups.view', 'groups.update']);
        $group = Group::create(['name' => 'Editors', 'slug' => 'editors']);
        $this->actingAs($admin)->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => []])->assertForbidden();
        $admin->groups()->firstOrFail()->defaultRole()->permissions()->attach(Permission::where('slug', 'permissions.assign')->firstOrFail());
        $this->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => []])->assertRedirect();
    }

    public function test_group_owned_roles_grant_permissions_and_are_scoped_to_the_group(): void
    {
        $manager = $this->admin(['permissions.assign', 'users.view']);
        $group = Group::create(['name' => 'Support', 'slug' => 'support']);
        $role = $this->actingAs($manager)->post(route('admin.groups.roles.store', $group), [
            'name' => 'Support staff', 'slug' => 'support-staff',
        ])->assertRedirect();
        $role = $group->roles()->where('slug', 'support-staff')->firstOrFail();
        $this->put(route('admin.groups.roles.permissions', [$group, $role]), [
            'permissions' => [Permission::where('slug', 'users.view')->value('id')],
        ])->assertRedirect();
        $this->put(route('admin.groups.roles.permissions', [$group, $role]), [
            'permissions' => [Permission::where('slug', 'admin.access')->value('id')],
        ])->assertForbidden();
        $group->users()->attach(User::factory()->create());
        $member = $group->users()->firstOrFail();
        $this->assertTrue($member->hasPermission('users.view'));

        $otherGroup = Group::create(['name' => 'Other', 'slug' => 'other']);
        $this->put(route('admin.groups.roles.permissions', [$otherGroup, $role]), ['permissions' => []])->assertNotFound();
    }

    public function test_owner_group_roles_cannot_be_changed_through_role_endpoints(): void
    {
        $owner = $this->installationOwner();
        $group = Group::where('slug', 'owner')->firstOrFail();

        $this->actingAs($owner)->post(route('admin.groups.roles.store', $group), [
            'name' => 'Escalated', 'slug' => 'escalated',
        ])->assertForbidden();
        $this->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrFail()]), [
            'permissions' => [],
        ])->assertForbidden();
    }

    public function test_owner_can_remove_an_extra_admin_role_while_owner_access_remains(): void
    {
        $owner = $this->installationOwner();
        $group = Group::create(['name' => 'Temporary admins', 'slug' => 'temporary-admins']);
        $role = $group->roles()->create(['name' => 'Temporary admin', 'slug' => 'temporary-admin']);
        $role->permissions()->attach(Permission::where('slug', 'admin.access')->firstOrFail());
        $member = User::factory()->create();
        $member->groups()->attach($group);

        $this->actingAs($owner)->delete(route('admin.groups.roles.destroy', [$group, $role]))->assertRedirect();

        $this->assertTrue($owner->fresh()->hasPermission('admin.access'));
        $this->assertFalse($member->fresh()->hasPermission('admin.access'));
    }

    public function test_group_directory_only_shows_members_to_user_viewers_and_never_exposes_email(): void
    {
        $group = Group::create(['name' => 'A members', 'slug' => 'a-members']);
        $member = User::factory()->create();
        $member->groups()->attach($group);

        $this->actingAs($this->admin(['groups.view']))->get(route('admin.groups.index'))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('admin/Groups')->where('groups.0.id', $group->id)->missing('groups.0.users'));

        $this->actingAs($this->admin(['groups.view', 'users.view']))->get(route('admin.groups.index'))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->where('groups.0.id', $group->id)->where('groups.0.users.0.name', $member->name)
            ->missing('groups.0.users.0.email'));
    }

    public function test_group_detail_requires_group_view_and_protects_member_information(): void
    {
        $group = Group::create(['name' => 'Detail', 'slug' => 'detail']);
        $member = User::factory()->create();
        $member->groups()->attach($group);

        $this->actingAs($this->admin([]))->get(route('admin.groups.show', $group))->assertForbidden();
        $this->actingAs($this->admin(['groups.view']))->get(route('admin.groups.show', $group))
            ->assertOk()->assertInertia(fn ($page) => $page->component('admin/GroupDetail')->missing('group.users'));
        $this->actingAs($this->admin(['groups.view', 'users.view']))->get(route('admin.groups.show', $group))
            ->assertOk()->assertInertia(fn ($page) => $page->where('group.users.0.name', $member->name)->missing('group.users.0.email'));
    }

    public function test_group_deletion_respects_policy_and_protects_administrator_group(): void
    {
        $group = Group::create(['name' => 'Editors', 'slug' => 'editors']);
        $viewer = $this->admin(['groups.view']);
        $this->actingAs($viewer)->delete(route('admin.groups.destroy', $group))->assertForbidden();

        $manager = $this->admin(['groups.delete']);
        $this->actingAs($manager)->delete(route('admin.groups.destroy', Group::where('slug', 'owner')->firstOrFail()))->assertForbidden();
        $this->delete(route('admin.groups.destroy', $group))->assertRedirect();
        $this->assertModelMissing($group);
    }

    public function test_group_deletion_cannot_remove_unheld_permissions(): void
    {
        $manager = $this->admin(['groups.delete']);
        $group = Group::create(['name' => 'Privileged', 'slug' => 'privileged']);
        $permission = Permission::where('slug', 'users.delete')->firstOrFail();
        $group->defaultRole()->permissions()->attach($permission);
        $member = User::factory()->create();
        $member->groups()->attach($group);

        $this->actingAs($manager)->delete(route('admin.groups.destroy', $group))->assertForbidden();
        $this->assertTrue($group->fresh()->defaultRole()->permissions()->whereKey($permission->id)->exists());
        $this->assertTrue($member->fresh()->groups()->whereKey($group->id)->exists());
    }

    public function test_administrator_cannot_remove_their_own_access_or_grant_unheld_permissions(): void
    {
        $admin = $this->admin(['users.update', 'groups.view', 'permissions.assign']);
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'groups' => [],
        ])->assertSessionHasErrors('groups');
        $this->assertTrue($admin->fresh()->hasPermission('admin.access'));

        $group = Group::create(['name' => 'Editors', 'slug' => 'editors']);
        $permission = Permission::where('slug', 'users.delete')->firstOrFail();
        $this->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), [
            'permissions' => [$permission->id],
        ])->assertForbidden();
    }

    public function test_group_relationships_and_sync_are_idempotent(): void
    {
        $group = Group::where('slug', 'owner')->firstOrFail();
        $user = $this->installationOwner();
        $this->assertTrue($user->hasPermission('admin.access'));
        $this->assertTrue($group->users()->whereKey($user->id)->exists());
        $this->assertTrue(Permission::where('slug', 'admin.access')->firstOrFail()->roles()->whereHas('group', fn ($query) => $query->whereKey($group->id))->exists());
        $this->artisan('permissions:sync')->assertSuccessful();
        $this->artisan('permissions:sync')->assertSuccessful();
        $this->assertDatabaseCount('permissions', count(collect(config('permissions'))->flatten()));
    }

    public function test_inactive_and_frozen_permissions_never_authorize(): void
    {
        $admin = $this->admin(['users.view']);
        $stale = Permission::create(['name' => 'Retired', 'slug' => 'retired.view']);
        $admin->groups()->firstOrFail()->defaultRole()->permissions()->attach($stale);
        $this->assertFalse($admin->hasPermission('retired.view'));
        $this->assertNotContains('retired.view', $admin->permissionSlugs());
        $admin->forceFill(['frozen_at' => now()])->save();
        $this->assertFalse($admin->hasPermission('users.view'));
        $this->assertSame([], $admin->permissionSlugs());
    }

    public function test_assigner_can_preserve_but_cannot_remove_or_grant_restricted_permissions(): void
    {
        $admin = $this->admin(['permissions.assign', 'users.view']);
        $group = Group::create(['name' => 'Mixed', 'slug' => 'mixed']);
        $restricted = Permission::where('slug', 'users.delete')->firstOrFail();
        $allowed = Permission::where('slug', 'users.view')->firstOrFail();
        $group->defaultRole()->permissions()->attach($restricted);

        $this->actingAs($admin)->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), [
            'permissions' => [$restricted->id, $allowed->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertCount(2, $group->fresh()->defaultRole()->permissions);
        $this->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), [
            'permissions' => [$allowed->id],
        ])->assertForbidden();
        $this->assertCount(2, $group->fresh()->defaultRole()->permissions);
    }

    public function test_membership_changes_preserve_restricted_groups_and_require_assignment_permission(): void
    {
        $admin = $this->admin(['users.update', 'permissions.assign', 'users.view']);
        $target = User::factory()->create();
        $restricted = Group::create(['name' => 'Restricted', 'slug' => 'restricted']);
        $restricted->defaultRole()->permissions()->attach(Permission::where('slug', 'users.delete')->firstOrFail());
        $allowed = Group::create(['name' => 'Readers', 'slug' => 'readers']);
        $allowed->defaultRole()->permissions()->attach(Permission::where('slug', 'users.view')->firstOrFail());
        $target->groups()->attach($restricted);
        $profile = ['name' => 'Updated', 'email' => $target->email];

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            ...$profile, 'groups' => [$restricted->id, $allowed->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertCount(2, $target->fresh()->groups);
        $this->put(route('admin.users.update', $target), [
            ...$profile, 'groups' => [$allowed->id],
        ])->assertForbidden();
        $this->assertCount(2, $target->fresh()->groups);
        $this->actingAs($this->admin(['users.update']))->put(route('admin.users.update', $target), $profile)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertCount(2, $target->fresh()->groups);
        $this->put(route('admin.users.update', $target), [...$profile, 'groups_present' => true])
            ->assertForbidden();
    }

    public function test_group_permission_changes_cannot_remove_own_admin_access(): void
    {
        $admin = $this->admin(['permissions.assign']);
        $this->admin();
        $group = $admin->groups()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.groups.roles.permissions', [$group, $group->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), [
            'permissions' => [Permission::where('slug', 'permissions.assign')->firstOrFail()->id],
        ])->assertForbidden();
        $this->assertTrue($admin->fresh()->hasPermission('admin.access'));
    }

    public function test_user_creation_cannot_assign_unheld_group_permissions(): void
    {
        $admin = $this->admin(['users.create', 'permissions.assign']);
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Escalation', 'email' => 'escalation@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'groups' => [Group::where('slug', 'owner')->firstOrFail()->id],
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.com']);
    }

    public function test_default_group_assignment_requires_assignment_authority_and_held_permissions(): void
    {
        $default = Group::where('slug', 'std-usr')->firstOrFail();
        $default->defaultRole()->permissions()->attach(Permission::where('slug', 'users.delete')->firstOrFail());
        $input = ['name' => 'New user', 'email' => 'default@example.com', 'password' => 'password', 'password_confirmation' => 'password'];

        $this->actingAs($this->admin(['users.create']))->post(route('admin.users.store'), $input)->assertForbidden();
        $this->actingAs($this->admin(['users.create', 'permissions.assign']))->post(route('admin.users.store'), $input)->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => $input['email']]);

        $this->actingAs($this->admin(['users.create', 'permissions.assign', 'users.delete']))
            ->post(route('admin.users.store'), $input)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(User::where('email', $input['email'])->firstOrFail()->groups()->whereKey($default->id)->exists());
    }

    public function test_default_groups_and_owner_protection(): void
    {
        $this->assertEqualsCanonicalizing(['owner', 'std-usr'], Group::pluck('slug')->all());
        $super = $this->installationOwner();
        $admin = $this->admin(['groups.create', 'groups.update', 'groups.delete', 'users.view', 'users.update', 'permissions.assign']);
        $this->assertTrue($super->hasPermission('permissions.assign'));
        $this->assertTrue($admin->hasPermission('permissions.assign'));
        $this->assertTrue($admin->hasPermission('groups.create'));
        $this->assertSame(0, Group::where('slug', 'std-usr')->firstOrFail()->defaultRole()->permissions()->count());
        $this->actingAs($admin)->put(route('admin.users.update', $super), ['name' => 'Changed', 'email' => $super->email])->assertForbidden();
        $this->delete(route('admin.users.destroy', $super))->assertForbidden();
        $this->put(route('admin.users.freeze', $super))->assertForbidden();
        $this->post(route('admin.users.password-reset', $super))->assertForbidden();
        $this->seed(RbacSeeder::class);
        $this->assertEqualsCanonicalizing(['owner', 'std-usr'], Group::whereIn('slug', ['owner', 'std-usr'])->pluck('slug')->all());
    }

    public function test_owner_identity_cannot_be_changed_through_user_or_group_management(): void
    {
        $owner = User::factory()->create();
        $owner->groups()->attach(Group::where('slug', 'owner')->firstOrFail());
        DB::table('installation_metadata')->insert(['id' => 1, 'installed_at' => now(), 'owner_id' => $owner->id]);
        $actor = $this->admin(['users.create', 'users.update', 'users.delete', 'groups.update', 'permissions.assign']);
        $ownerGroup = Group::where('slug', 'owner')->firstOrFail();

        $this->actingAs($actor)->put(route('admin.users.update', $owner), ['name' => 'Changed', 'email' => $owner->email])->assertForbidden();
        $this->delete(route('admin.users.destroy', $owner))->assertForbidden();
        $this->put(route('admin.users.freeze', $owner))->assertForbidden();
        $this->post(route('admin.users.store'), [
            'name' => 'Promoted', 'email' => 'promoted@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'groups' => [$ownerGroup->id],
        ])->assertForbidden();
        $this->put(route('admin.users.update', $actor), [
            'name' => $actor->name, 'email' => $actor->email, 'groups' => [$ownerGroup->id],
        ])->assertForbidden();
        $this->put(route('admin.groups.roles.permissions', [$ownerGroup, $ownerGroup->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => []])->assertForbidden();
        $this->assertSame($owner->id, DB::table('installation_metadata')->value('owner_id'));
        $this->assertTrue(app(InstallationIntegrityService::class)->ownerIsConsistent());
        User::factory()->create()->groups()->attach($ownerGroup);
        $this->assertFalse(app(InstallationIntegrityService::class)->ownerIsConsistent());
        try {
            app(ResetUserPassword::class)->reset($owner, ['password' => 'password']);
            $this->fail('Normal password reset must not recover the installation owner.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_installation_owner_migration_preserves_existing_administrator_roles(): void
    {
        $migration = require database_path('migrations/2026_10_29_000001_bind_installation_owner.php');
        $migration->down();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $legacyOwner = Group::create(['name' => 'Former super administrator', 'slug' => 'super-admin']);
        $legacyAdmin = Group::create(['name' => 'Administrator', 'slug' => 'adm']);
        $access = Permission::where('slug', 'admin.access')->firstOrFail();
        $legacyAdmin->defaultRole()->permissions()->attach($access);
        $owner->groups()->attach($legacyOwner);
        $other->groups()->attach($legacyAdmin);
        DB::table('installation_metadata')->insert(['id' => 1, 'installed_at' => now()]);

        $migration->up();

        $this->assertDatabaseHas('installation_metadata', ['id' => 1, 'owner_id' => $owner->id]);
        $this->assertTrue($owner->groups()->where('slug', 'owner')->exists());
        $this->assertTrue($owner->groups()->whereKey($legacyOwner->id)->exists());
        $this->assertTrue($other->groups()->whereKey($legacyAdmin->id)->exists());
        $this->assertTrue($other->fresh()->hasPermission('admin.access'));
        $this->assertTrue($owner->fresh()->hasPermission('admin.access'));
    }

    public function test_administrator_manages_ordinary_roles_but_cannot_change_privileged_roles(): void
    {
        $admin = $this->admin(['groups.create', 'groups.update', 'groups.delete', 'users.view', 'users.update', 'permissions.assign']);
        $this->actingAs($admin)->post(route('admin.groups.store'), [
            'name' => 'Support', 'slug' => 'support',
        ])->assertRedirect();
        $support = Group::where('slug', 'support')->firstOrFail();
        $view = Permission::where('slug', 'users.view')->firstOrFail();
        $this->put(route('admin.groups.roles.permissions', [$support, $support->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => [$view->id]])->assertRedirect();
        $this->assertTrue($support->fresh()->defaultRole()->permissions()->where('slug', 'users.view')->exists());
        $access = Permission::where('slug', 'admin.access')->firstOrFail();
        $this->put(route('admin.groups.roles.permissions', [$support, $support->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => [$view->id, $access->id]])->assertForbidden();
        $this->put(route('admin.groups.roles.permissions', [$admin->groups()->firstOrFail(), $admin->groups()->firstOrFail()->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => []])->assertForbidden();
        $this->put(route('admin.groups.roles.permissions', [Group::where('slug', 'owner')->firstOrFail(), Group::where('slug', 'owner')->firstOrFail()->roles()->firstOrCreate(['slug' => 'default'], ['name' => 'Default'])]), ['permissions' => []])->assertForbidden();

        $peer = User::factory()->create();
        $this->put(route('admin.users.update', $peer), [
            'name' => $peer->name, 'email' => $peer->email,
            'groups' => [$admin->groups()->firstOrFail()->id],
        ])->assertRedirect();
        $this->assertTrue($peer->fresh()->groups()->whereKey($admin->groups()->firstOrFail()->id)->exists());
        $this->put(route('admin.users.update', $peer), [
            'name' => $peer->name, 'email' => $peer->email,
            'groups' => [Group::where('slug', 'owner')->firstOrFail()->id],
        ])->assertForbidden();
    }

    public function test_group_detail_is_shown_in_the_groups_page_and_user_detail_has_safe_fields(): void
    {
        $admin = $this->admin(['groups.view', 'users.view', 'permissions.assign']);
        $group = Group::where('slug', 'std-usr')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.groups.index'))
            ->assertOk()->assertInertia(fn ($page) => $page->component('admin/Groups')
            ->where('groups', fn ($groups) => collect($groups)->contains(fn ($item) => $item['id'] === $group->id && array_key_exists('roles', $item))));
        $this->get(route('admin.users.show', $user))
            ->assertOk()->assertInertia(fn ($page) => $page->component('admin/UserDetail')->where('user.id', $user->id)->missing('user.password')->missing('user.remember_token'));
    }

    public function test_owner_cannot_be_frozen_or_demoted(): void
    {
        $super = $this->installationOwner();
        $peer = $this->admin();
        $this->actingAs($super)->put(route('admin.users.update', $super), [
            'name' => $super->name, 'email' => $super->email, 'groups' => [$peer->groups()->firstOrFail()->id],
        ])->assertForbidden();
        $this->put(route('admin.users.freeze', $super))->assertForbidden();
        $this->put(route('admin.users.freeze', $peer))->assertRedirect();
    }
}
