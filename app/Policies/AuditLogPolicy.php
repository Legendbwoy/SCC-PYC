<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Audit\Models\AuditLog;
use App\Support\Security\PycPermission;

class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(PycPermission::AuditLogsView->value);
    }

    public function view(User $actor, AuditLog $auditLog): bool
    {
        return $actor->can(PycPermission::AuditLogsView->value);
    }

    public function update(): bool
    {
        return false;
    }

    public function delete(): bool
    {
        return false;
    }
}
