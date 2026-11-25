<?php

declare(strict_types=1);

namespace App\Modules\Expenses\Contracts;

use App\Models\User;

interface ExpenseApprovalServiceInterface
{
    public function approve(int $expenseId, User $approver, ?string $notes = null): void;

    public function reject(int $expenseId, User $approver, string $reason): void;
}
