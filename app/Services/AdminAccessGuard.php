<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAccessGuard
{
    public function isOwner(User $user): bool
    {
        $ownerId = DB::table('installation_metadata')->where('id', 1)->value('owner_id');

        return $ownerId !== null && (int) $ownerId === (int) $user->getKey();
    }

    public function ensureNotOwner(User $user): void
    {
        abort_if($this->isOwner($user), 403, 'Owner account changes require the controlled recovery procedure.');
    }

    public function ensureCanManageGroup(Group $group): void
    {
        abort_if($group->slug === 'owner', 403);
    }

    /** @param iterable<string> $slugs */
    public function ensureCanAssign(User $actor, iterable $slugs): void
    {
        $changed = collect($slugs);
        abort_if($changed->diff($actor->permissionSlugs())->isNotEmpty(), 403);
    }

    public function lockAssignments(): void
    {
        // ponytail: serialize RBAC writes; use a dedicated lock if contention matters.
        User::query()->orderBy('id')->lockForUpdate()->get(['id']);
        Group::query()->orderBy('id')->lockForUpdate()->get(['id']);
    }

    public function ensureAnotherAdministrator(?User $excludedUser = null, ?Group $excludedGroup = null): void
    {
        $remaining = User::query()->whereNull('frozen_at')->whereHas('groups', function ($query) use ($excludedGroup) {
            if ($excludedGroup) {
                $query->whereKeyNot($excludedGroup->id);
            }
            $query->whereHas('roles.permissions', fn ($permissions) => $permissions->where('slug', 'admin.access'));
        });
        if ($excludedUser) {
            $remaining->whereKeyNot($excludedUser->id);
        }
        if (! $remaining->exists()) {
            throw ValidationException::withMessages(['groups' => 'At least one administrator must retain access.']);
        }
    }
}
