<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('groups.view');
    }

    public function view(User $actor, Group $group): bool
    {
        return $actor->hasPermission('groups.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('groups.create');
    }

    public function update(User $actor, Group $group): bool
    {
        return $group->slug !== 'owner' && $actor->hasPermission('groups.update');
    }

    public function delete(User $actor, Group $group): bool
    {
        return ! in_array($group->slug, ['owner', 'std-usr'], true) && $actor->hasPermission('groups.delete');
    }
}
