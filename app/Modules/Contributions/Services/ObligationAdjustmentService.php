<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Services;

use App\Models\User;
use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Contributions\Models\ObligationAdjustment;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Applies authorized waivers, exemptions, and adjustments with reasons and audit records (Spec Section 6.1 & 6.2).
 */
class ObligationAdjustmentService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function applyAdjustment(
        MemberObligation $obligation,
        string $adjustmentType,
        int $amountPesewas,
        string $reason,
        User $actor,
    ): ObligationAdjustment {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('An audit reason is required for obligation adjustments.');
        }

        if ($adjustmentType === 'exemption') {
            if (! $actor->can(PycPermission::DuesExempt->value)) {
                throw new AuthorizationException('You do not have permission to grant dues exemptions.');
            }
        } else {
            if (! $actor->can(PycPermission::DuesAdjust->value)) {
                throw new AuthorizationException('You do not have permission to adjust member obligations.');
            }
        }

        return DB::transaction(function () use ($obligation, $adjustmentType, $amountPesewas, $reason, $actor): ObligationAdjustment {
            $locked = MemberObligation::query()->lockForUpdate()->findOrFail($obligation->id);

            // Normalize sign: waivers, exemptions, and discounts reduce the obligation (negative signed pesewas)
            $signedPesewas = match ($adjustmentType) {
                'exemption' => -1 * $locked->outstandingPesewas(),
                'waiver', 'discount' => -1 * abs($amountPesewas),
                'surcharge' => abs($amountPesewas),
                default => throw new InvalidArgumentException("Unsupported adjustment type [{$adjustmentType}]."),
            };

            if ($signedPesewas === 0) {
                throw new InvalidArgumentException('Adjustment amount must be non-zero.');
            }

            if (($locked->amount_due_pesewas + $locked->adjustment_pesewas + $signedPesewas) < $locked->amount_allocated_pesewas) {
                throw new InvalidArgumentException('Adjustment cannot reduce the required obligation below already allocated payments.');
            }

            $reference = 'PYC-ADJ-'.now()->year.'-'.strtoupper(Str::random(6));

            $adjustment = ObligationAdjustment::query()->create([
                'member_obligation_id' => $locked->id,
                'adjustment_type' => $adjustmentType,
                'amount_pesewas' => $signedPesewas,
                'reason' => $reason,
                'status' => 'approved',
                'approval_reference' => $reference,
                'requested_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->recalculateObligationState($locked, $adjustmentType);

            $this->auditLogger->log(
                event: 'dues.obligation_adjusted',
                category: 'finance',
                description: "Applied [{$adjustmentType}] ({$signedPesewas}p) to obligation #{$locked->id} ({$locked->period_key}).",
                auditable: $locked,
                newValues: [
                    'adjustment_type' => $adjustmentType,
                    'signed_pesewas' => $signedPesewas,
                    'new_status' => $locked->status,
                    'reason' => $reason,
                    'approval_reference' => $reference,
                ],
                actor: $actor,
            );

            return $adjustment;
        });
    }

    public function recalculateObligationState(MemberObligation $locked, ?string $latestAdjustmentType = null): void
    {
        $totalAdjustments = (int) ObligationAdjustment::query()
            ->where('member_obligation_id', $locked->id)
            ->where('status', 'approved')
            ->sum('amount_pesewas');

        $effectiveRequired = max(0, $locked->amount_due_pesewas + $totalAdjustments);

        if ($effectiveRequired === 0 && $locked->amount_allocated_pesewas === 0) {
            $newStatus = $latestAdjustmentType === 'exemption'
                ? ObligationStatus::Exempt->value
                : ObligationStatus::Waived->value;
        } elseif ($locked->amount_allocated_pesewas >= $effectiveRequired) {
            $newStatus = ObligationStatus::Paid->value;
        } elseif ($locked->amount_allocated_pesewas > 0) {
            $newStatus = ObligationStatus::PartiallyPaid->value;
        } else {
            $newStatus = ObligationStatus::Unpaid->value;
        }

        $locked->update([
            'adjustment_pesewas' => $totalAdjustments,
            'status' => $newStatus,
        ]);
    }
}
