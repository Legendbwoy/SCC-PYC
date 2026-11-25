<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Enums;

/**
 * Official delivery status definitions (Spec Section 10.12).
 */
enum DeliveryStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Accepted = 'accepted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Retrying = 'retrying';
    case Cancelled = 'cancelled';
    case Unknown = 'unknown';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Failed, self::Cancelled], true);
    }
}
