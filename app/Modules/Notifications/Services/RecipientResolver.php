<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\DTOs\NotificationRecipient;

class RecipientResolver
{
    public function fromUser(User $user, ?int $memberId = null): NotificationRecipient
    {
        return new NotificationRecipient(
            userId: (int) $user->id,
            memberId: $memberId,
            name: $user->name,
            email: $this->normalizeEmail($user->email),
            phone: $this->normalizePhone($user->phone),
        );
    }

    /**
     * Normalizes Ghana local numbers (e.g. 0241234567 -> +233241234567) and international E.164 numbers.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $cleaned = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        if ($cleaned === '') {
            return null;
        }

        if (preg_match('/^0([235]\d{8})$/', $cleaned, $matches)) {
            return '+233'.$matches[1];
        }

        if (preg_match('/^233([235]\d{8})$/', $cleaned, $matches)) {
            return '+233'.$matches[1];
        }

        if (preg_match('/^\+[1-9]\d{7,14}$/', $cleaned)) {
            return $cleaned;
        }

        return null;
    }

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
    }
}
