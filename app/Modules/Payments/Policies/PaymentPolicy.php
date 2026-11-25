<?php

declare(strict_types=1);

namespace App\Modules\Payments\Policies;

use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Support\Security\PycPermission;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PycPermission::PaymentsView->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->can(PycPermission::PaymentsView->value)) {
            return true;
        }

        return $user->memberAccount()->where('member_id', $payment->member_id)->exists();
    }
}
