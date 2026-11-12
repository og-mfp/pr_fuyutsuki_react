<?php

use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new RbacSeeder)->run();
    }

    public function down(): void
    {
        $groupId = DB::table('groups')->where('slug', 'admin')->value('id');
        $ids = DB::table('permissions')->whereIn('slug', [
            'groups.create', 'groups.update', 'groups.delete', 'permissions.assign',
        ])->pluck('id');
        DB::table('group_permission')->where('group_id', $groupId)->whereIn('permission_id', $ids)->delete();
    }
};
