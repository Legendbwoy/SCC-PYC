<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Security\Exceptions\MakerCheckerViolationException;
use Illuminate\Database\Eloquent\Model;

/**
 * Enforces dual authorization / maker-checker controls across financial workflows (Spec Section 3.5 & 4.3).
 */
class MakerCheckerGuard
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Verifies that $approver holds $requiredPermission and did not initiate/record the transaction.
     *
     * @throws MakerCheckerViolationException
     */
    public function assertCanApprove(
        User $approver,
        int $initiatorUserId,
        PycPermission $requiredPermission,
        string $contextLabel = 'financial transaction',
        ?Model $auditable = null,
    ): void {
        if (! $approver->isActive()) {
            throw new MakerCheckerViolationException('Inactive user accounts cannot authorize transactions.');
        }

        if (! $approver->can($requiredPermission->value)) {
            $this->auditLogger->log(
                event: 'security.authorization_denied',
                category: 'security',
                description: "Denied [{$requiredPermission->value}] for {$contextLabel}: missing permission.",
                auditable: $auditable,
                metadata: ['initiator_user_id' => $initiatorUserId],
                actor: $approver,
            );

            throw new MakerCheckerViolationException(
                "You are not authorized to approve this {$contextLabel}."
            );
        }

        $allowSuperAdminSelfApproval = (bool) config('pyc.approvals.allow_super_admin_self_approval', false);

        if ((int) $approver->getKey() === $initiatorUserId && ! $allowSuperAdminSelfApproval) {
            $this->auditLogger->log(
                event: 'security.maker_checker_blocked',
                category: 'security',
                description: "Maker-checker violation blocked: user attempted to approve their own {$contextLabel}.",
                auditable: $auditable,
                metadata: [
                    'initiator_user_id' => $initiatorUserId,
                    'approver_user_id' => $approver->getKey(),
                    'permission' => $requiredPermission->value,
                ],
                actor: $approver,
            );

            throw new MakerCheckerViolationException(
                "Maker-checker control: the officer who recorded or submitted this {$contextLabel} cannot approve it."
            );
        }
    }
}
