<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $metadata = DB::table('installation_metadata')->get();
        if ($metadata->count() > 1) {
            throw new RuntimeException('Cannot bind installation owner: multiple installation metadata records exist.');
        }
        $ownerId = null;
        if ($metadata->isNotEmpty()) {
            $owners = DB::table('group_user')->join('groups', 'groups.id', '=', 'group_user.group_id')
                ->where('groups.slug', 'super-admin')->distinct()->pluck('group_user.user_id');
            if ($owners->count() !== 1) {
                throw new RuntimeException('Cannot bind installation owner: expected exactly one Super Administrator.');
            }
            $ownerId = $owners->sole();
        }
        $existingOwnerGroup = DB::table('groups')->where('slug', 'owner')->value('id');
        if ($existingOwnerGroup && (DB::table('group_user')->where('group_id', $existingOwnerGroup)->exists()
            || (Schema::hasTable('group_permission') && DB::table('group_permission')->where('group_id', $existingOwnerGroup)->exists())
            || (Schema::hasTable('rbac_roles') && DB::table('rbac_roles')->where('group_id', $existingOwnerGroup)->exists()))) {
            throw new RuntimeException('Cannot reserve the owner role: an existing owner group has assignments.');
        }

        $catalog = array_unique(Permission::catalogSlugs());
        $permissionIds = DB::table('permissions')->whereIn('slug', $catalog)->pluck('id');
        if ($permissionIds->count() !== count($catalog)) {
            throw new RuntimeException('Run permissions:sync before binding the installation owner.');
        }

        Schema::table('installation_metadata', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->boolean('singleton')->default(true)->unique();
        });

        if ($metadata->isNotEmpty()) {
            DB::table('installation_metadata')->where('id', $metadata->sole()->id)->update(['owner_id' => $ownerId]);
        }
        Schema::table('installation_metadata', fn (Blueprint $table) => $table->unsignedBigInteger('owner_id')->nullable(false)->change());

        $this->renameGroup('std-user', 'std-usr', 'Standard User');
        $roleSetting = DB::table('app_settings')->where('key', 'users.default_role');
        if (DB::connection()->getDriverName() === 'pgsql') {
            $roleSetting->whereRaw('value = ?::jsonb', ['"std-user"'])
                ->update(['value' => DB::raw("'\"std-usr\"'::jsonb")]);
        } else {
            $roleSetting->where('value', '"std-user"')->update(['value' => '"std-usr"']);
        }
        Cache::forget('app-setting:users.default_role');
        DB::table('groups')->insertOrIgnore(['name' => 'Owner', 'slug' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        $ownerGroupId = DB::table('groups')->where('slug', 'owner')->value('id');
        if (Schema::hasTable('rbac_roles')) {
            $roleId = DB::table('rbac_roles')->insertGetId(['group_id' => $ownerGroupId, 'name' => 'Default', 'slug' => 'default', 'created_at' => now(), 'updated_at' => now()]);
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        } else {
            foreach ($permissionIds as $permissionId) {
                DB::table('group_permission')->insertOrIgnore(['group_id' => $ownerGroupId, 'permission_id' => $permissionId]);
            }
        }
        if ($metadata->isNotEmpty()) {
            $ownerId = DB::table('installation_metadata')->value('owner_id');
            DB::table('group_user')->insertOrIgnore(['user_id' => $ownerId, 'group_id' => $ownerGroupId]);
        }
    }

    public function down(): void
    {
        if (DB::table('installation_metadata')->exists()
            || DB::table('group_user')->whereIn('group_id', DB::table('groups')->where('slug', 'owner')->select('id'))->exists()) {
            throw new RuntimeException('Cannot roll back ownership while an installation or owner membership exists.');
        }
        DB::table('groups')->where('slug', 'std-usr')->update(['slug' => 'std-user']);
        DB::table('groups')->where('slug', 'owner')->delete();
        Schema::table('installation_metadata', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropUnique(['singleton']);
            $table->dropColumn('singleton');
        });
    }

    private function renameGroup(string $oldSlug, string $newSlug, string $name): void
    {
        $oldId = DB::table('groups')->where('slug', $oldSlug)->value('id');
        $newId = DB::table('groups')->where('slug', $newSlug)->value('id');
        if (! $oldId) {
            return;
        }
        if (! $newId) {
            DB::table('groups')->where('id', $oldId)->update(['slug' => $newSlug, 'name' => $name]);

            return;
        }
        if (DB::table('group_user')->where('group_id', $newId)->exists()) {
            throw new RuntimeException("Cannot rename {$oldSlug}: the target role {$newSlug} already has users.");
        }
        $pivots = ['group_user' => 'user_id'];
        if (Schema::hasTable('group_permission')) {
            $pivots['group_permission'] = 'permission_id';
        }
        foreach ($pivots as $table => $foreignKey) {
            $rows = DB::table($table)->where('group_id', $oldId)->pluck($foreignKey);
            foreach ($rows as $id) {
                DB::table($table)->insertOrIgnore(['group_id' => $newId, $foreignKey => $id]);
            }
        }
        DB::table('groups')->where('id', $oldId)->delete();
        DB::table('groups')->where('id', $newId)->update(['name' => $name]);
    }
};
