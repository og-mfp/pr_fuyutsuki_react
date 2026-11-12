<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        Group::firstOrCreate(['slug' => 'std-usr'], ['name' => 'Standard User']);
        $owner = Group::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);
        if (Schema::hasColumn('installation_metadata', 'owner_id')) {
            $owner->defaultRole()->permissions()->sync(Permission::whereIn('slug', Permission::catalogSlugs())->pluck('id'));
        }
    }
}
