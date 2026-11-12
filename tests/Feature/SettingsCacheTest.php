<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\SettingsRegistry;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsCacheTest extends TestCase
{
    use DatabaseMigrations;

    public function test_sensitive_values_are_fresh_and_loaded_in_one_query(): void
    {
        AppSetting::updateOrCreate(['key' => 'authentication.authentication_lockdown'], ['value' => true]);
        AppSetting::updateOrCreate(['key' => 'health_maintenance.retry_after'], ['value' => 123]);
        AppSetting::updateOrCreate(['key' => 'notifications.security_enabled'], ['value' => false]);
        Cache::put('app-settings:non-sensitive:v1', [
            'authentication.authentication_lockdown' => false,
            'health_maintenance.retry_after' => 999,
            'notifications.security_enabled' => true,
        ], 60);

        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains(strtolower($query->sql), 'from "app_settings"')) {
                $queries++;
            }
        });
        $settings = app(SettingsService::class);
        $this->assertTrue($settings->get('authentication.authentication_lockdown'));
        $this->assertSame(123, $settings->get('health_maintenance.retry_after'));
        $this->assertFalse($settings->get('notifications.security_enabled'));
        $this->assertSame(1, $queries);
    }

    public function test_non_sensitive_cache_is_invalidated_after_commit_and_never_contains_uncommitted_data(): void
    {
        $actor = User::factory()->create();
        AppSetting::updateOrCreate(['key' => 'general.display_name'], ['value' => 'Initial']);
        AppSetting::updateOrCreate(['key' => 'general.description'], ['value' => 'Description']);
        $settings = new SettingsService(app(SettingsRegistry::class));
        $this->assertSame('Initial', $settings->get('general.display_name'));
        $this->assertTrue(Cache::has('app-settings:non-sensitive:v1'));

        DB::beginTransaction();
        $settings->update('general', ['display_name' => 'Rolled back'], $actor);
        $this->assertSame('Rolled back', (new SettingsService(app(SettingsRegistry::class)))->get('general.display_name'));
        DB::rollBack();
        $this->assertSame('Initial', (new SettingsService(app(SettingsRegistry::class)))->get('general.display_name'));

        $settings->update('general', ['display_name' => 'Updated'], $actor);
        $fresh = new SettingsService(app(SettingsRegistry::class));
        $this->assertSame('Updated', $fresh->get('general.display_name'));
        $this->assertSame('Description', $fresh->get('general.description'));

        DB::beginTransaction();
        $fresh->update('general', ['display_name' => 'Committed'], $actor);
        $this->assertSame('Committed', (new SettingsService(app(SettingsRegistry::class)))->get('general.display_name'));
        DB::commit();
        $this->assertSame('Committed', (new SettingsService(app(SettingsRegistry::class)))->get('general.display_name'));
    }

    public function test_authenticated_page_loads_fresh_control_settings_in_one_query(): void
    {
        $user = User::factory()->create();
        (new SettingsService(app(SettingsRegistry::class)))->get('general.display_name');
        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains(strtolower($query->sql), 'from "app_settings"')) {
                $queries++;
            }
        });

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertSame(1, $queries);
    }
}
