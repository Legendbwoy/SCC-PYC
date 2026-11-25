<?php

declare(strict_types=1);

namespace App\Modules\Membership\Enums;

enum MemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
