<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class LegacyAccessMigrationTest extends TestCase
{
    use RefreshDatabase;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! defined('PHPUNIT_COMPOSER_INSTALL')) {
            define('PHPUNIT_COMPOSER_INSTALL', dirname(__DIR__, 2).'/vendor/autoload.php');
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_existing_manage_access_grants_are_preserved(): void
    {
        $roleUser = User::factory()->create();
        $directUser = User::factory()->create();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('rbac_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('group_permission');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('permissions');
        Schema::enableForeignKeyConstraints();

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('model_id');
            $table->string('model_type');
        });

        DB::table('permissions')->insert(['id' => 1, 'name' => 'manage access', 'guard_name' => 'web']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'admin', 'guard_name' => 'web']);
        DB::table('role_has_permissions')->insert(['role_id' => 1, 'permission_id' => 1]);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => $roleUser->id, 'model_type' => User::class]);
        DB::table('model_has_permissions')->insert(['permission_id' => 1, 'model_id' => $directUser->id, 'model_type' => User::class]);

        $migration = require database_path('migrations/2026_10_07_000000_migrate_spatie_access.php');
        $migration->up();
        $rolesMigration = require database_path('migrations/2026_11_12_000001_move_group_permissions_to_roles.php');
        $rolesMigration->up();

        $this->assertTrue($roleUser->hasPermission('admin.access'));
        $this->assertTrue($directUser->hasPermission('admin.access'));
        $this->assertTrue(Schema::hasTable('legacy_spatie_permissions'));
    }
}
