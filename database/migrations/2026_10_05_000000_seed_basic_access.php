<?php

use App\Models\Group;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Permission::catalogSlugs() as $slug) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => str($slug)->replace('.', ' ')->title()]);
        }
        Group::firstOrCreate(['slug' => 'std-usr'], ['name' => 'Standard User']);
        Group::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner']);
    }

    public function down(): void {}
};
