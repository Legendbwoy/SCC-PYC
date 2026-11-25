<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Modules\Accounting\Contracts\AccountingPostingServiceInterface;
use App\Modules\Accounting\DTOs\JournalLineData;
use App\Modules\Accounting\Models\FinancialPeriod;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\FinancialReportingService;
use App\Modules\Contributions\Services\MonthlyDuesService;
use App\Modules\Expenses\Models\ExpenseCategory;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Receipt;
use App\Modules\Payments\Services\PaymentService;
use App\Support\Security\PycRole;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Verifies all Sprint 5 & 6 Payments, Receipts, Double-Entry Accounting & Expense acceptance criteria (Spec Section 7, 8 & 17.2).
 */
class PaymentsAccountingAndExpensesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        config()->set('pyc.payments.paystack.secret_key', 'sk_test_pyc_sandbox_secret');
        config()->set('pyc.payments.paystack.webhook_secret', 'whsec_pyc_sandbox_secret');
        config()->set('pyc.payments.paystack.base_url', 'https://api.paystack.co');
        config()->set('notifications.sms.generic_http.endpoint', 'https://sms-sandbox.example/api/send');
        config()->set('notifications.sms.generic_http.api_token', 'sandbox-token');
        config()->set('notifications.email.mailer', 'array');
    }

    public function test_cash_payments_enforce_maker_checker_allocate_post_balanced_journal_and_issue_pdf_receipt(): void
    {
        Http::fake([
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-CASH', 'status' => 'accepted'], 200),
        ]);

        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0001',
            'first_name' => 'Kwesi',
            'last_name' => 'Arthur',
            'phone' => '+233241234567',
            'email' => 'kwesi@example.org',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-01-01',
        ]);

        app(MonthlyDuesService::class)->generate(now()->format('Y-m'));

        $finSec = User::factory()->withConfirmedTwoFactor()->create();
        $finSec->assignRole(PycRole::FinancialSecretary->value);

        $president = User::factory()->withConfirmedTwoFactor()->create();
        $president->assignRole(PycRole::President->value);

        // 1) Financial Secretary records GHS 10.00 (1,000p) cash payment -> awaits independent approval
        $recordResp = $this->actingAs($finSec)
            ->postJson('/api/v1/payments/cash', [
                'member_id' => $member->id,
                'amount_pesewas' => 1000,
                'notes' => 'Sunday Mass dues table',
            ])
            ->assertCreated();

        $paymentId = (int) $recordResp->json('data.id');
        $payment = Payment::query()->findOrFail($paymentId);

        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame('awaiting_approval', $payment->approval_status);
        $this->assertNull($payment->journal_entry_id);
        $this->assertSame(0, Receipt::query()->count());

        // 2) Recorder attempting to approve their own cash entry is strictly blocked (403)
        $this->actingAs($finSec)
            ->postJson("/api/v1/payments/{$payment->id}/approve")
            ->assertForbidden();

        // 3) Independent approval by PYC President allocates, posts balanced journal, and issues receipt once
        $this->actingAs($president)
            ->postJson("/api/v1/payments/{$payment->id}/approve", [
                'reason' => 'Verified physical cash receipt book',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'posted');

        $payment->refresh();
        $this->assertSame('posted', $payment->payment_status);
        $this->assertSame('approved', $payment->approval_status);
        $this->assertNotNull($payment->journal_entry_id);
        $this->assertNotNull($payment->receipt);
        $this->assertMatchesRegularExpression('/^PYC-RCT-\d{4}-00001$/', (string) $payment->receipt?->receipt_number);

        // Official PDF receipt downloads cleanly
        $this->actingAs($president)
            ->get(route('payments.receipt.pdf', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_paystack_verification_validates_amount_currency_and_webhook_replay_is_idempotent(): void
    {
        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0002',
            'first_name' => 'Aba',
            'last_name' => 'Quansah',
            'phone' => '+233245556666',
            'email' => 'aba@example.org',
            'status' => MemberStatus::Active->value,
            'joined_at' => '2026-01-01',
        ]);

        app(MonthlyDuesService::class)->generate(now()->format('Y-m'));

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test_abc',
                    'access_code' => 'acc_test_abc',
                    'reference' => '',
                ],
            ], 200),
            'https://api.paystack.co/transaction/verify/*' => Http::sequence()
                // First attempt: simulated tampered amount (500p instead of 1000p) -> must fail!
                ->push([
                    'status' => true,
                    'data' => [
                        'id' => 99001,
                        'status' => 'success',
                        'amount' => 500,
                        'currency' => 'GHS',
                    ],
                ], 200)
                // Second payment: genuine 1000p GHS verification -> succeeds!
                ->push([
                    'status' => true,
                    'data' => [
                        'id' => 99002,
                        'status' => 'success',
                        'amount' => 1000,
                        'currency' => 'GHS',
                    ],
                ], 200)
                ->push([
                    'status' => true,
                    'data' => [
                        'id' => 99002,
                        'status' => 'success',
                        'amount' => 1000,
                        'currency' => 'GHS',
                    ],
                ], 200),
            'https://sms-sandbox.example/*' => Http::response(['message_id' => 'SMS-PS', 'status' => 'accepted'], 200),
        ]);

        $paymentService = app(PaymentService::class);

        // 1) Mismatched amount is rejected and audited
        $badInit = $paymentService->initializeOnlinePayment($member, 1000);
        try {
            $paymentService->verifyOnlinePayment($badInit['payment']->reference);
            $this->fail('Expected InvalidArgumentException on mismatched Paystack amount.');
        } catch (InvalidArgumentException) {
            $this->assertSame('failed', $badInit['payment']->fresh()?->payment_status);
            $this->assertSame(0, JournalEntry::query()->count());
        }

        // 2) Genuine signed Paystack webhook verifies server-side, posts once, and ignores replays
        $goodInit = $paymentService->initializeOnlinePayment($member, 1000);
        $ref = $goodInit['payment']->reference;

        $webhookPayload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'id' => 8800123,
                'reference' => $ref,
                'amount' => 1000,
                'currency' => 'GHS',
                'status' => 'success',
            ],
        ], JSON_THROW_ON_ERROR);

        // Invalid signature rejected with 401
        $this->call('POST', '/api/v1/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => 'forged-signature',
        ], $webhookPayload)->assertStatus(401);

        // Valid HMAC-SHA512 signature processes and posts once
        $validSig = hash_hmac('sha512', $webhookPayload, 'whsec_pyc_sandbox_secret');

        $this->call('POST', '/api/v1/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $validSig,
        ], $webhookPayload)->assertOk();

        // Replay of exact same webhook event + duplicate direct verify call does NOT double-post!
        $this->call('POST', '/api/v1/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $validSig,
        ], $webhookPayload)->assertOk();

        $paymentService->verifyOnlinePayment($ref);

        $this->assertSame(1, JournalEntry::query()->count());
        $this->assertSame(1, Receipt::query()->count());
    }

    public function test_settlement_expenses_immutability_reversals_and_closed_period_controls(): void
    {
        $treasurer = User::factory()->withConfirmedTwoFactor()->create();
        $treasurer->assignRole(PycRole::Treasurer->value);

        $president = User::factory()->withConfirmedTwoFactor()->create();
        $president->assignRole(PycRole::President->value);

        $postingService = app(AccountingPostingServiceInterface::class);

        // 1) Unbalanced journal is rejected
        try {
            $postingService->post(
                sourceType: 'test',
                sourceId: 1,
                entryDate: now(),
                narration: 'Unbalanced attempt',
                lines: [
                    new JournalLineData('1010', debitPesewas: 1000),
                    new JournalLineData('4000', creditPesewas: 900),
                ],
                actor: $treasurer,
            );
            $this->fail('Expected unbalanced journal to throw InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, JournalEntry::query()->count());
        }

        // 2) Paystack settlement (GHS 10 gross, GHS 0.50 fee -> Bank 950p + Charges 50p / Clearing 1000p)
        app(PaymentService::class)->recordSettlement(
            providerSettlementReference: 'PAYSTACK-SET-2026-01',
            grossPesewas: 1000,
            feePesewas: 50,
            settlementDate: now()->toDateString(),
            actor: $treasurer,
        );

        // 3) Expense dual authorization (maker-checker) + disbursement posting
        $transportCat = ExpenseCategory::query()->where('code', 'transport')->firstOrFail();

        $expResp = $this->actingAs($treasurer)
            ->postJson('/api/v1/expenses', [
                'expense_category_id' => $transportCat->id,
                'payee_name' => 'Parish Bus Driver',
                'description' => 'Deanery rally bus fuel',
                'amount_pesewas' => 2000,
                'expense_date' => now()->toDateString(),
                'payment_channel' => 'cash',
                'submit_immediately' => true,
            ])
            ->assertCreated();

        $expenseId = (int) $expResp->json('data.id');

        // Submitter (Treasurer) cannot approve own expense
        $this->actingAs($treasurer)
            ->postJson("/api/v1/expenses/{$expenseId}/approve")
            ->assertForbidden();

        // Independent approval by President + disbursement by Treasurer
        $this->actingAs($president)
            ->postJson("/api/v1/expenses/{$expenseId}/approve")
            ->assertOk();

        $this->actingAs($treasurer)
            ->postJson("/api/v1/expenses/{$expenseId}/pay")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        // Verify Trial Balance is strictly balanced
        $tb = app(FinancialReportingService::class)->trialBalance(now());
        $this->assertTrue($tb['is_balanced']);

        // 4) Posted journals cannot be edited or deleted; reversals create a traceable counter-entry
        $firstJournal = JournalEntry::query()->firstOrFail();

        try {
            $firstJournal->update(['narration' => 'Edited narration']);
            $this->fail('Expected ImmutableRecordException when editing JournalEntry.');
        } catch (ImmutableRecordException) {
            // Expected
        }

        try {
            $firstJournal->delete();
            $this->fail('Expected ImmutableRecordException when deleting JournalEntry.');
        } catch (ImmutableRecordException) {
            // Expected
        }

        $this->actingAs($president)
            ->postJson("/api/v1/accounting/journals/{$firstJournal->id}/reverse", [
                'reason' => 'Correcting settlement date entry',
            ])
            ->assertCreated();

        $this->assertNotNull($firstJournal->fresh()?->reversingEntry);
        $this->assertTrue(app(FinancialReportingService::class)->trialBalance(now())['is_balanced']);

        // 5) Closed financial periods block ordinary posting
        $janPeriod = FinancialPeriod::query()->where('period_key', now()->year.'-01')->firstOrFail();
        $this->actingAs($president)
            ->postJson("/api/v1/accounting/periods/{$janPeriod->id}/close", [
                'notes' => 'January books reconciled and closed',
            ])
            ->assertOk();

        $this->expectException(InvalidArgumentException::class);
        $postingService->post(
            sourceType: 'late_entry',
            sourceId: 99,
            entryDate: Carbon::parse(now()->year.'-01-15'),
            narration: 'Blocked entry into closed January period',
            lines: [
                new JournalLineData('1010', debitPesewas: 500),
                new JournalLineData('4200', creditPesewas: 500),
            ],
            actor: $treasurer,
        );
    }
}
