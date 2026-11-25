<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Receipt;
use App\Support\Shared\ReferenceGenerator;
use App\Support\Shared\ReferenceType;
use App\Support\Shared\SystemSettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * Generates official PYC receipts once after verification, allocation, and journal posting (Spec Section 7.3 & 10.9).
 */
class ReceiptService
{
    public function __construct(
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly SystemSettingService $settingService,
    ) {}

    public function issueOnceForPostedPayment(Payment $payment): Receipt
    {
        if ($payment->payment_status !== 'posted' || $payment->journal_entry_id === null) {
            throw new InvalidArgumentException('Official receipts can only be issued after successful allocation and journal posting.');
        }

        $existing = Receipt::query()->where('payment_id', $payment->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $payment->loadMissing(['member', 'allocations.obligation.campaign']);

        $receiptNumber = $this->referenceGenerator->next(ReferenceType::Receipt, (int) ($payment->posted_at?->year ?? now()->year));

        $allocationsSummary = $payment->allocations->map(static fn ($alloc): array => [
            'campaign' => $alloc->obligation?->campaign?->title ?? 'Contribution',
            'period' => $alloc->obligation?->period_key ?? '',
            'amount_pesewas' => $alloc->amount_pesewas,
        ])->values()->all();

        return Receipt::query()->create([
            'receipt_number' => $receiptNumber,
            'payment_id' => $payment->id,
            'member_id' => $payment->member_id,
            'amount_pesewas' => $payment->amount_pesewas,
            'currency' => $payment->currency,
            'issued_at' => now(),
            'snapshot' => [
                'member_name' => $payment->member?->fullName(),
                'member_number' => $payment->member?->member_number,
                'payment_reference' => $payment->reference,
                'payment_method' => strtoupper($payment->method),
                'allocations' => $allocationsSummary,
            ],
        ]);
    }

    public function downloadPdf(Receipt $receipt): Response
    {
        $receipt->loadMissing(['payment.journalEntry', 'member']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
            'branding' => $this->settingService->branding(),
        ]);

        return $pdf->download("{$receipt->receipt_number}.pdf");
    }
}
