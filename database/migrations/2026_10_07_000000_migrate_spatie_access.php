<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        // Keep the old tables intact for audit and rollback; the application reads native tables.
        Schema::rename('permissions', 'legacy_spatie_permissions');
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        // A temporary name avoids PostgreSQL index and sequence name collisions with the preserved table.
        Schema::create('rbac_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::rename('rbac_permissions', 'permissions');
        Schema::create('group_user', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['group_id', 'user_id']);
        });
        Schema::create('group_permission', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['group_id', 'permission_id']);
        });

        $catalog = Permission::catalogSlugs();
        foreach ($catalog as $slug) {
            DB::table('permissions')->insert([
                'name' => str($slug)->replace('.', ' ')->title(), 'slug' => $slug,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $permissionIds = DB::table('permissions')->pluck('id', 'slug');
        $legacyManageAccessId = DB::table('legacy_spatie_permissions')->where('name', 'manage access')->value('id');
        foreach (DB::table('roles')->get() as $role) {
            $groupId = DB::table('groups')->insertGetId([
                'name' => str($role->name)->title(), 'slug' => $role->name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('model_has_roles')->where('role_id', $role->id)->where('model_type', 'App\\Models\\User')->pluck('model_id') as $userId) {
                DB::table('group_user')->insertOrIgnore(['group_id' => $groupId, 'user_id' => $userId]);
            }
            $slugs = $legacyManageAccessId && DB::table('role_has_permissions')
                ->where('role_id', $role->id)->where('permission_id', $legacyManageAccessId)->exists()
                ? $catalog : [];
            foreach ($slugs as $slug) {
                DB::table('group_permission')->insertOrIgnore(['group_id' => $groupId, 'permission_id' => $permissionIds[$slug]]);
            }
        }
        if ($legacyManageAccessId) {
            $directUsers = DB::table('model_has_permissions')
                ->where('permission_id', $legacyManageAccessId)
                ->where('model_type', 'App\\Models\\User')->pluck('model_id');
            if ($directUsers->isNotEmpty()) {
                $groupId = DB::table('groups')->insertGetId([
                    'name' => 'Legacy direct administrators', 'slug' => 'legacy-direct-admin',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($catalog as $slug) {
                    DB::table('group_permission')->insert(['group_id' => $groupId, 'permission_id' => $permissionIds[$slug]]);
                }
                foreach ($directUsers as $userId) {
                    DB::table('group_user')->insertOrIgnore(['group_id' => $groupId, 'user_id' => $userId]);
                }
            }
        }
        // Existing custom permission names have no code-owned capability equivalent and remain in legacy tables.
    }

    public function down(): void
    {
        if (! Schema::hasTable('legacy_spatie_permissions')) {
            return;
        }
        Schema::dropIfExists('group_permission');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('permissions');
        Schema::rename('legacy_spatie_permissions', 'permissions');
    }
};
