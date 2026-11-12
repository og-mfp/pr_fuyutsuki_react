<?php

use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('groups')->where('slug', 'admin')->update(['slug' => 'super-admin', 'name' => 'Super Administrator']);
        DB::table('groups')->where('slug', 'member')->update(['slug' => 'std-user', 'name' => 'Std User']);
        (new RbacSeeder)->run();
    }

    public function down(): void
    {
        $adminId = DB::table('groups')->where('slug', 'admin')->value('id');
        $superId = DB::table('groups')->where('slug', 'super-admin')->value('id');
        foreach (DB::table('group_user')->where('group_id', $adminId)->pluck('user_id') as $userId) {
            DB::table('group_user')->insertOrIgnore(['group_id' => $superId, 'user_id' => $userId]);
        }
        DB::table('groups')->where('id', $adminId)->delete();
        DB::table('groups')->where('slug', 'super-admin')->update(['slug' => 'admin', 'name' => 'Administrator']);
        DB::table('groups')->where('slug', 'std-user')->update(['slug' => 'member', 'name' => 'Member']);
    }
};
