<?php

declare(strict_types=1);

namespace App\Modules\Membership\Policies;

use App\Models\User;
use App\Modules\Membership\Models\Member;
use App\Support\Security\PycPermission;

/**
 * Enforces RBAC and strict Member data isolation (Spec Section 3.5, 4.2 & 17.1).
 */
class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PycPermission::MembersView->value);
    }

    public function view(User $user, Member $member): bool
    {
        if ($user->can(PycPermission::MembersView->value)) {
            return true;
        }

        // Members may access ONLY their own linked Member record
        return $user->memberAccount()->where('member_id', $member->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(PycPermission::MembersCreate->value);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->can(PycPermission::MembersUpdate->value);
    }

    public function approve(User $user, Member $member): bool
    {
        return $user->can(PycPermission::MembersApprove->value);
    }

    public function archive(User $user, Member $member): bool
    {
        return $user->can(PycPermission::MembersArchive->value);
    }
}
