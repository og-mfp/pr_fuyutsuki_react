<?php

namespace Tests\Feature;

use App\Events\HighRiskAuthenticationDetected;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\HighRiskAuthenticationNotification;
use App\Services\AuditRelationships;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_activity_is_allowlisted_and_unchanged_updates_are_ignored(): void
    {
        $user = User::factory()->create();
        $created = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->firstOrFail();
        $this->assertArrayNotHasKey('password', $created->properties->get('attributes'));
        $user->update(['name' => 'New name', 'password' => 'Different-password-123!']);
        $updated = Activity::where('subject_type', User::class)->where('event', 'updated')->firstOrFail();
        $this->assertSame('New name', $updated->properties->get('attributes')['name']);
        $this->assertArrayNotHasKey('password', $updated->properties->get('attributes'));
        $count = Activity::count();
        $user->update(['name' => 'New name']);
        $this->assertSame($count, Activity::count());
        $user->delete();
        $this->assertDatabaseHas('activity_log', ['subject_type' => User::class, 'subject_id' => $user->id, 'event' => 'deleted']);
    }

    public function test_security_events_never_store_credentials_or_unverified_identity(): void
    {
        $user = User::factory()->create();
        event(new Login('web', $user, false));
        event(new Logout('web', $user));
        event(new Failed('web', null, ['email' => 'unverified@example.com', 'password' => 'secret']));
        $this->assertDatabaseHas('activity_log', ['log_name' => 'security', 'event' => 'login']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'security', 'event' => 'logout']);
        $failed = Activity::where('event', 'login.failed')->firstOrFail();
        $this->assertNull($failed->causer_id);
        $this->assertStringNotContainsString('secret', $failed->toJson());
        $this->assertStringNotContainsString('unverified@example.com', $failed->toJson());
    }

    public function test_relationship_audit_rolls_back_with_mutation(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Auditors', 'slug' => 'auditors']);
        try {
            DB::transaction(function () use ($user, $group) {
                $user->groups()->attach($group);
                AuditRelationships::record($user, 'groups', [], [$group->id]);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }
        $this->assertDatabaseMissing('group_user', ['user_id' => $user->id, 'group_id' => $group->id]);
        $this->assertDatabaseMissing('activity_log', ['subject_type' => User::class, 'subject_id' => $user->id, 'event' => 'groups.synced']);
    }

    public function test_audit_routes_require_permission_and_expose_no_mutation_route(): void
    {
        $user = User::factory()->create();
        $this->get('/admin/activity-logs')->assertRedirect();
        $this->actingAs($user)->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($user)->get('/admin/activity-logs/export')->assertForbidden();
        $access = Permission::firstOrCreate(['slug' => 'admin.access'], ['name' => 'Admin Access']);
        $view = Permission::firstOrCreate(['slug' => 'audit.view'], ['name' => 'Audit View']);
        $group = Group::create(['name' => 'Auditors', 'slug' => 'auditors']);
        $group->defaultRole()->permissions()->attach([$access->id, $view->id]);
        $user->groups()->attach($group);
        $this->actingAs($user)->get('/admin/activity-logs')->assertOk();
        $this->actingAs($user)->get('/admin/activity-logs?category=invalid')->assertSessionHasErrors('category');
        $entry = Activity::where('subject_type', User::class)->where('subject_id', $user->id)->firstOrFail();
        $entry->properties = ['attributes' => ['name' => 'Safe', 'password' => 'hidden-secret']];
        $entry->save();
        $this->actingAs($user)->get("/admin/activity-logs/{$entry->id}")
            ->assertOk()->assertJsonMissing(['password' => 'hidden-secret']);
        $export = Permission::firstOrCreate(['slug' => 'audit.export'], ['name' => 'Audit Export']);
        $group->defaultRole()->permissions()->attach($export);
        $csv = $this->actingAs($user)->get('/admin/activity-logs/export?subject=user&subject_id='.$user->id)
            ->assertOk()->streamedContent();
        $this->assertStringContainsString('Safe', $csv);
        $this->assertStringNotContainsString('hidden-secret', $csv);
        $this->actingAs($user)->post('/admin/activity-logs')->assertMethodNotAllowed();
    }

    public function test_security_activity_is_filterable_and_exposes_only_risk_summary(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $access = Permission::firstOrCreate(['slug' => 'admin.access'], ['name' => 'Admin Access']);
        $view = Permission::firstOrCreate(['slug' => 'audit.view'], ['name' => 'Audit View']);
        $group = Group::create(['name' => 'Auditors', 'slug' => 'auditors']);
        $group->defaultRole()->permissions()->attach([$access->id, $view->id]);
        $admin->groups()->attach($group);
        $entry = activity('security')->event('login.risk.high')->performedOn($target)->causedByAnonymous()
            ->withProperties(['level' => 'high', 'score' => 82, 'signals' => ['unknown_device'], 'token' => 'secret'])
            ->log('login.risk.high');

        $this->actingAs($admin)->get('/admin/activity-logs?category=security&risk_level=high&subject=user&subject_id='.$target->id)
            ->assertOk()->assertInertia(fn ($page) => $page->has('activities.data', 1)
            ->where('activities.data.0.risk_level', 'high')->where('activities.data.0.risk_score', 82)
            ->where('activities.data.0.risk_signals.0', 'unknown_device'));
        $this->actingAs($admin)->get('/admin/activity-logs/'.$entry->id)->assertOk()
            ->assertJsonMissing(['token' => 'secret'])->assertJsonPath('risk_score', 82);
    }

    public function test_audit_export_includes_more_than_ten_thousand_matching_records(): void
    {
        $admin = User::factory()->create();
        $group = Group::create(['name' => 'Auditors', 'slug' => 'auditors']);
        $group->defaultRole()->permissions()->attach(Permission::whereIn('slug', ['admin.access', 'audit.export'])->pluck('id'));
        $admin->groups()->attach($group);
        $timestamp = now();
        foreach (array_chunk(array_fill(0, 10001, [
            'log_name' => 'security',
            'description' => 'Export limit check',
            'event' => 'audit.export.check',
            'properties' => '{}',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]), 100) as $rows) {
            DB::table('activity_log')->insert($rows);
        }

        $csv = $this->actingAs($admin)->get('/admin/activity-logs/export?event=audit.export.check')
            ->assertOk()->streamedContent();

        $this->assertSame(10002, substr_count($csv, "\n"));
    }

    public function test_user_directory_shows_latest_security_event_for_investigation(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();
        $access = Permission::firstOrCreate(['slug' => 'admin.access'], ['name' => 'Admin Access']);
        $users = Permission::firstOrCreate(['slug' => 'users.view'], ['name' => 'Users View']);
        $group = Group::create(['name' => 'User Investigators', 'slug' => 'user-investigators']);
        $group->defaultRole()->permissions()->attach([$access->id, $users->id]);
        $admin->groups()->attach($group);
        activity('security')->event('login.risk.high')->performedOn($target)->causedByAnonymous()
            ->withProperties(['level' => 'high', 'score' => 90])->log('login.risk.high');

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertInertia(fn ($page) => $page
            ->where('users.data', fn ($users) => collect($users)->contains(fn ($user) => $user['id'] === $target->id
                && $user['latest_security_event'] === 'login.risk.high' && $user['latest_security_at'] !== null)));
    }

    public function test_high_risk_alert_is_queued_to_owner_when_delivery_is_configured(): void
    {
        Notification::fake();
        config(['mail.mailers.smtp.transport' => 'smtp', 'mail.default' => 'smtp']);
        $admin = $this->installationOwner();

        event(new HighRiskAuthenticationDetected(User::factory()->create(), 91, '192.0.2.10'));

        Notification::assertSentTo($admin, HighRiskAuthenticationNotification::class);
    }
}
