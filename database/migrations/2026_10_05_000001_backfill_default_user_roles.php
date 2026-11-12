<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $member = Group::where('slug', 'member')->first()
            ?? Group::where('slug', 'std-usr')->firstOrFail();
        User::doesntHave('groups')->each(fn (User $user) => $user->groups()->attach($member));
    }

    public function down(): void {}
};
