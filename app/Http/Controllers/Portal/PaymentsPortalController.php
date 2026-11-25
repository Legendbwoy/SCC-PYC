<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Membership\Models\Member;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Settlement;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Payments\Services\ReceiptService;
use App\Support\Security\PycPermission;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PaymentsPortalController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ReceiptService $receiptService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $status = $request->string('status')->trim()->toString();
        $method = $request->string('method')->trim()->toString();

        $query = Payment::query()->with(['member', 'receipt', 'recorder', 'approver'])->latest('id');

        if ($status !== '') {
            $query->where('payment_status', $status);
        }

        if ($method !== '') {
            $query->where('method', $method);
        }

        $awaitingApproval = Payment::query()
            ->with(['member', 'recorder'])
            ->where('approval_status', 'awaiting_approval')
            ->latest('id')
            ->get();

        $postedTotal = (int) Payment::query()->where('payment_status', 'posted')->sum('amount_pesewas');

        return view('portal.payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'awaitingApproval' => $awaitingApproval,
            'postedTotal' => Money::fromPesewas($postedTotal),
            'activeMembers' => Member::query()->where('status', 'active')->orderBy('first_name')->get(),
            'settlements' => Settlement::query()->latest('id')->limit(10)->get(),
            'filters' => ['status' => $status, 'method' => $method],
        ]);
    }

    public function recordCash(Request $request): RedirectResponse
    {
        /** @var User $recorder */
        $recorder = $request->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'amount_ghs' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $member = Member::query()->findOrFail((int) $validated['member_id']);
        $money = Money::fromMajorString($validated['amount_ghs'], 'GHS');

        $payment = $this->paymentService->recordCashPayment(
            member: $member,
            amountPesewas: $money->pesewas,
            recorder: $recorder,
            notes: $validated['notes'] ?? null,
        );

        return redirect()
            ->route('payments.index')
            ->with('status', "Recorded cash payment {$payment->reference} ({$money->format()}) — awaiting independent officer approval.");
    }

    public function approveCash(Request $request, Payment $payment): RedirectResponse
    {
        /** @var User $approver */
        $approver = $request->user();

        $approved = $this->paymentService->approveCashPayment(
            payment: $payment,
            approver: $approver,
            notes: $request->string('reason')->trim()->toString() ?: null,
        );

        return redirect()
            ->route('payments.index')
            ->with('status', "Approved {$approved->reference}, posted journal, and issued receipt {$approved->receipt?->receipt_number}.");
    }

    public function rejectCash(Request $request, Payment $payment): RedirectResponse
    {
        /** @var User $approver */
        $approver = $request->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->paymentService->rejectCashPayment($payment, $approver, $validated['reason']);

        return redirect()
            ->route('payments.index')
            ->with('status', "Rejected cash entry {$payment->reference}.");
    }

    public function downloadReceiptPdf(Payment $payment): Response
    {
        Gate::authorize('view', $payment);

        $receipt = $payment->receipt;
        abort_if($receipt === null, 404, 'Receipt not found.');

        return $this->receiptService->downloadPdf($receipt);
    }

    public function storeSettlement(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::AccountingPost->value), 403);

        $validated = $request->validate([
            'provider_settlement_reference' => ['required', 'string', 'max:160', 'unique:settlements,provider_settlement_reference'],
            'gross_ghs' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'fee_ghs' => ['required', 'string', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'settlement_date' => ['required', 'date'],
        ]);

        $gross = Money::fromMajorString($validated['gross_ghs'], 'GHS');
        $fee = Money::fromMajorString($validated['fee_ghs'], 'GHS');

        $settlement = $this->paymentService->recordSettlement(
            providerSettlementReference: trim($validated['provider_settlement_reference']),
            grossPesewas: $gross->pesewas,
            feePesewas: $fee->pesewas,
            settlementDate: $validated['settlement_date'],
            actor: $actor,
        );

        return redirect()
            ->route('payments.index')
            ->with('status', "Recorded Paystack settlement {$settlement->provider_settlement_reference} and posted to bank ledger.");
    }
}
