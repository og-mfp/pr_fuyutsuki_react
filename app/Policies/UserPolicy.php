<?php

namespace App\Policies;

use App\Models\User;
use App\Services\AdminAccessGuard;

class UserPolicy
{
    private function canManage(User $user): bool
    {
        return ! app(AdminAccessGuard::class)->isOwner($user);
    }

    private function canManageAccount(User $actor, User $user): bool
    {
        return $this->canManage($user)
            && array_diff($user->assignedPermissionSlugs(), $actor->permissionSlugs()) === [];
    }

    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('users.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->hasPermission('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission('users.create');
    }

    public function update(User $actor, User $user): bool
    {
        return $this->canManage($user) && $actor->hasPermission('users.update');
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->canManageAccount($actor, $user) && $actor->isNot($user) && $actor->hasPermission('users.delete');
    }

    public function resetPassword(User $actor, User $user): bool
    {
        return $this->canManageAccount($actor, $user) && $actor->hasPermission('users.update');
    }

    public function freeze(User $actor, User $user): bool
    {
        return $this->canManageAccount($actor, $user) && $actor->isNot($user) && $actor->hasPermission('users.update');
    }

    public function unfreeze(User $actor, User $user): bool
    {
        return $this->canManageAccount($actor, $user) && $actor->hasPermission('users.update');
    }
}
