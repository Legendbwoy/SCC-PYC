<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Membership\Models\Member;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Payment & Receipt REST API v1 endpoints (Spec Section 13.4 & 13.8).
 */
class PaymentsApiController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Payment::class);

        $query = Payment::query()->with(['member', 'receipt'])->latest('id');

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->string('approval_status')->toString());
        }

        if ($request->filled('method')) {
            $query->where('method', $request->string('method')->toString());
        }

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved.',
            'data' => $query->paginate(20),
        ]);
    }

    public function initialize(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'amount_pesewas' => ['required', 'integer', 'min:1'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.obligation_id' => ['required_with:allocations', 'integer', 'exists:member_obligations,id'],
            'allocations.*.amount_pesewas' => ['required_with:allocations', 'integer', 'min:1'],
        ]);

        if (! empty($validated['member_id'])) {
            $member = Member::query()->findOrFail((int) $validated['member_id']);
            $canPayForMember = $user->isExecutiveOfficer()
                || $user->memberAccount()->where('member_id', $member->id)->exists();
            abort_unless($canPayForMember, 403);
        } else {
            $member = $user->memberAccount?->member;
            abort_if($member === null, 422, 'No linked member record found.');
        }

        $result = $this->paymentService->initializeOnlinePayment(
            member: $member,
            amountPesewas: (int) $validated['amount_pesewas'],
            requestedAllocations: array_values($validated['allocations'] ?? []),
            actor: $user,
        );

        return response()->json([
            'success' => true,
            'message' => 'Paystack payment initialized.',
            'data' => [
                'id' => $result['payment']->id,
                'reference' => $result['payment']->reference,
                'amount_pesewas' => $result['payment']->amount_pesewas,
                'currency' => $result['payment']->currency,
                'formatted_amount' => $result['payment']->amountMoney()->formattedAmount(),
                'payment_status' => $result['payment']->payment_status,
                'authorization_url' => $result['authorization_url'],
            ],
        ], 201);
    }

    public function showByReference(string $reference): JsonResponse
    {
        $payment = Payment::query()
            ->with(['member', 'allocations.obligation.campaign', 'receipt', 'journalEntry'])
            ->where('reference', $reference)
            ->firstOrFail();

        Gate::authorize('view', $payment);

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved.',
            'data' => $this->serializePayment($payment),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
        ]);

        $target = Payment::query()->where('reference', $validated['reference'])->firstOrFail();
        Gate::authorize('view', $target);

        $verified = $this->paymentService->verifyOnlinePayment($validated['reference'], $actor);

        return response()->json([
            'success' => true,
            'message' => 'Payment successfully verified and posted.',
            'data' => $this->serializePayment($verified),
        ]);
    }

    public function recordCash(Request $request): JsonResponse
    {
        /** @var User $recorder */
        $recorder = $request->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'amount_pesewas' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.obligation_id' => ['required_with:allocations', 'integer', 'exists:member_obligations,id'],
            'allocations.*.amount_pesewas' => ['required_with:allocations', 'integer', 'min:1'],
        ]);

        $member = Member::query()->findOrFail((int) $validated['member_id']);

        $payment = $this->paymentService->recordCashPayment(
            member: $member,
            amountPesewas: (int) $validated['amount_pesewas'],
            recorder: $recorder,
            notes: $validated['notes'] ?? null,
            requestedAllocations: array_values($validated['allocations'] ?? []),
        );

        return response()->json([
            'success' => true,
            'message' => 'Cash payment recorded and awaiting independent officer approval.',
            'data' => $this->serializePayment($payment),
        ], 201);
    }

    public function approve(Request $request, Payment $payment): JsonResponse
    {
        /** @var User $approver */
        $approver = $request->user();
        $notes = $request->string('reason')->trim()->toString() ?: null;

        $approved = $this->paymentService->approveCashPayment($payment, $approver, $notes);

        return response()->json([
            'success' => true,
            'message' => 'Payment approved, allocated, posted, and receipted.',
            'data' => $this->serializePayment($approved),
        ]);
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        /** @var User $approver */
        $approver = $request->user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $rejected = $this->paymentService->rejectCashPayment($payment, $approver, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Cash payment entry rejected.',
            'data' => $this->serializePayment($rejected),
        ]);
    }

    public function receipt(Payment $payment): JsonResponse
    {
        Gate::authorize('view', $payment);

        $receipt = $payment->receipt;
        abort_if($receipt === null, 404, 'No receipt has been issued for this payment yet.');

        return response()->json([
            'success' => true,
            'message' => 'Official receipt retrieved.',
            'data' => [
                'receipt_number' => $receipt->receipt_number,
                'payment_reference' => $payment->reference,
                'amount_pesewas' => $receipt->amount_pesewas,
                'currency' => $receipt->currency,
                'formatted_amount' => $receipt->amountMoney()->formattedAmount(),
                'issued_at' => $receipt->issued_at->toIso8601String(),
                'snapshot' => $receipt->snapshot,
            ],
        ]);
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'amount_pesewas' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
            'refund_channel' => ['required', 'string', Rule::in(['cash', 'bank', 'paystack'])],
        ]);

        $refund = $this->paymentService->refundPayment(
            payment: $payment,
            refundPesewas: (int) $validated['amount_pesewas'],
            reason: $validated['reason'],
            refundChannel: $validated['refund_channel'],
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund processed and posted.',
            'data' => $refund,
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'member_id' => $payment->member_id,
            'amount_pesewas' => $payment->amount_pesewas,
            'currency' => $payment->currency,
            'formatted_amount' => $payment->amountMoney()->formattedAmount(),
            'method' => $payment->method,
            'status' => $payment->payment_status,
            'payment_status' => $payment->payment_status,
            'approval_status' => $payment->approval_status,
            'receipt_number' => $payment->receipt?->receipt_number,
            'journal_entry_id' => $payment->journal_entry_id,
            'posted_at' => $payment->posted_at?->toIso8601String(),
        ];
    }
}
