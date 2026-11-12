<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\InstallationStatus;
use App\Models\User;
use App\Services\InstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FirstTimeSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_time_setup_creates_the_only_superuser(): void
    {
        $this->assertSame(InstallationStatus::NotInstalled, app(InstallationService::class)->status());

        $this->get(route('setup.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('setup/Index')
                ->where('checks.0.ready', true)
                ->where('checks.1.ready', true)
                ->where('checks.2.ready', true)
                ->where('checks.3.ready', true)
                ->where('checks.4.ready', true)
                ->where('checks.5.ready', true));
        $sessionId = session()->getId();

        $response = $this->post(route('setup.store'), [
            'name' => 'Super User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->sole();

        $response->assertRedirect(route('dashboard'));
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->groups()->where('slug', 'owner')->exists());
        $this->assertTrue($user->can('access-admin'));
        $this->assertDatabaseCount('installation_metadata', 1);
        $this->assertDatabaseHas('installation_metadata', ['id' => 1, 'owner_id' => $user->id]);
        $this->assertFileExists(storage_path('app/private/installed.lock'));
    }

    public function test_setup_closes_after_the_first_user_exists(): void
    {
        app(InstallationService::class)->install([
            'name' => 'Super User',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->get(route('setup.create'))->assertRedirect(route('login'));

        $this->post(route('setup.store'), [
            'name' => 'Another Admin',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 1);
    }

    public function test_owner_migration_cannot_be_rolled_back_after_installation(): void
    {
        $owner = $this->installationOwner();
        $migration = require database_path('migrations/2026_10_29_000001_bind_installation_owner.php');

        try {
            $migration->down();
            $this->fail('Rollback must not delete installation ownership.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Cannot roll back ownership', $exception->getMessage());
        }
        $this->assertDatabaseHas('installation_metadata', ['id' => 1, 'owner_id' => $owner->id]);
        $this->assertTrue($owner->groups()->where('slug', 'owner')->exists());
    }

    public function test_registration_cannot_bypass_first_time_setup(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Member',
            'email' => 'member@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseEmpty('users');
    }

    public function test_both_installation_markers_are_required(): void
    {
        $installation = app(InstallationService::class);
        $owner = User::factory()->create();

        DB::table('installation_metadata')->insert([
            'id' => 1,
            'installed_at' => now(),
            'owner_id' => $owner->id,
        ]);

        $this->assertFalse($installation->isInstalled());
        $this->assertSame(InstallationStatus::Inconsistent, $installation->status());

        DB::table('installation_metadata')->delete();
        file_put_contents(storage_path('app/private/installed.lock'), '{}');

        $this->assertFalse($installation->isInstalled());
        $this->assertSame(InstallationStatus::Inconsistent, $installation->status());
    }

    public function test_markers_without_an_administrator_are_inconsistent(): void
    {
        $owner = User::factory()->create();
        DB::table('installation_metadata')->insert([
            'id' => 1,
            'installed_at' => now(),
            'owner_id' => $owner->id,
        ]);
        file_put_contents(storage_path('app/private/installed.lock'), '{}');

        $this->assertSame(InstallationStatus::Inconsistent, app(InstallationService::class)->status());
        $this->get(route('setup.create'))->assertStatus(503);
    }

    public function test_existing_users_block_a_new_installation(): void
    {
        User::factory()->create();

        $checks = app(InstallationService::class)->checks();

        $this->assertFalse($checks[5]['ready']);
        $this->assertFalse(app(InstallationService::class)->isInstalled());
        $this->assertSame(InstallationStatus::Inconsistent, app(InstallationService::class)->status());
        $this->get(route('home'))->assertStatus(503);
        $this->get(route('setup.create'))->assertStatus(503);
    }

    public function test_database_failure_is_unknown_and_blocks_setup_and_registration(): void
    {
        DB::setDefaultConnection('missing');
        try {
            $this->assertSame(InstallationStatus::Unknown, app(InstallationService::class)->status());
            $this->get(route('home'))->assertRedirect(route('setup.create'));
            $this->get(route('setup.create'))->assertOk()->assertInertia(fn ($page) => $page
                ->component('setup/Index')
                ->where('checks.2.ready', false)
                ->where('checks.5.ready', false));
            $this->post(route('setup.store'), [])->assertStatus(503);

            try {
                app(CreateNewUser::class)->create(['name' => 'Member', 'email' => 'member@example.com', 'password' => 'password']);
                $this->fail('Registration should be blocked when installation state is unknown.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('email', $exception->errors());
            }
        } finally {
            DB::setDefaultConnection('sqlite');
        }
    }

    public function test_setup_shows_readiness_with_an_unmigrated_database_and_database_sessions(): void
    {
        config(['database.connections.empty_setup' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'session.driver' => 'database']);
        app('session')->forgetDrivers();
        DB::setDefaultConnection('empty_setup');

        try {
            $this->get(route('home'))->assertRedirect(route('setup.create'));
            $this->get(route('setup.create'))->assertOk()->assertInertia(fn ($page) => $page
                ->component('setup/Index')
                ->where('checks.2.ready', false)
                ->where('checks.5.ready', false));
        } finally {
            DB::setDefaultConnection('sqlite');
            config(['session.driver' => 'array']);
            app('session')->forgetDrivers();
        }
    }

    public function test_initialization_remains_safe_while_another_setup_holds_the_lock(): void
    {
        $lock = Cache::lock('first-time-setup', 10);
        $this->assertTrue($lock->get());

        try {
            $this->assertNull(app(InstallationService::class)->install([
                'name' => 'Super User',
                'email' => 'admin@example.com',
                'password' => 'password',
            ]));
            $this->assertDatabaseEmpty('users');
        } finally {
            $lock->release();
        }
    }

    public function test_rolled_back_installation_keeps_setup_closed_when_lock_was_written(): void
    {
        try {
            DB::transaction(function () {
                app(InstallationService::class)->install([
                    'name' => 'Super User', 'email' => 'admin@example.com', 'password' => 'password',
                ]);
                throw new \RuntimeException('Rollback after the lock was written.');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Rollback after the lock was written.', $exception->getMessage());
        }

        $this->assertDatabaseEmpty('users');
        $this->assertDatabaseEmpty('installation_metadata');
        $this->assertFileExists(storage_path('app/private/installed.lock'));
        $this->assertSame(InstallationStatus::Inconsistent, app(InstallationService::class)->status());
        $this->get(route('setup.create'))->assertStatus(503);
    }

    public function test_failed_setup_checks_explain_how_to_fix_them(): void
    {
        config(['app.key' => null]);

        $check = app(InstallationService::class)->checks()[0];

        $this->assertFalse($check['ready']);
        $this->assertSame('Set APP_KEY before continuing.', $check['detail']);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/private/installed.lock'));

        parent::tearDown();
    }
}
