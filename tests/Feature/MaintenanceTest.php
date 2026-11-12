<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ApplicationHealthService;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_status_uses_laravels_actual_maintenance_repository(): void
    {
        config(['app.maintenance.driver' => 'array']);
        $mode = app()->maintenanceMode();
        $mode->activate(['reason' => 'planned release', 'secret' => 'do-not-expose']);

        try {
            $snapshot = app(ApplicationHealthService::class)->snapshot();
            $this->assertSame('maintenance', $snapshot['application']);
            $this->assertTrue($snapshot['maintenance']['active']);
            $this->assertSame('planned release', $snapshot['maintenance']['reason']);
            $this->assertNull($snapshot['maintenance']['activated_at']);
            $this->assertNull($snapshot['maintenance']['actor']);
            $this->assertArrayNotHasKey('secret', $snapshot['maintenance']);
        } finally {
            $mode->deactivate();
        }
    }

    public function test_maintenance_settings_are_authorized_sensitive_and_validated(): void
    {
        $normal = User::factory()->create();
        $this->actingAs($normal)->put(route('admin.configuration.update', 'health_maintenance'), [
            'heading' => 'Planned work',
        ])->assertForbidden();

        $admin = $this->installationOwner();
        $this->actingAs($admin)->put(route('admin.configuration.update', 'health_maintenance'), [
            'heading' => 'Planned work',
        ])->assertSessionHasErrors('current_password');
        $this->put(route('admin.configuration.update', 'health_maintenance'), [
            'heading' => 'Planned work', 'retry_after' => 86401, 'current_password' => 'password',
        ])->assertSessionHasErrors('retry_after');
        $this->put(route('admin.configuration.update', 'health_maintenance'), [
            'heading' => 'Planned work', 'description' => 'Please return soon', 'retry_after' => 600,
            'current_password' => 'password',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Planned work', app(SettingsService::class)->get('health_maintenance.heading'));
        $this->assertDatabaseHas('activity_log', ['log_name' => 'configuration', 'description' => 'setting changed']);
    }

    public function test_native_maintenance_response_is_503_and_escapes_public_settings(): void
    {
        config(['app.maintenance.driver' => 'array']);
        app()->maintenanceMode()->activate([
            'retry' => 600,
            'template' => view('errors.maintenance', [
                'heading' => '<script>alert(1)</script>',
                'description' => '<img src=x onerror=alert(1)>',
            ])->render(),
            'status' => 503,
            'secret' => null,
        ]);

        try {
            $this->get('/')->assertStatus(503)
                ->assertHeader('Retry-After', '600')
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
                ->assertDontSee('<script>', false)
                ->assertDontSee('<img', false);
        } finally {
            app()->maintenanceMode()->deactivate();
        }
    }

    public function test_health_snapshot_does_not_claim_unknown_maintenance_state_is_operational(): void
    {
        config(['app.maintenance.driver' => 'unsupported']);

        $this->assertNull(app(ApplicationHealthService::class)->snapshot()['maintenance']['active']);
    }

    public function test_health_distinguishes_queue_backend_evidence_from_worker_evidence(): void
    {
        config(['queue.default' => 'database']);
        $queue = app(ApplicationHealthService::class)->snapshot()['queue'];

        $this->assertSame('database', $queue['driver']);
        $this->assertSame('reachable', $queue['backend_status']);
        $this->assertSame('not_verified', $queue['worker_status']);
    }

    public function test_sync_queue_does_not_claim_a_backend_or_worker(): void
    {
        config(['queue.default' => 'sync']);

        $queue = app(ApplicationHealthService::class)->snapshot()['queue'];

        $this->assertSame('not_applicable', $queue['backend_status']);
        $this->assertSame('not_applicable', $queue['worker_status']);
    }

    public function test_health_reports_database_queue_backend_failure_without_crashing(): void
    {
        config(['queue.default' => 'database']);
        DB::setDefaultConnection('missing');
        try {
            $snapshot = app(ApplicationHealthService::class)->snapshot();
            $this->assertSame('unavailable', $snapshot['database']);
            $this->assertSame('unreachable', $snapshot['queue']['backend_status']);
            $this->assertSame('not_verified', $snapshot['queue']['worker_status']);
        } finally {
            DB::setDefaultConnection('sqlite');
        }
    }

    public function test_health_reports_cache_read_failure_without_crashing(): void
    {
        Cache::shouldReceive('store')->andThrow(new \RuntimeException('cache unavailable'));

        $this->assertSame('unavailable', app(ApplicationHealthService::class)->snapshot()['cache']);
    }

    public function test_maintenance_bypass_does_not_grant_admin_authorization(): void
    {
        config(['app.maintenance.driver' => 'array']);
        app()->maintenanceMode()->activate(['secret' => 'known-secret']);

        try {
            $cookie = MaintenanceModeBypassCookie::create('known-secret');
            $this->withUnencryptedCookie('laravel_maintenance', $cookie->getValue())
                ->get(route('admin.configuration.index'))
                ->assertRedirect(route('login'));
        } finally {
            app()->maintenanceMode()->deactivate();
        }
    }

    public function test_artisan_up_recovers_when_the_web_interface_is_unavailable(): void
    {
        config(['app.maintenance.driver' => 'array']);
        app()->maintenanceMode()->activate([]);

        $this->assertSame(0, Artisan::call('up'));
        $this->assertFalse(app()->maintenanceMode()->active());
    }
}
