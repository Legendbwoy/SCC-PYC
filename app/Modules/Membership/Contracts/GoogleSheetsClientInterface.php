<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

/**
 * Isolated Google Sheets / Forms response synchronization contract (Spec Section 5.3 & 5.4).
 */
interface GoogleSheetsClientInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchResponses(?string $sinceCursor = null): array;
}
