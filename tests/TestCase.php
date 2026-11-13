<?php

namespace Tests;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\SecurityChangeNotification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $config = $app['config'];

        if ($config->get('app.env') !== 'testing'
            || $config->get('database.default') !== 'sqlite'
            || $config->get('database.connections.sqlite.database') !== ':memory:'
            || filled($config->get('database.connections.sqlite.url'))) {
            throw new RuntimeException('Refusing to run tests outside the isolated in-memory testing database.');
        }

        return $app;
    }

    protected function authorizeSecurityChange(User $user, string $action, array $payload = [], ?string $target = null): void
    {
        Notification::fake();
        $this->actingAs($user);
        session()->start();
        $this->withCookie(config('session.cookie'), session()->getId())->withCredentials();
        $id = $this->postJson(route('security-challenges.store'), compact('action', 'payload', 'target'))->assertOk()->json('id');
        $code = null;
        Notification::assertSentOnDemand(SecurityChangeNotification::class, function ($notification) use (&$code) {
            $code = $notification->code;

            return $code !== null;
        });
        $this->postJson(route('security-challenges.confirm', $id), ['code' => $code])->assertNoContent();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        $this->isolateInstallationStorage();
    }

    protected function installationOwner(): User
    {
        $user = User::factory()->create();
        $user->groups()->attach(Group::where('slug', 'owner')->firstOrFail());
        DB::table('installation_metadata')->insert(['id' => 1, 'installed_at' => now(), 'owner_id' => $user->id]);

        return $user;
    }

    protected function privilegedUser(): User
    {
        $user = User::factory()->create();
        $group = Group::create(['name' => 'Test access', 'slug' => 'test-access-'.$user->id]);
        $group->defaultRole()->permissions()->attach(Permission::whereIn('slug', Permission::catalogSlugs())->pluck('id'));
        $user->groups()->attach($group);

        return $user;
    }

    protected function isolateInstallationStorage(): void
    {
        $path = storage_path('framework/testing');
        $this->app->useStoragePath($path);
        foreach (['app/private', 'framework'] as $directory) {
            if (! is_dir($path.'/'.$directory)) {
                mkdir($path.'/'.$directory, 0777, true);
            }
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
