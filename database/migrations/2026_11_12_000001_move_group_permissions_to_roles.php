<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbac_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'slug']);
        });
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('rbac_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        foreach (DB::table('groups')->orderBy('id')->get() as $group) {
            $roleId = DB::table('rbac_roles')->insertGetId([
                'group_id' => $group->id,
                'name' => 'Default',
                'slug' => 'default',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_permission')->insertUsing(['role_id', 'permission_id'],
                DB::table('group_permission')->where('group_id', $group->id)->selectRaw('? as role_id, permission_id', [$roleId]));
        }
        Schema::drop('group_permission');
    }

    public function down(): void
    {
        Schema::create('group_permission', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['group_id', 'permission_id']);
        });
        DB::table('rbac_roles')->join('role_permission', 'rbac_roles.id', '=', 'role_permission.role_id')
            ->select('rbac_roles.group_id', 'role_permission.permission_id')->distinct()->orderBy('rbac_roles.group_id')
            ->each(function ($row) {
                DB::table('group_permission')->insertOrIgnore(['group_id' => $row->group_id, 'permission_id' => $row->permission_id]);
            });
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('rbac_roles');
    }
};
