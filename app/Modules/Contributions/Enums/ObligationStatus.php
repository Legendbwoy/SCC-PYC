<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Enums;

enum ObligationStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Waived = 'waived';
    case Exempt = 'exempt';
}
