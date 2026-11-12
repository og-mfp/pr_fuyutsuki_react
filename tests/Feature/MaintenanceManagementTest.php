<?php

namespace Tests\Feature;

use App\Models\MaintenanceNativeEvent;
use App\Models\MaintenanceReconciliation;
use App\Models\MaintenanceWindow;
use App\Models\User;
use App\Services\MaintenanceManagementService;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\CacheBasedMaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\ActivityLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MaintenanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            app()->maintenanceMode()->deactivate();
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    public function test_owner_sees_health_and_settings_on_maintenance_page(): void
    {
        $owner = $this->installationOwner();

        $this->actingAs($owner)->get(route('admin.maintenance.index'))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('admin/Maintenance')
            ->has('health.database')
            ->has('health.queue.worker_status')
            ->has('settings.heading')
            ->has('windows.data'));

        $this->get(route('admin.configuration.index'))->assertInertia(fn ($page) => $page
            ->missing('health')->missing('settings.health_maintenance'));
    }

    public function test_owner_receives_inconsistency_dialog_once_per_session(): void
    {
        $owner = $this->installationOwner();
        MaintenanceWindow::create([
            'title' => 'Incomplete', 'type' => 'other', 'reason' => 'Incident', 'public_message' => 'Notice',
            'scheduled_start_at' => now()->subHour(), 'scheduled_end_at' => now()->addHour(),
            'status' => 'in_progress', 'published' => false,
        ]);

        $this->actingAs($owner)->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('maintenanceWarning', true));
        $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('maintenanceWarning', false));
    }

    public function test_administrator_without_system_settings_access_cannot_modify_maintenance(): void
    {
        $actor = $this->privilegedUser();
        $window = MaintenanceWindow::create([
            'title' => 'Release', 'type' => 'deployment', 'reason' => 'Upgrade', 'public_message' => 'Brief outage',
            'scheduled_start_at' => now()->addHour(), 'scheduled_end_at' => now()->addHours(2),
            'status' => 'scheduled', 'published' => false,
        ]);

        $this->actingAs($actor)->get(route('admin.maintenance.index'))->assertForbidden();
        $this->put(route('admin.maintenance.update', $window), ['current_password' => 'password'])->assertForbidden();
        $this->delete(route('admin.maintenance.destroy', $window), ['current_password' => 'password'])->assertForbidden();
        $this->post(route('admin.maintenance.reconcile'), ['explanation' => 'Accept', 'current_password' => 'password'])->assertForbidden();
        $this->assertSame('scheduled', $window->fresh()->status);
    }

    public function test_emergency_cli_requires_reason_and_completes_idempotently(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->artisan('maintenance:begin', ['--emergency' => true, '--operator' => 'cli-test'])->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());

        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Security patch'])->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Security patch', '--operator' => '   '])->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Security patch', '--operator' => str_repeat('x', 161)])->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Security patch', '--operator' => 'cli-test'])->assertExitCode(0);
        $this->assertTrue(app()->maintenanceMode()->active());
        $this->artisan('maintenance:end', ['--summary' => 'Patch verified', '--operator' => 'toni'])->assertExitCode(0);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->assertDatabaseHas('maintenance_windows', ['type' => 'emergency', 'status' => 'completed', 'started_by_operator' => 'cli-test', 'completed_by_operator' => 'toni']);
        $this->artisan('maintenance:end', ['--summary' => 'Retry'])->assertExitCode(1);
    }

    public function test_maintenance_completion_revokes_all_user_sessions_including_bypass_logins(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $existingLogin = User::factory()->create();
        $bypassLogin = User::factory()->create();
        DB::table('users')->whereIn('id', [$existingLogin->id, $bypassLogin->id])->update(['remember_token' => 'remember-me']);

        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Release', '--operator' => 'cli-test'])->assertExitCode(0);
        $this->artisan('maintenance:end', ['--summary' => 'Release complete'])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $existingLogin->id, 'maintenance_session_version' => 1, 'remember_token' => null]);
        $this->assertDatabaseHas('users', ['id' => $bypassLogin->id, 'maintenance_session_version' => 1, 'remember_token' => null]);
    }

    public function test_session_from_before_maintenance_is_rejected_after_session_version_changes(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['maintenance_session_version' => 1]);

        $this->actingAs($user)->withSession(['auth.maintenance_session_version' => 0])
            ->get(route('dashboard'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_emergency_maintenance_fails_closed_when_shared_lock_is_unavailable(): void
    {
        config(['app.maintenance.driver' => 'array']);
        Cache::shouldReceive('lock')->once()->andThrow(new \RuntimeException('Cache unavailable.'));

        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Incident', '--operator' => 'cli-test'])
            ->assertExitCode(1);

        $this->assertFalse(app()->maintenanceMode()->active());
        $this->assertDatabaseEmpty('maintenance_windows');
    }

    public function test_maintenance_commands_log_exit_and_cli_identity_without_raw_arguments(): void
    {
        $previousConnection = getenv('SSH_CONNECTION');
        $previousClient = getenv('SSH_CLIENT');
        putenv('SSH_CONNECTION');
        putenv('SSH_CLIENT');
        Log::spy();

        try {
            $this->artisan('maintenance:status')->assertExitCode(0);
            Event::dispatch(new CommandFinished('maintenance:status', new ArrayInput([]), new BufferedOutput, 0));
            putenv('SSH_CONNECTION=203.0.113.17 45678 22 22');
            $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'private reason'])->assertExitCode(1);
            Event::dispatch(new CommandFinished('maintenance:begin', new ArrayInput([]), new BufferedOutput, 1));
            Event::dispatch(new CommandFinished('maintenance:begin', new ArrayInput(
                ['--operator' => 'INC-55'],
                new InputDefinition([new InputOption('operator', null, InputOption::VALUE_OPTIONAL)]),
            ), new BufferedOutput, 0));
        } finally {
            $previousConnection === false ? putenv('SSH_CONNECTION') : putenv('SSH_CONNECTION='.$previousConnection);
            $previousClient === false ? putenv('SSH_CLIENT') : putenv('SSH_CLIENT='.$previousClient);
        }

        $account = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? null)
            : null;
        Log::shouldHaveReceived('notice')->with('Maintenance Artisan command finished', \Mockery::on(fn (array $context) => $context['command'] === 'maintenance:status'
            && $context['exit_code'] === 0 && $context['source'] === 'cli' && $context['source_ip'] === null
            && $context['os_user'] === $account && $context['operator_identifier'] === null))->once();
        Log::shouldHaveReceived('notice')->with('Maintenance Artisan command finished', \Mockery::on(fn (array $context) => $context['command'] === 'maintenance:begin'
            && $context['exit_code'] === 1 && $context['source_ip'] === '203.0.113.17'
            && $context['os_user'] === $account && $context['operator_identifier'] === null
            && ! array_key_exists('reason', $context)))->once();
        Log::shouldHaveReceived('notice')->with('Maintenance Artisan command finished', \Mockery::on(fn (array $context) => $context['command'] === 'maintenance:begin'
            && $context['exit_code'] === 0 && $context['source_ip'] === '203.0.113.17'
            && $context['operator_identifier'] === 'INC-55'))->once();
    }

    public function test_optional_secret_bypasses_maintenance_but_not_authorization_and_rotates(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Release', '--operator' => 'cli-test'])->assertExitCode(0);
        $this->assertNull(app()->maintenanceMode()->data()['secret']);
        $this->get('/')->assertStatus(503);
        $this->get('/up')->assertOk();
        $this->artisan('maintenance:end', ['--summary' => 'Done'])->assertExitCode(0);

        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Release', '--operator' => 'cli-test', '--with-secret' => true])
            ->expectsOutputToContain('Bypass URL:')->assertExitCode(0);
        $secret = app()->maintenanceMode()->data()['secret'];
        $this->assertNotEmpty($secret);
        $this->get('/wrong-secret')->assertStatus(503);
        $cookie = $this->get('/'.$secret)->assertRedirect()->getCookie('laravel_maintenance', false);
        $this->assertNotNull($cookie);
        $this->assertTrue(MaintenanceModeBypassCookie::isValid($cookie->getValue(), $secret));
        $this->withUnencryptedCookie('laravel_maintenance', $cookie->getValue())
            ->get(route('admin.configuration.index'))->assertRedirect(route('login'));
        $bypassUser = User::factory()->create();
        $this->actingAs($bypassUser)->withSession(['auth.maintenance_session_version' => 0])
            ->withUnencryptedCookie('laravel_maintenance', $cookie->getValue())
            ->get(route('dashboard'))->assertOk();
        $this->assertStringNotContainsString($secret, DB::table('activity_log')->get()->toJson());
        $this->artisan('maintenance:end', ['--summary' => 'Done'])->assertExitCode(0);
        $this->actingAs($bypassUser->fresh());
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Next release', '--operator' => 'cli-test', '--with-secret' => true])->assertExitCode(0);
        $this->assertNotSame($secret, app()->maintenanceMode()->data()['secret']);
        $this->withUnencryptedCookie('laravel_maintenance', $cookie->getValue())->get('/')->assertStatus(503);
    }

    public function test_redis_maintenance_state_and_lock_are_shared(): void
    {
        if (getenv('MAINTENANCE_REDIS_TEST') !== '1') {
            $this->markTestSkipped('Redis integration is enabled in CI.');
        }
        config(['app.maintenance.driver' => 'cache', 'app.maintenance.store' => 'redis']);
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Shared state', '--operator' => 'cli-test'])->assertExitCode(0);
        $repository = new CacheBasedMaintenanceMode(app('cache'), 'redis', 'illuminate:foundation:down');
        $this->assertTrue($repository->active());
        $lock = Cache::store('redis')->lock('maintenance-operation', 30);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('maintenance:end', ['--summary' => 'Blocked'])->assertExitCode(1);
            $this->assertTrue($repository->active());
        } finally {
            $lock->release();
        }
        $this->artisan('maintenance:end', ['--summary' => 'Done'])->assertExitCode(0);
        $this->assertFalse($repository->active());
        DB::setDefaultConnection('missing');
        try {
            $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Database outage', '--operator' => 'cli-test'])->assertExitCode(0);
            $this->assertTrue($repository->active());
            $this->artisan('maintenance:end', ['--summary' => 'Restored'])->assertExitCode(0);
            $this->assertFalse($repository->active());
        } finally {
            DB::setDefaultConnection('sqlite');
        }
    }

    public function test_status_exit_codes_distinguish_unknown_from_reviewable_inconsistency(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->artisan('maintenance:status')->expectsOutputToContain('consistent')->assertExitCode(0);
        app()->maintenanceMode()->activate([]);
        $this->artisan('maintenance:status')->expectsOutputToContain('needs review')->assertExitCode(0);
        Schema::drop('maintenance_native_events');
        $this->artisan('maintenance:status')->expectsOutputToContain('status unavailable')->assertExitCode(1);
    }

    public function test_unknown_inspection_is_passed_to_admin_without_reconciliation(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $owner = $this->installationOwner();
        Schema::drop('maintenance_native_events');
        $this->actingAs($owner)->get(route('admin.maintenance.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->where('maintenanceState', 'unknown')->where('maintenanceConsistent', false));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Maintenance state is unknown');
        app(MaintenanceManagementService::class)->reconcile('Cannot safely reconcile');
    }

    public function test_managed_commands_run_laravels_maintenance_lifecycle(): void
    {
        config(['app.maintenance.driver' => 'array']);
        Event::fake([MaintenanceModeEnabled::class, MaintenanceModeDisabled::class]);
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Lifecycle check', '--operator' => 'cli-test'])->assertExitCode(0);
        $data = app()->maintenanceMode()->data();
        $this->assertSame('Lifecycle check', $data['reason']);
        $this->assertNotNull($data['operation_id']);
        $this->assertNotEmpty($data['template']);
        $this->assertFileExists(storage_path('framework/maintenance.php'));
        Event::assertDispatched(MaintenanceModeEnabled::class);
        $this->assertDatabaseCount('maintenance_native_events', 0);

        $this->artisan('maintenance:end', ['--summary' => 'Verified'])->assertExitCode(0);
        $this->assertFileDoesNotExist(storage_path('framework/maintenance.php'));
        Event::assertDispatched(MaintenanceModeDisabled::class);
        $this->assertDatabaseCount('maintenance_native_events', 0);
    }

    public function test_internal_lifecycle_commands_cannot_bypass_managed_operations(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->artisan('maintenance:managed-down')->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());
        app()->maintenanceMode()->activate([]);
        $this->artisan('maintenance:managed-up')->assertExitCode(1);
        $this->assertTrue(app()->maintenanceMode()->active());
    }

    public function test_failed_native_lifecycle_after_activation_keeps_operation_recoverable(): void
    {
        config(['app.maintenance.driver' => 'array']);
        Event::listen(MaintenanceModeEnabled::class, fn () => throw new \RuntimeException('Listener failed'));
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Lifecycle failure', '--operator' => 'cli-test'])->assertExitCode(1);
        $this->assertTrue(app()->maintenanceMode()->active());
        $this->assertDatabaseHas('maintenance_windows', ['reason' => 'Lifecycle failure', 'status' => 'in_progress']);
        $this->artisan('maintenance:end', ['--summary' => 'Recovered'])->assertExitCode(0);
    }

    public function test_failed_native_lifecycle_after_deactivation_leaves_record_recoverable(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Recovery failure', '--operator' => 'cli-test'])->assertExitCode(0);
        Event::listen(MaintenanceModeDisabled::class, fn () => throw new \RuntimeException('Listener failed'));
        $this->artisan('maintenance:end', ['--summary' => 'Recovered'])->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->assertDatabaseHas('maintenance_windows', ['reason' => 'Recovery failure', 'status' => 'in_progress']);
        $this->artisan('maintenance:end', ['--summary' => 'Recovery verified'])->assertExitCode(0);
        $this->assertDatabaseHas('maintenance_windows', ['reason' => 'Recovery failure', 'status' => 'completed']);
    }

    public function test_managed_file_mode_serves_the_snapshot_before_application_bootstrap(): void
    {
        config(['app.maintenance.driver' => 'file']);
        $this->assertFileDoesNotExist(storage_path('framework/down'));
        try {
            $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Bootstrap check', '--operator' => 'cli-test'])->assertExitCode(0);
            $process = new Process([PHP_BINARY, '-r', '$_SERVER["REQUEST_URI"] = "/"; require $argv[1]; echo "APPLICATION_BOOTSTRAPPED";', storage_path('framework/maintenance.php')]);
            $process->mustRun();
            $this->assertStringContainsString('We are performing emergency maintenance', $process->getOutput());
            $this->assertStringNotContainsString('APPLICATION_BOOTSTRAPPED', $process->getOutput());
            $this->artisan('maintenance:end', ['--summary' => 'Verified'])->assertExitCode(0);
            $this->assertFileDoesNotExist(storage_path('framework/maintenance.php'));
        } finally {
            app()->maintenanceMode()->deactivate();
            if (is_file(storage_path('framework/maintenance.php'))) {
                unlink(storage_path('framework/maintenance.php'));
            }
        }
    }

    public function test_begin_reports_missing_window_with_actionable_guidance(): void
    {
        $this->artisan('maintenance:begin', ['--window' => 1, '--operator' => 'cli-test'])
            ->expectsOutputToContain('Maintenance window 1 not found')
            ->assertExitCode(1);
    }

    public function test_planned_window_rejects_conflicts_and_tracks_cli_transitions(): void
    {
        $now = Carbon::now();
        $first = app(MaintenanceManagementService::class)->create([
            'title' => 'Release', 'type' => 'deployment', 'reason' => 'Schema update', 'public_message' => 'Brief outage',
            'scheduled_start_at' => $now->copy()->addHours(2)->format('Y-m-d H:i'),
            'scheduled_end_at' => $now->copy()->addHours(3)->format('Y-m-d H:i'), 'published' => true,
        ], null);
        try {
            app(MaintenanceManagementService::class)->create([
                'title' => 'Overlap', 'type' => 'database', 'reason' => 'Reason', 'public_message' => 'Notice',
                'scheduled_start_at' => $now->copy()->addHours(2)->addMinutes(30)->format('Y-m-d H:i'),
                'scheduled_end_at' => $now->copy()->addHours(4)->format('Y-m-d H:i'), 'published' => true,
            ], null);
            $this->fail('Overlapping windows must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('maintenance_windows', 1);
        }

        config(['app.maintenance.driver' => 'array']);
        $first->update(['scheduled_start_at' => now()->subMinute(), 'scheduled_end_at' => now()->addHour()]);
        $this->artisan('maintenance:begin', ['--window' => $first->id, '--operator' => 'cli-test'])->assertExitCode(0);
        $this->artisan('maintenance:end', ['--summary' => 'Deployment verified'])->assertExitCode(0);
        $this->assertDatabaseHas('maintenance_windows', ['id' => $first->id, 'status' => 'completed']);
    }

    public function test_late_maintenance_requires_explicit_override(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $window = MaintenanceWindow::create([
            'title' => 'Late deployment', 'type' => 'deployment', 'reason' => 'Deploy',
            'public_message' => 'Planned work', 'scheduled_start_at' => now()->subHours(2),
            'scheduled_end_at' => now()->subHour(), 'status' => 'scheduled', 'published' => false,
        ]);

        $this->artisan('maintenance:begin', ['--window' => $window->id, '--operator' => 'cli-test'])->assertExitCode(1);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->artisan('maintenance:begin', ['--window' => $window->id, '--override' => true, '--operator' => 'cli-test'])->assertExitCode(0);
        $this->artisan('maintenance:end', ['--summary' => 'Completed'])->assertExitCode(0);
    }

    public function test_maintenance_schedule_uses_user_timezone_and_stores_utc(): void
    {
        $start = Carbon::now('Asia/Jakarta')->addHours(3)->startOfMinute();
        $window = app(MaintenanceManagementService::class)->create([
            'title' => 'Timezone check', 'type' => 'deployment', 'reason' => 'Test', 'public_message' => 'Test',
            'scheduled_start_at' => $start->format('Y-m-d H:i'),
            'scheduled_end_at' => $start->copy()->addHour()->format('Y-m-d H:i'), 'published' => false,
        ], User::factory()->create(), 'Asia/Jakarta');

        $this->assertSame('UTC', config('app.timezone'));
        $this->assertTrue($window->scheduled_start_at->equalTo($start->utc()));
    }

    public function test_schedule_update_cannot_move_start_into_the_past(): void
    {
        $actor = User::factory()->create();
        $data = [
            'title' => 'Release', 'type' => 'deployment', 'reason' => 'Upgrade', 'public_message' => 'Brief outage',
            'scheduled_start_at' => now()->addHours(2)->toDateTimeString(),
            'scheduled_end_at' => now()->addHours(3)->toDateTimeString(), 'published' => false,
        ];
        $window = app(MaintenanceManagementService::class)->create($data, $actor);
        $data['scheduled_start_at'] = now()->subMinute()->toDateTimeString();

        try {
            app(MaintenanceManagementService::class)->update($window, $data, $actor);
            $this->fail('A past start should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scheduled_start_at', $exception->errors());
        }
        $this->assertSame('scheduled', $window->fresh()->status);
        $this->assertTrue($window->fresh()->scheduled_start_at->isFuture());
    }

    public function test_published_upcoming_announcement_is_shared_without_internal_reason(): void
    {
        $this->installationOwner();
        MaintenanceWindow::create([
            'title' => 'Database upgrade', 'type' => 'database', 'reason' => 'Private migration details',
            'public_message' => 'Database maintenance is planned.', 'scheduled_start_at' => now()->addHours(2),
            'scheduled_end_at' => now()->addHours(3), 'status' => 'scheduled', 'published' => true,
        ]);
        MaintenanceWindow::create([
            'title' => 'Cancelled work', 'type' => 'other', 'reason' => 'Private', 'public_message' => 'Do not show',
            'scheduled_start_at' => now()->addHours(2), 'scheduled_end_at' => now()->addHours(3), 'status' => 'cancelled', 'published' => true,
        ]);

        $this->get(route('login'))->assertOk()->assertInertia(fn ($page) => $page
            ->has('maintenanceAnnouncements', 1)
            ->where('maintenanceAnnouncements.0.public_message', 'Database maintenance is planned.')
            ->missing('maintenanceAnnouncements.0.reason'));

        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->has('maintenanceAnnouncements', 1)
            ->missing('maintenanceAnnouncements.0.reason'));
    }

    public function test_native_down_and_up_remain_available_and_untracked_completion_is_detected(): void
    {
        config(['app.maintenance.driver' => 'array']);
        Log::spy();
        $user = User::factory()->create();
        $this->artisan('down', ['--retry' => 30])->assertExitCode(0);
        $this->assertTrue(app()->maintenanceMode()->active());
        Event::dispatch(new CommandFinished('down', new ArrayInput([]), new BufferedOutput, 0));
        Log::shouldHaveReceived('notice')->with('Native Artisan maintenance command finished', \Mockery::on(fn (array $context) => $context['command'] === 'down' && $context['actual_active'] === true))->once();
        $this->assertSame('untracked_activation', app(MaintenanceManagementService::class)->inspect()['state']);
        $this->artisan('maintenance:status')->expectsOutputToContain('untracked_activation')->assertExitCode(0);
        Event::dispatch(new CommandStarting('up', new ArrayInput([]), new BufferedOutput));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'maintenance_session_version' => 1, 'remember_token' => null]);
        $this->artisan('up')->assertExitCode(0);
        $this->assertFalse(app()->maintenanceMode()->active());
        Event::dispatch(new CommandFinished('up', new ArrayInput([]), new BufferedOutput, 0));
        Log::shouldHaveReceived('notice')->with('Native Artisan maintenance command finished', \Mockery::on(fn (array $context) => $context['command'] === 'up' && $context['actual_active'] === false))->once();
        $this->assertTrue(app(MaintenanceManagementService::class)->inspect()['consistent']);
        $this->assertSame(2, app(MaintenanceManagementService::class)->inspect()['unresolved_incidents']);

        $window = MaintenanceWindow::create([
            'title' => 'External operation', 'type' => 'other', 'reason' => 'Recorded plan', 'public_message' => 'Notice',
            'scheduled_start_at' => now()->subHour(), 'scheduled_end_at' => now()->addHour(), 'started_at' => now()->subMinutes(30),
            'status' => 'in_progress', 'published' => false,
        ]);
        $this->artisan('down')->assertExitCode(0);
        $this->artisan('up')->assertExitCode(0);
        $this->assertSame('untracked_completion', app(MaintenanceManagementService::class)->inspect()['state']);
        $this->assertNull($window->fresh()->ended_at);
    }

    public function test_reconciliation_requires_explanation_and_preserves_observed_evidence(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->artisan('down')->assertExitCode(0);
        $this->artisan('maintenance:reconcile', ['--operator' => 'cli-test'])->assertExitCode(1);
        $this->artisan('maintenance:reconcile', ['--explanation' => 'Native down used during incident'])->assertExitCode(1);
        $this->artisan('maintenance:reconcile', ['--explanation' => 'Native down used during incident', '--operator' => ' '])->assertExitCode(1);
        $this->artisan('maintenance:reconcile', ['--explanation' => 'Native down used during incident', '--operator' => str_repeat('x', 161)])->assertExitCode(1);
        $this->assertDatabaseCount('maintenance_reconciliations', 0);
        $this->artisan('maintenance:reconcile', ['--explanation' => 'Native down used during incident', '--operator' => 'INC-9'])->assertExitCode(0);

        $record = MaintenanceReconciliation::firstOrFail();
        $this->assertTrue($record->actual_active);
        $this->assertNull($record->native_reason);
        $this->assertNull($record->reconciled_by);
        $this->assertSame('INC-9', $record->operator_identifier);
        $this->assertTrue(app()->maintenanceMode()->active());
        $this->assertTrue(app(MaintenanceManagementService::class)->inspect()['acknowledged']);
    }

    public function test_native_incidents_survive_repeated_and_failed_commands_and_are_acknowledged_individually(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $service = app(MaintenanceManagementService::class);
        app()->maintenanceMode()->activate([]);
        $service->recordNativeCommand('down', 0);
        $first = MaintenanceNativeEvent::latest('id')->firstOrFail();
        $service->recordNativeCommand('down', 0);
        app()->maintenanceMode()->deactivate();
        $service->recordNativeCommand('up', 1);
        $service->recordNativeCommand('up', 0);
        $completion = MaintenanceNativeEvent::latest('id')->firstOrFail();
        $service->recordNativeCommand('up', 0);

        $this->assertSame(5, MaintenanceNativeEvent::count());
        $this->assertTrue($service->inspect()['consistent']);
        $this->assertSame(2, $service->inspect()['unresolved_incidents']);
        $this->assertSame($first->id, $service->reconcile('Investigated activation')->native_event_id);
        $this->assertSame($completion->id, $service->inspect()['incident']->id);
        $this->assertSame($completion->id, $service->reconcile('Investigated recovery')->native_event_id);
        $this->assertSame(0, $service->inspect()['unresolved_incidents']);
        app()->maintenanceMode()->activate([]);
        $service->recordNativeCommand('down', 0);
        $this->assertSame(1, $service->inspect()['unresolved_incidents']);
    }

    public function test_stale_recovery_requires_selection_and_preserves_observed_times(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $this->travelTo(now()->startOfSecond());
        $window = MaintenanceWindow::create([
            'title' => 'Stale operation', 'type' => 'other', 'reason' => 'Original reason', 'public_message' => 'Notice',
            'scheduled_start_at' => now()->subHour(), 'scheduled_end_at' => now()->addHour(), 'started_at' => now()->subMinutes(30),
            'status' => 'in_progress', 'published' => false,
        ]);
        $other = $window->replicate();
        $other->save();
        $service = app(MaintenanceManagementService::class);
        $service->recordNativeCommand('up', 0);
        $observed = now();
        $this->travel(10)->minutes();
        $this->artisan('maintenance:end', ['--summary' => 'Restored'])->assertExitCode(1);
        $this->artisan('maintenance:end', ['--window' => $window->id])->assertExitCode(1);
        $this->artisan('maintenance:end', ['--window' => $window->id, '--summary' => 'Restored'])->assertExitCode(0);
        $window->refresh();
        $this->assertSame('Original reason', $window->reason);
        $this->assertSame('Restored', $window->operator_notes);
        $this->assertNull($window->ended_at);
        $this->assertTrue($window->recovery_observed_at->equalTo($observed));
        $this->assertTrue($window->closed_at->equalTo(now()));
        $this->assertSame('in_progress', $other->fresh()->status);
        $this->artisan('maintenance:end', ['--window' => $other->id, '--summary' => 'Also restored'])->assertExitCode(0);
        $this->assertTrue($service->inspect()['consistent']);
        $this->assertSame(1, $service->inspect()['unresolved_incidents']);
        $service->reconcile('Investigated native recovery');
        $this->assertSame(0, $service->inspect()['unresolved_incidents']);
    }

    public function test_stale_recovery_without_observed_event_does_not_invent_recovery_time(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $window = MaintenanceWindow::create([
            'title' => 'Stale operation', 'type' => 'other', 'reason' => 'Original', 'public_message' => 'Notice',
            'scheduled_start_at' => now()->subHour(), 'scheduled_end_at' => now()->addHour(), 'started_at' => now()->subMinutes(30),
            'status' => 'in_progress', 'published' => false,
        ]);
        $this->artisan('maintenance:end', ['--summary' => 'Verified'])->assertExitCode(0);
        $this->assertNull($window->fresh()->ended_at);
        $this->assertNull($window->fresh()->recovery_observed_at);
        $this->assertNotNull($window->fresh()->closed_at);
    }

    public function test_reconciliation_row_survives_activity_audit_failure(): void
    {
        config(['app.maintenance.driver' => 'array']);
        app()->instance(ActivityLogger::class, $this->failingActivityLogger());
        app()->maintenanceMode()->activate(['reason' => 'native operator context']);

        $this->artisan('maintenance:reconcile', ['--explanation' => 'Accepted native state', '--operator' => 'cli-test'])->assertExitCode(0);
        $this->assertDatabaseHas('maintenance_reconciliations', ['explanation' => 'Accepted native state']);
        $this->assertTrue(app()->maintenanceMode()->active());
    }

    public function test_emergency_recovery_survives_activity_audit_failure(): void
    {
        config(['app.maintenance.driver' => 'array']);
        app()->instance(ActivityLogger::class, $this->failingActivityLogger());

        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Contain outage', '--operator' => 'cli-test'])->assertExitCode(0);
        $this->assertTrue(app()->maintenanceMode()->active());
        $this->artisan('maintenance:end', ['--summary' => 'Recovery verified'])->assertExitCode(0);
        $this->assertFalse(app()->maintenanceMode()->active());
        $this->assertDatabaseHas('maintenance_windows', ['type' => 'emergency', 'status' => 'completed']);
    }

    public function test_emergency_activation_and_recovery_work_without_maintenance_database(): void
    {
        config(['app.maintenance.driver' => 'array']);
        Schema::drop('maintenance_windows');

        $this->artisan('maintenance:begin', ['--emergency' => true, '--reason' => 'Contain outage', '--operator' => 'cli-test'])->assertExitCode(0);
        $this->assertTrue(app()->maintenanceMode()->active());
        $this->artisan('maintenance:end', ['--summary' => 'Service restored'])->assertExitCode(0);
        $this->assertFalse(app()->maintenanceMode()->active());
    }

    private function failingActivityLogger(): ActivityLogger
    {
        $logger = \Mockery::mock(ActivityLogger::class);
        $logger->shouldReceive('setLogStatus')->andReturnSelf();
        $logger->shouldReceive('useLog')->andReturnSelf();
        $logger->shouldReceive('causedBy')->andReturnSelf();
        $logger->shouldReceive('withProperties')->andReturnSelf();
        $logger->shouldReceive('log')->andThrow(new \RuntimeException('audit unavailable'));

        return $logger;
    }
}
