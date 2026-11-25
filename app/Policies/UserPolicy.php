<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Security\PycPermission;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PycPermission::UsersManage->value);
    }

    public function view(User $actor, User $target): bool
    {
        return (int) $actor->getKey() === (int) $target->getKey()
            || $actor->can(PycPermission::UsersManage->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(PycPermission::UsersManage->value);
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can(PycPermission::UsersManage->value);
    }
}
