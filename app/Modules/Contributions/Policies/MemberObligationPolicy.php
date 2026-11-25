<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Policies;

use App\Models\User;
use App\Modules\Contributions\Models\MemberObligation;
use App\Support\Security\PycPermission;

class MemberObligationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PycPermission::DuesView->value);
    }

    public function view(User $user, MemberObligation $obligation): bool
    {
        if ($user->can(PycPermission::DuesView->value)) {
            return true;
        }

        return $user->memberAccount()->where('member_id', $obligation->member_id)->exists();
    }

    public function generate(User $user): bool
    {
        return $user->can(PycPermission::DuesGenerate->value);
    }

    public function adjust(User $user, MemberObligation $obligation): bool
    {
        return $user->can(PycPermission::DuesAdjust->value)
            || $user->can(PycPermission::DuesExempt->value);
    }
}
