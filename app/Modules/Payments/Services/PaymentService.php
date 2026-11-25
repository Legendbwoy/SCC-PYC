<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Models\User;
use App\Modules\Accounting\Contracts\AccountingPostingServiceInterface;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\FinancialApproval;
use App\Modules\Contributions\Contracts\ObligationAllocationServiceInterface;
use App\Modules\Membership\Models\Member;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\DTOs\NotificationRecipient;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Modules\Payments\Contracts\PaymentGatewayInterface;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Payments\Models\PaymentWebhook;
use App\Modules\Payments\Models\Receipt;
use App\Modules\Payments\Models\Refund;
use App\Modules\Payments\Models\Settlement;
use App\Support\Audit\AuditLogger;
use App\Support\Security\MakerCheckerGuard;
use App\Support\Security\PycPermission;
use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Coordinates Paystack online checkout/verification, manual cash maker-checker workflow,
 * obligation allocations, sole-authority journal posting, receipts, refunds, and settlements (Spec Section 7 & 12.3).
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaystackService $paystackWebhookVerifier,
        private readonly ObligationAllocationServiceInterface $allocationService,
        private readonly AccountingPostingServiceInterface $postingService,
        private readonly ReceiptService $receiptService,
        private readonly MakerCheckerGuard $makerCheckerGuard,
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly NotificationOrchestrator $notificationOrchestrator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<array{obligation_id: int, amount_pesewas: int}>  $requestedAllocations
     * @return array{payment: Payment, authorization_url: string}
     */
    public function initializeOnlinePayment(
        Member $member,
        int $amountPesewas,
        array $requestedAllocations = [],
        ?User $actor = null,
    ): array {
        if ($amountPesewas <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero pesewas.');
        }

        $email = $member->email ?: $actor?->email;
        if (empty($email)) {
            throw new InvalidArgumentException('An email address is required to initialize Paystack checkout.');
        }

        $reference = $this->referenceGenerator->next(ReferenceType::Payment);

        $payment = Payment::query()->create([
            'reference' => $reference,
            'member_id' => $member->id,
            'amount_pesewas' => $amountPesewas,
            'currency' => 'GHS',
            'method' => 'paystack',
            'payment_status' => 'pending',
            'approval_status' => 'not_required',
            'requested_allocations' => $requestedAllocations !== [] ? $requestedAllocations : null,
            'recorded_by' => $actor?->id,
        ]);

        $checkout = $this->paymentGateway->initializeTransaction(
            reference: $reference,
            email: $email,
            amountPesewas: $amountPesewas,
            currency: 'GHS',
            metadata: [
                'member_id' => $member->id,
                'member_number' => $member->member_number,
                'payment_id' => $payment->id,
            ],
        );

        $payment->update([
            'provider_authorization_url' => $checkout['authorization_url'],
        ]);

        $this->auditLogger->log(
            event: 'payments.paystack_initialized',
            category: 'finance',
            description: "Initialized Paystack payment [{$payment->reference}] ({$payment->amountMoney()->format()}) for [{$member->member_number}].",
            auditable: $payment,
            actor: $actor,
        );

        return [
            'payment' => $payment->fresh() ?? $payment,
            'authorization_url' => $checkout['authorization_url'],
        ];
    }

    /**
     * Verifies a Paystack transaction reference server-side, validates amount/currency,
     * allocates obligations, posts balanced journal lines, issues receipt once, and notifies post-commit.
     */
    public function verifyOnlinePayment(string $reference, ?User $actor = null): Payment
    {
        $verification = $this->paymentGateway->verifyTransaction($reference);
        $mismatchError = null;
        $failedError = null;

        [$payment, $receipt] = DB::transaction(function () use ($reference, $verification, $actor, &$mismatchError, &$failedError): array {
            $locked = Payment::query()
                ->with(['member.account', 'receipt'])
                ->where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->payment_status === 'posted') {
                return [$locked, $locked->receipt];
            }

            if ($locked->method !== 'paystack') {
                throw new InvalidArgumentException('Only online Paystack payments can be verified via Paystack.');
            }

            if (! $verification['verified']) {
                $locked->update(['payment_status' => 'failed']);
                $failedError = "Paystack verification failed for reference [{$reference}] (status: {$verification['status']}).";

                return [$locked, null];
            }

            // Spec Section 7.3 & 17.2: Never trust client amount/currency — validate strictly against provider API
            if ($verification['currency'] !== $locked->currency || $verification['amount_pesewas'] !== $locked->amount_pesewas) {
                $locked->update(['payment_status' => 'failed']);

                $this->auditLogger->log(
                    event: 'security.payment_verification_mismatch',
                    category: 'security',
                    description: "Blocked Paystack verification mismatch on [{$locked->reference}]: expected {$locked->currency} {$locked->amount_pesewas}p, provider returned {$verification['currency']} {$verification['amount_pesewas']}p.",
                    auditable: $locked,
                    actor: $actor,
                );

                $mismatchError = 'Payment verification mismatch: amount or currency did not match expected payment record.';

                return [$locked, null];
            }

            $locked->update([
                'payment_status' => 'verified',
                'provider_reference' => $verification['provider_reference'],
                'verified_at' => now(),
            ]);

            $receipt = $this->allocatePostAndReceipt($locked, '1030', $actor);

            return [$locked->fresh(['receipt', 'allocations', 'journalEntry']), $receipt];
        });

        if ($failedError !== null) {
            throw new InvalidArgumentException($failedError);
        }

        if ($mismatchError !== null) {
            throw new InvalidArgumentException($mismatchError);
        }

        // Critical Payment Notification Rule (Spec Section 10.18): dispatch ONLY after commit, fail-safely!
        if ($receipt instanceof Receipt && $payment instanceof Payment) {
            $this->dispatchReceiptNotifications($payment, $receipt, false, $actor);
        }

        return $payment;
    }

    /**
     * Validates Paystack HMAC-SHA512 signature, deduplicates provider event ID, and verifies server-side (Spec 13.7).
     */
    public function handlePaystackWebhook(string $rawPayload, string $signature): PaymentWebhook
    {
        if (! $this->paystackWebhookVerifier->verifySignature($rawPayload, $signature)) {
            throw new AuthorizationException('Invalid Paystack webhook signature.');
        }

        $decoded = json_decode($rawPayload, true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Malformed webhook JSON payload.');
        }

        $eventType = (string) ($decoded['event'] ?? 'unknown');
        $reference = (string) data_get($decoded, 'data.reference', '');
        $providerEventId = (string) (data_get($decoded, 'data.id') ?: ($eventType.':'.$reference));
        $payloadHash = hash('sha256', $rawPayload);

        try {
            $webhook = PaymentWebhook::query()->create([
                'provider' => 'paystack',
                'provider_event_id' => $providerEventId,
                'event_type' => $eventType,
                'reference' => $reference !== '' ? $reference : null,
                'payload_hash' => $payloadHash,
                'payload' => $decoded,
                'processing_status' => 'received',
            ]);
        } catch (QueryException) {
            return PaymentWebhook::query()
                ->where('provider', 'paystack')
                ->where('provider_event_id', $providerEventId)
                ->firstOrFail();
        }

        if ($eventType !== 'charge.success' || $reference === '') {
            $webhook->update([
                'processing_status' => 'ignored',
                'processed_at' => now(),
            ]);

            return $webhook;
        }

        try {
            $this->verifyOnlinePayment($reference);
            $webhook->update([
                'processing_status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $webhook->update([
                'processing_status' => 'failed',
                'processed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }

        return $webhook;
    }

    /**
     * Records a manual cash payment awaiting independent maker-checker approval (Spec Section 7.2 & 7.3).
     *
     * @param  list<array{obligation_id: int, amount_pesewas: int}>  $requestedAllocations
     */
    public function recordCashPayment(
        Member $member,
        int $amountPesewas,
        User $recorder,
        ?string $notes = null,
        array $requestedAllocations = [],
    ): Payment {
        if (! $recorder->can(PycPermission::PaymentsRecordCash->value)) {
            throw new AuthorizationException('You are not authorized to record manual cash payments.');
        }

        if ($amountPesewas <= 0) {
            throw new InvalidArgumentException('Cash payment amount must be greater than zero pesewas.');
        }

        $payment = DB::transaction(function () use ($member, $amountPesewas, $recorder, $notes, $requestedAllocations): Payment {
            $reference = $this->referenceGenerator->next(ReferenceType::Payment);

            $created = Payment::query()->create([
                'reference' => $reference,
                'member_id' => $member->id,
                'amount_pesewas' => $amountPesewas,
                'currency' => 'GHS',
                'method' => 'cash',
                'payment_status' => 'pending',
                'approval_status' => 'awaiting_approval',
                'requested_allocations' => $requestedAllocations !== [] ? $requestedAllocations : null,
                'recorded_by' => $recorder->id,
                'notes' => $notes,
            ]);

            $this->auditLogger->log(
                event: 'payments.cash_recorded',
                category: 'finance',
                description: "Recorded manual cash payment [{$created->reference}] ({$created->amountMoney()->format()}) awaiting independent approval.",
                auditable: $created,
                actor: $recorder,
            );

            return $created;
        });

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "cash_payment_pending:{$payment->id}",
            templateCode: 'cash_payment_pending',
            recipient: new NotificationRecipient(
                userId: $member->account?->user_id,
                memberId: (int) $member->id,
                name: $member->fullName(),
                email: $member->email,
                phone: $member->phone,
            ),
            variables: [
                'member_name' => $member->first_name,
                'amount' => $payment->amountMoney()->formattedAmount(),
                'payment_reference' => $payment->reference,
            ],
            channels: ['sms', 'email'],
            eventName: 'payments.cash_recorded',
            relatedType: $payment->getMorphClass(),
            relatedId: (int) $payment->id,
            initiatedByUserId: (int) $recorder->id,
            failSafely: true,
        ));

        return $payment;
    }

    /**
     * Independently approves a recorded cash payment, allocates obligations, posts to Cash in Hand (1010), and issues receipt.
     */
    public function approveCashPayment(Payment $payment, User $approver, ?string $notes = null): Payment
    {
        [$approvedPayment, $receipt] = DB::transaction(function () use ($payment, $approver, $notes): array {
            $locked = Payment::query()
                ->with(['member.account', 'receipt'])
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($locked->method !== 'cash') {
                throw new InvalidArgumentException('Only manual cash payments use the cash approval workflow.');
            }

            if ($locked->payment_status === 'posted' && $locked->approval_status === 'approved') {
                return [$locked, $locked->receipt];
            }

            if ($locked->approval_status !== 'awaiting_approval') {
                throw new InvalidArgumentException("Cash payment [{$locked->reference}] is not awaiting approval.");
            }

            $this->makerCheckerGuard->assertCanApprove(
                approver: $approver,
                initiatorUserId: (int) $locked->recorded_by,
                requiredPermission: PycPermission::PaymentsApprove,
                contextLabel: "cash payment [{$locked->reference}]",
                auditable: $locked,
            );

            $locked->update([
                'approval_status' => 'approved',
                'payment_status' => 'verified',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'verified_at' => now(),
            ]);

            FinancialApproval::query()->create([
                'approvable_type' => $locked->getMorphClass(),
                'approvable_id' => $locked->id,
                'action_type' => 'cash_payment',
                'decision' => 'approved',
                'reason' => $notes ?: 'Independently verified cash receipt',
                'initiator_id' => (int) $locked->recorded_by,
                'approver_id' => $approver->id,
            ]);

            $receipt = $this->allocatePostAndReceipt($locked, '1010', $approver);

            return [$locked->fresh(['receipt', 'allocations', 'journalEntry']), $receipt];
        });

        if ($receipt instanceof Receipt && $approvedPayment instanceof Payment) {
            $this->dispatchReceiptNotifications($approvedPayment, $receipt, true, $approver);
        }

        return $approvedPayment;
    }

    public function rejectCashPayment(Payment $payment, User $approver, string $reason): Payment
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $rejected = DB::transaction(function () use ($payment, $approver, $reason): Payment {
            $locked = Payment::query()->with('member.account')->lockForUpdate()->findOrFail($payment->id);

            if ($locked->method !== 'cash' || $locked->approval_status !== 'awaiting_approval') {
                throw new InvalidArgumentException('Only cash payments awaiting approval can be rejected.');
            }

            $this->makerCheckerGuard->assertCanApprove(
                approver: $approver,
                initiatorUserId: (int) $locked->recorded_by,
                requiredPermission: PycPermission::PaymentsApprove,
                contextLabel: "cash payment [{$locked->reference}]",
                auditable: $locked,
            );

            $locked->update([
                'approval_status' => 'rejected',
                'payment_status' => 'failed',
            ]);

            FinancialApproval::query()->create([
                'approvable_type' => $locked->getMorphClass(),
                'approvable_id' => $locked->id,
                'action_type' => 'cash_payment',
                'decision' => 'rejected',
                'reason' => $reason,
                'initiator_id' => (int) $locked->recorded_by,
                'approver_id' => $approver->id,
            ]);

            $this->auditLogger->log(
                event: 'payments.cash_rejected',
                category: 'finance',
                description: "Rejected cash payment [{$locked->reference}]: {$reason}",
                auditable: $locked,
                actor: $approver,
            );

            return $locked;
        });

        if ($rejected->member !== null) {
            $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: "cash_payment_rejected:{$rejected->id}",
                templateCode: 'cash_payment_rejected',
                recipient: new NotificationRecipient(
                    userId: $rejected->member->account?->user_id,
                    memberId: (int) $rejected->member->id,
                    name: $rejected->member->fullName(),
                    email: $rejected->member->email,
                    phone: $rejected->member->phone,
                ),
                variables: [
                    'member_name' => $rejected->member->first_name,
                    'amount' => $rejected->amountMoney()->formattedAmount(),
                    'payment_reference' => $rejected->reference,
                    'reason' => $reason,
                ],
                channels: ['sms', 'email'],
                failSafely: true,
            ));
        }

        return $rejected;
    }

    /**
     * Processes an authorized refund with ledger reversal posting (Spec Section 7.3 & 13.4).
     */
    public function refundPayment(
        Payment $payment,
        int $refundPesewas,
        string $reason,
        string $refundChannel,
        User $actor,
    ): Refund {
        if (! $actor->can(PycPermission::PaymentsRefund->value)) {
            throw new AuthorizationException('You are not authorized to process payment refunds.');
        }

        if ($refundPesewas <= 0 || trim($reason) === '') {
            throw new InvalidArgumentException('Valid positive refund amount and reason are required.');
        }

        $refund = DB::transaction(function () use ($payment, $refundPesewas, $reason, $refundChannel, $actor): Refund {
            $locked = Payment::query()->with(['member.account', 'allocations.obligation.campaign.type'])->lockForUpdate()->findOrFail($payment->id);

            if ($locked->payment_status !== 'posted') {
                throw new InvalidArgumentException('Only posted payments can be refunded.');
            }

            if ($refundPesewas > $locked->amount_pesewas) {
                throw new InvalidArgumentException('Refund amount cannot exceed original payment amount.');
            }

            $reference = $this->referenceGenerator->next(ReferenceType::Refund);
            $creditAssetCode = $refundChannel === 'cash' ? '1010' : '1020';
            $firstIncomeCode = $locked->allocations->first()?->obligation?->campaign?->type?->default_income_account_code ?? '4000';

            $journal = $this->postingService->post(
                sourceType: 'refund',
                sourceId: (int) $locked->id,
                entryDate: now(),
                narration: "Refund {$reference} for payment {$locked->reference}: {$reason}",
                lines: [
                    new JournalLineData(accountCode: (string) $firstIncomeCode, debitPesewas: $refundPesewas, description: "Refund against {$locked->reference}"),
                    new JournalLineData(accountCode: $creditAssetCode, creditPesewas: $refundPesewas, description: "Refund payout {$reference}"),
                ],
                actor: $actor,
                idempotencyKey: "refund:{$reference}",
            );

            $createdRefund = Refund::query()->create([
                'reference' => $reference,
                'payment_id' => $locked->id,
                'amount_pesewas' => $refundPesewas,
                'currency' => 'GHS',
                'reason' => $reason,
                'refund_channel' => $refundChannel,
                'status' => 'processed',
                'requested_by' => $actor->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'processed_at' => now(),
                'journal_entry_id' => $journal->id,
            ]);

            $locked->update(['payment_status' => 'refunded']);

            $this->auditLogger->log(
                event: 'payments.refunded',
                category: 'finance',
                description: "Processed refund [{$createdRefund->reference}] ({$refundPesewas}p) for payment [{$locked->reference}].",
                auditable: $createdRefund,
                actor: $actor,
            );

            return $createdRefund;
        });

        $member = $payment->member;
        if ($member !== null) {
            $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: "refund_processed:{$refund->id}",
                templateCode: 'refund_processed',
                recipient: new NotificationRecipient(
                    userId: $member->account?->user_id,
                    memberId: (int) $member->id,
                    name: $member->fullName(),
                    email: $member->email,
                    phone: $member->phone,
                ),
                variables: [
                    'member_name' => $member->first_name,
                    'amount' => number_format($refund->amount_pesewas / 100, 2),
                    'refund_reference' => $refund->reference,
                ],
                channels: ['sms', 'email'],
                failSafely: true,
            ));
        }

        return $refund;
    }

    /**
     * Records a Paystack bank settlement and posts Dr Bank (1020) + Dr Charges (5400) / Cr Paystack Clearing (1030) (Spec Section 8.3).
     */
    public function recordSettlement(
        string $providerSettlementReference,
        int $grossPesewas,
        int $feePesewas,
        string $settlementDate,
        User $actor,
    ): Settlement {
        if (! $actor->can(PycPermission::AccountingPost->value)) {
            throw new AuthorizationException('You are not authorized to post provider settlements.');
        }

        if ($grossPesewas <= 0 || $feePesewas < 0 || $feePesewas >= $grossPesewas) {
            throw new InvalidArgumentException('Invalid settlement gross/fee amounts.');
        }

        $netPesewas = $grossPesewas - $feePesewas;

        return DB::transaction(function () use ($providerSettlementReference, $grossPesewas, $feePesewas, $netPesewas, $settlementDate, $actor): Settlement {
            $lines = [
                new JournalLineData(accountCode: '1020', debitPesewas: $netPesewas, description: "Paystack bank settlement {$providerSettlementReference}"),
            ];

            if ($feePesewas > 0) {
                $lines[] = new JournalLineData(accountCode: '5400', debitPesewas: $feePesewas, description: "Paystack settlement fee {$providerSettlementReference}");
            }

            $lines[] = new JournalLineData(accountCode: '1030', creditPesewas: $grossPesewas, description: "Clear Paystack receivable {$providerSettlementReference}");

            $settlement = Settlement::query()->create([
                'provider' => 'paystack',
                'provider_settlement_reference' => $providerSettlementReference,
                'gross_amount_pesewas' => $grossPesewas,
                'fee_amount_pesewas' => $feePesewas,
                'net_amount_pesewas' => $netPesewas,
                'currency' => 'GHS',
                'settlement_date' => $settlementDate,
                'status' => 'posted',
                'recorded_by' => $actor->id,
            ]);

            $journal = $this->postingService->post(
                sourceType: 'settlement',
                sourceId: (int) $settlement->id,
                entryDate: Carbon::parse($settlementDate),
                narration: "Paystack settlement {$providerSettlementReference}",
                lines: $lines,
                actor: $actor,
                idempotencyKey: "settlement:{$providerSettlementReference}",
            );

            $settlement->update(['journal_entry_id' => $journal->id]);

            return $settlement->fresh(['journalEntry']) ?? $settlement;
        });
    }

    private function allocatePostAndReceipt(Payment $lockedPayment, string $debitAssetAccountCode, ?User $actor): Receipt
    {
        /** @var list<array{obligation_id: int, amount_pesewas: int}> $explicit */
        $explicit = is_array($lockedPayment->requested_allocations) ? $lockedPayment->requested_allocations : [];

        $slices = $this->allocationService->allocate(
            memberId: (int) $lockedPayment->member_id,
            amountPesewas: $lockedPayment->amount_pesewas,
            explicitAllocations: $explicit,
            allowAdvanceGeneration: true,
        );

        foreach ($slices as $slice) {
            PaymentAllocation::query()->create([
                'payment_id' => $lockedPayment->id,
                'member_obligation_id' => $slice['obligation_id'],
                'amount_pesewas' => $slice['amount_pesewas'],
            ]);
        }

        // Group credits by income account code (e.g. 4000 Monthly Dues Income, 4100 Event Contributions Income)
        $creditsByAccount = [];
        foreach ($slices as $slice) {
            $code = $slice['income_account_code'];
            $creditsByAccount[$code] = ($creditsByAccount[$code] ?? 0) + $slice['amount_pesewas'];
        }

        $journalLines = [
            new JournalLineData(
                accountCode: $debitAssetAccountCode,
                debitPesewas: $lockedPayment->amount_pesewas,
                description: "Member payment {$lockedPayment->reference} ({$lockedPayment->method})"
            ),
        ];

        foreach ($creditsByAccount as $incomeCode => $creditAmount) {
            $journalLines[] = new JournalLineData(
                accountCode: (string) $incomeCode,
                creditPesewas: $creditAmount,
                description: "Allocated contribution income ({$lockedPayment->reference})"
            );
        }

        $journal = $this->postingService->post(
            sourceType: 'payment',
            sourceId: (int) $lockedPayment->id,
            entryDate: now(),
            narration: "Member payment {$lockedPayment->reference} ({$lockedPayment->member?->member_number})",
            lines: $journalLines,
            actor: $actor,
            idempotencyKey: "payment:post:{$lockedPayment->id}",
        );

        $lockedPayment->update([
            'payment_status' => 'posted',
            'posted_at' => now(),
            'journal_entry_id' => $journal->id,
        ]);

        $receipt = $this->receiptService->issueOnceForPostedPayment($lockedPayment);

        $this->auditLogger->log(
            event: 'payments.posted_and_receipted',
            category: 'finance',
            description: "Posted payment [{$lockedPayment->reference}] via journal [{$journal->journal_number}] and issued receipt [{$receipt->receipt_number}].",
            auditable: $lockedPayment,
            actor: $actor,
        );

        return $receipt;
    }

    private function dispatchReceiptNotifications(Payment $payment, Receipt $receipt, bool $isCashApproval, ?User $actor): void
    {
        $member = $payment->member;
        if ($member === null) {
            return;
        }

        $recipient = new NotificationRecipient(
            userId: $member->account?->user_id,
            memberId: (int) $member->id,
            name: $member->fullName(),
            email: $member->email,
            phone: $member->phone,
        );

        $contributionLabel = $receipt->snapshot['allocations'][0]['campaign'] ?? 'PYC Contributions';
        $paymentDate = ($payment->posted_at ?? now())->timezone('Africa/Accra')->format('d M Y');

        if ($isCashApproval) {
            $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
                idempotencyKey: "cash_payment_approved:{$payment->id}",
                templateCode: 'cash_payment_approved',
                recipient: $recipient,
                variables: [
                    'amount' => $payment->amountMoney()->formattedAmount(),
                    'contribution' => (string) $contributionLabel,
                    'receipt_number' => $receipt->receipt_number,
                    'payment_date' => $paymentDate,
                ],
                channels: ['sms', 'email'],
                initiatedByUserId: $actor?->id,
                failSafely: true,
            ));
        }

        // Email + In-App full receipt details; SMS concise receipt per Section 10.9
        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "payment_receipt:sms:{$receipt->id}",
            templateCode: 'payment_receipt',
            recipient: $recipient,
            variables: [
                'amount' => $payment->amountMoney()->formattedAmount(),
                'contribution' => (string) $contributionLabel,
                'receipt_number' => $receipt->receipt_number,
                'payment_date' => $paymentDate,
            ],
            channels: ['sms'],
            initiatedByUserId: $actor?->id,
            failSafely: true,
        ));

        $this->notificationOrchestrator->dispatch(new DispatchNotificationData(
            idempotencyKey: "payment_receipt:email:{$receipt->id}",
            templateCode: 'payment_receipt',
            recipient: $recipient,
            variables: [
                'member_name' => $member->fullName(),
                'amount' => $payment->amountMoney()->formattedAmount(),
                'contribution' => (string) $contributionLabel,
                'receipt_number' => $receipt->receipt_number,
                'payment_date' => $paymentDate,
                'payment_method' => strtoupper($payment->method),
                'payment_reference' => $payment->reference,
            ],
            channels: ['email', 'in_app'],
            initiatedByUserId: $actor?->id,
            failSafely: true,
        ));
    }
}
