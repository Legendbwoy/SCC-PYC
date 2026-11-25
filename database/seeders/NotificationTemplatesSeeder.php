<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Services\NotificationTemplateService;
use Illuminate\Database\Seeder;

/**
 * Seeds all 18 official notification template codes from Spec Section 10.8 & 10.9 across SMS and Email.
 */
class NotificationTemplatesSeeder extends Seeder
{
    public function run(NotificationTemplateService $templateService): void
    {
        $catalogue = [
            // Membership Onboarding (Transactional)
            [
                'code' => 'membership_received',
                'name' => 'Membership Registration Acknowledgement',
                'category' => 'membership',
                'transactional' => true,
                'vars' => ['applicant_name'],
                'sms' => 'Hello {{applicant_name}}, St. Cecilia PYC has received your membership registration. Our General Secretary will review it shortly.',
                'subject' => 'SCC-PYC Membership Application Received',
                'email' => '<p>Dear {{applicant_name}},</p><p>Thank you for submitting your membership application to St. Cecilia Catholic Church Parish Youth Council. Your application is currently pending review by the General Secretary.</p>',
            ],
            [
                'code' => 'membership_approved',
                'name' => 'Membership Application Approved',
                'category' => 'membership',
                'transactional' => true,
                'vars' => ['member_name', 'member_number'],
                'sms' => 'Congratulations {{member_name}}! Your SCC-PYC membership is approved. Member No: {{member_number}}.',
                'subject' => 'SCC-PYC Membership Approved – {{member_number}}',
                'email' => '<p>Dear {{member_name}},</p><p>Your membership with St. Cecilia Parish Youth Council has been approved. Your official Member Number is <strong>{{member_number}}</strong>.</p>',
            ],
            [
                'code' => 'membership_rejected',
                'name' => 'Membership Application Outcome',
                'category' => 'membership',
                'transactional' => true,
                'vars' => ['applicant_name', 'reason'],
                'sms' => 'Hello {{applicant_name}}, we could not approve your SCC-PYC membership application ({{reason}}). Please contact the General Secretary.',
                'subject' => 'Update on Your SCC-PYC Membership Application',
                'email' => '<p>Dear {{applicant_name}},</p><p>Thank you for your interest in St. Cecilia Parish Youth Council. At this time your application could not be approved: {{reason}}.</p>',
            ],
            [
                'code' => 'member_welcome',
                'name' => 'Member Portal Welcome Notice',
                'category' => 'membership',
                'transactional' => true,
                'vars' => ['member_name', 'member_number'],
                'sms' => 'Welcome to St. Cecilia PYC, {{member_name}} ({{member_number}})! Your member portal account is now active.',
                'subject' => 'Welcome to the St. Cecilia PYC Member Portal',
                'email' => '<p>Dear {{member_name}},</p><p>Welcome to the St. Cecilia Parish Youth Council portal! Your account ({{member_number}}) is now active.</p>',
            ],
            [
                'code' => 'account_invitation',
                'name' => 'Secure Portal Account Invitation',
                'category' => 'security',
                'transactional' => true,
                'vars' => ['member_name', 'invitation_url', 'expires_in'],
                'sms' => 'Hello {{member_name}}, activate your SCC-PYC portal account here: {{invitation_url}} (valid for {{expires_in}}).',
                'subject' => 'Activate Your St. Cecilia PYC Portal Account',
                'email' => '<p>Dear {{member_name}},</p><p>Please activate your St. Cecilia PYC Member Portal account using this single-use link (valid for {{expires_in}}):</p><p><a href="{{invitation_url}}">{{invitation_url}}</a></p>',
            ],

            // Events (Announcements/Reminders respect optional consent; Registration/Cancellation transactional)
            [
                'code' => 'event_announcement',
                'name' => 'Parish Youth Event Announcement',
                'category' => 'events',
                'transactional' => false,
                'vars' => ['event_title', 'event_date', 'venue'],
                'sms' => 'SCC-PYC Event: {{event_title}} on {{event_date}} at {{venue}}. Visit the portal to register!',
                'subject' => 'Upcoming PYC Event: {{event_title}}',
                'email' => '<p>Join us for <strong>{{event_title}}</strong> on {{event_date}} at {{venue}}. Sign in to the PYC Member Portal to register.</p>',
            ],
            [
                'code' => 'event_registration',
                'name' => 'Event Registration Confirmation',
                'category' => 'events',
                'transactional' => true,
                'vars' => ['member_name', 'event_title', 'event_date', 'venue'],
                'sms' => 'Confirmed: {{member_name}}, you are registered for {{event_title}} on {{event_date}} at {{venue}}.',
                'subject' => 'Registration Confirmed – {{event_title}}',
                'email' => '<p>Dear {{member_name}},</p><p>Your registration for <strong>{{event_title}}</strong> on {{event_date}} at {{venue}} is confirmed.</p>',
            ],
            [
                'code' => 'event_reminder',
                'name' => 'Upcoming Event Reminder',
                'category' => 'events',
                'transactional' => false,
                'vars' => ['event_title', 'event_date', 'venue'],
                'sms' => 'Reminder: SCC-PYC {{event_title}} takes place on {{event_date}} at {{venue}}. See you there!',
                'subject' => 'Reminder: {{event_title}} on {{event_date}}',
                'email' => '<p>This is a friendly reminder for <strong>{{event_title}}</strong> taking place on {{event_date}} at {{venue}}.</p>',
            ],
            [
                'code' => 'event_cancelled',
                'name' => 'Event Cancellation Notice',
                'category' => 'events',
                'transactional' => true,
                'vars' => ['event_title', 'event_date', 'reason'],
                'sms' => 'Notice: SCC-PYC event {{event_title}} ({{event_date}}) has been cancelled: {{reason}}.',
                'subject' => 'Event Cancelled – {{event_title}}',
                'email' => '<p>Please note that <strong>{{event_title}}</strong> scheduled for {{event_date}} has been cancelled. Reason: {{reason}}.</p>',
            ],

            // Payments & Receipts (Spec Section 10.9 & 10.18)
            [
                'code' => 'payment_successful',
                'name' => 'Verified & Posted Payment Notice',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['amount', 'contribution', 'payment_reference'],
                'sms' => 'SCC-PYC: Payment of GHS {{amount}} for {{contribution}} (Ref: {{payment_reference}}) has been verified and posted.',
                'subject' => 'Payment Verified – {{payment_reference}}',
                'email' => '<p>Your payment of <strong>GHS {{amount}}</strong> for {{contribution}} (Reference: {{payment_reference}}) has been verified and posted.</p>',
            ],
            [
                'code' => 'payment_receipt',
                'name' => 'Official Payment Receipt',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['member_name', 'amount', 'contribution', 'receipt_number', 'payment_date', 'payment_method', 'payment_reference'],
                'sms' => 'Payment confirmed! GHS {{amount}} received for {{contribution}}. Receipt: {{receipt_number}}. Date: {{payment_date}}. Thank you for supporting PYC.',
                'subject' => 'Official PYC Payment Receipt – {{receipt_number}}',
                'email' => '<p>Dear {{member_name}},</p><p>Thank you for supporting St. Cecilia Parish Youth Council. Here are your official receipt details:</p><ul><li>Receipt No: <strong>{{receipt_number}}</strong></li><li>Amount: <strong>GHS {{amount}}</strong></li><li>Contribution: {{contribution}}</li><li>Method: {{payment_method}}</li><li>Payment Reference: {{payment_reference}}</li><li>Date: {{payment_date}}</li></ul>',
            ],
            [
                'code' => 'payment_failed',
                'name' => 'Payment Failure Notice',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['member_name', 'amount', 'payment_reference'],
                'sms' => 'Hello {{member_name}}, your online payment attempt of GHS {{amount}} (Ref: {{payment_reference}}) was not completed.',
                'subject' => 'Payment Not Completed – {{payment_reference}}',
                'email' => '<p>Dear {{member_name}},</p><p>Your online payment attempt of GHS {{amount}} (Reference: {{payment_reference}}) could not be completed. No funds were posted to your account.</p>',
            ],
            [
                'code' => 'cash_payment_pending',
                'name' => 'Cash Payment Recorded (Pending Approval)',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['member_name', 'amount', 'payment_reference'],
                'sms' => 'Hello {{member_name}}, cash payment of GHS {{amount}} (Ref: {{payment_reference}}) was recorded and awaits independent officer approval.',
                'subject' => 'Cash Payment Recorded – Pending Verification ({{payment_reference}})',
                'email' => '<p>Dear {{member_name}},</p><p>A manual cash payment of GHS {{amount}} (Reference: {{payment_reference}}) has been recorded and is awaiting independent officer approval before your official receipt is issued.</p>',
            ],
            [
                'code' => 'cash_payment_approved',
                'name' => 'Cash Payment Approved & Posted',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['amount', 'contribution', 'receipt_number', 'payment_date'],
                'sms' => 'Cash payment approved! GHS {{amount}} for {{contribution}}. Official Receipt: {{receipt_number}} ({{payment_date}}).',
                'subject' => 'Cash Payment Approved – Receipt {{receipt_number}}',
                'email' => '<p>Your cash payment of <strong>GHS {{amount}}</strong> for {{contribution}} on {{payment_date}} has been independently approved and posted. Official Receipt: <strong>{{receipt_number}}</strong>.</p>',
            ],
            [
                'code' => 'cash_payment_rejected',
                'name' => 'Cash Payment Rejection Notice',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['member_name', 'amount', 'payment_reference', 'reason'],
                'sms' => 'Hello {{member_name}}, recorded cash entry {{payment_reference}} (GHS {{amount}}) was not approved: {{reason}}.',
                'subject' => 'Cash Payment Entry Rejected – {{payment_reference}}',
                'email' => '<p>Dear {{member_name}},</p><p>The recorded cash payment entry {{payment_reference}} (GHS {{amount}}) was rejected during verification. Reason: {{reason}}.</p>',
            ],
            [
                'code' => 'refund_processed',
                'name' => 'Refund Processed Notice',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['member_name', 'amount', 'refund_reference'],
                'sms' => 'Hello {{member_name}}, your refund of GHS {{amount}} (Ref: {{refund_reference}}) has been processed.',
                'subject' => 'Refund Processed – {{refund_reference}}',
                'email' => '<p>Dear {{member_name}},</p><p>Your authorized refund of <strong>GHS {{amount}}</strong> (Reference: {{refund_reference}}) has been processed.</p>',
            ],
            [
                'code' => 'monthly_dues_reminder',
                'name' => 'Monthly Youth Dues Reminder',
                'category' => 'finance',
                'transactional' => false,
                'vars' => ['member_name', 'period', 'amount'],
                'sms' => 'Hello {{member_name}}, friendly reminder that your SCC-PYC monthly dues of GHS {{amount}} for {{period}} are due.',
                'subject' => 'SCC-PYC Monthly Dues Reminder – {{period}}',
                'email' => '<p>Dear {{member_name}},</p><p>This is a friendly reminder for your {{period}} Parish Youth Council monthly dues of <strong>GHS {{amount}}</strong>.</p>',
            ],
            [
                'code' => 'outstanding_dues_notice',
                'name' => 'Outstanding Dues Balance Notice',
                'category' => 'finance',
                'transactional' => true,
                'vars' => ['member_name', 'outstanding_amount'],
                'sms' => 'Hello {{member_name}}, your current SCC-PYC outstanding dues balance is GHS {{outstanding_amount}}. Sign in to the portal for details.',
                'subject' => 'SCC-PYC Outstanding Contributions Summary',
                'email' => '<p>Dear {{member_name}},</p><p>Your current outstanding dues balance with St. Cecilia Parish Youth Council is <strong>GHS {{outstanding_amount}}</strong>. Sign in to your Member Portal to review your statement or make a partial/full payment.</p>',
            ],
        ];

        foreach ($catalogue as $item) {
            // Seed SMS template version 1 if not already present
            if (! NotificationTemplate::query()->where('code', $item['code'])->where('channel', 'sms')->exists()) {
                $smsVars = $this->extractUsedVars($item['sms']);
                $templateService->saveTemplateVersion(
                    code: $item['code'],
                    name: $item['name'],
                    category: $item['category'],
                    channel: 'sms',
                    body: $item['sms'],
                    subject: null,
                    requiredVariables: $smsVars,
                    isTransactional: $item['transactional'],
                );
            }

            // Seed Email template version 1 if not already present
            if (! NotificationTemplate::query()->where('code', $item['code'])->where('channel', 'email')->exists()) {
                $emailVars = $this->extractUsedVars($item['subject'].' '.$item['email']);
                $templateService->saveTemplateVersion(
                    code: $item['code'],
                    name: $item['name'],
                    category: $item['category'],
                    channel: 'email',
                    body: $item['email'],
                    subject: $item['subject'],
                    requiredVariables: $emailVars,
                    isTransactional: $item['transactional'],
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function extractUsedVars(string $text): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
