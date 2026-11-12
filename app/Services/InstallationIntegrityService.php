<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InstallationIntegrityService
{
    public function ownerIsConsistent(): bool
    {
        $metadata = DB::table('installation_metadata')->where('id', 1)->first();
        if (! $metadata || DB::table('installation_metadata')->count() !== 1) {
            return false;
        }

        $ownerGroupId = DB::table('groups')->where('slug', 'owner')->value('id');

        return $ownerGroupId
            && DB::table('users')->where('id', $metadata->owner_id)->whereNull('frozen_at')->exists()
            && DB::table('group_user')->where('group_id', $ownerGroupId)->where('user_id', $metadata->owner_id)->exists()
            && DB::table('group_user')->where('group_id', $ownerGroupId)->count() === 1;
    }
}
