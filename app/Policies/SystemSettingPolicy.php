<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Security\PycPermission;
use App\Support\Shared\Models\SystemSetting;

class SystemSettingPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PycPermission::SettingsManage->value);
    }

    public function update(User $actor, ?SystemSetting $setting = null): bool
    {
        return $actor->can(PycPermission::SettingsManage->value);
    }
}
