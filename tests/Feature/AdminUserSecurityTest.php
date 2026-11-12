<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $canUpdate = true): User
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Manager', 'slug' => 'manager-'.$user->id]);
        $group->defaultRole()->permissions()->sync(Permission::whereIn('slug', $canUpdate
            ? ['admin.access', 'users.update'] : ['admin.access'])->pluck('id'));
        $user->groups()->attach($group);

        return $user;
    }

    public function test_admin_can_send_password_reset_link_but_other_admin_cannot(): void
    {
        Notification::fake();
        $target = User::factory()->create();

        $this->actingAs($this->admin(false))
            ->post(route('admin.users.password-reset', $target))->assertForbidden();

        $this->actingAs($this->admin())
            ->post(route('admin.users.password-reset', $target))->assertRedirect();
        Notification::assertSentTo($target, ResetPassword::class);
    }

    public function test_freezing_user_blocks_login_and_existing_session_until_unfrozen(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.freeze', $target))->assertRedirect();
        $this->assertNotNull($target->fresh()->frozen_at);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($target->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($admin->fresh())->delete(route('admin.users.unfreeze', $target))->assertRedirect();
        $this->assertNull($target->fresh()->frozen_at);
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($target);
    }

    public function test_admin_cannot_freeze_self_or_freeze_without_update_permission(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.freeze', $admin))->assertForbidden();
        $this->actingAs($this->admin(false))->put(route('admin.users.freeze', $target))->assertForbidden();
        $this->assertNull($target->fresh()->frozen_at);
    }
}
