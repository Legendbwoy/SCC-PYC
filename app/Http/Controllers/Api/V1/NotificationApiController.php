<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationCampaign;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Models\NotificationPreference;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Services\NotificationConsentService;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Modules\Notifications\Services\NotificationTemplateService;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Notification REST API v1 endpoints (Spec Section 13.6).
 */
class NotificationApiController extends Controller
{
    public function __construct(
        private readonly NotificationTemplateService $templateService,
        private readonly NotificationConsentService $consentService,
        private readonly NotificationOrchestrator $orchestrator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved.',
            'data' => $user->notifications()->paginate(20),
        ]);
    }

    public function templates(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsView->value), 403);

        $templates = NotificationTemplate::query()
            ->with('currentVersion')
            ->orderBy('code')
            ->orderBy('channel')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'message' => 'Notification templates retrieved.',
            'data' => $templates,
        ]);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsManageTemplates->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(['membership', 'events', 'finance', 'security', 'general'])],
            'channel' => ['required', 'string', Rule::in(['sms', 'email', 'in_app'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'required_variables' => ['nullable', 'array'],
            'required_variables.*' => ['string', 'regex:/^[a-zA-Z0-9_]+$/'],
            'is_transactional' => ['boolean'],
            'is_active' => ['boolean'],
            'change_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $template = $this->templateService->saveTemplateVersion(
            code: $validated['code'],
            name: $validated['name'],
            category: $validated['category'],
            channel: $validated['channel'],
            body: $validated['body'],
            subject: $validated['subject'] ?? null,
            requiredVariables: array_values($validated['required_variables'] ?? []),
            isTransactional: (bool) ($validated['is_transactional'] ?? true),
            isActive: (bool) ($validated['is_active'] ?? true),
            changeNotes: $validated['change_notes'] ?? null,
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Template version saved.',
            'data' => $template,
        ], 201);
    }

    public function updateTemplate(Request $request, NotificationTemplate $template): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsManageTemplates->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'required_variables' => ['nullable', 'array'],
            'required_variables.*' => ['string', 'regex:/^[a-zA-Z0-9_]+$/'],
            'is_active' => ['boolean'],
            'change_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = $this->templateService->saveTemplateVersion(
            code: $template->code,
            name: $validated['name'] ?? $template->name,
            category: $template->category,
            channel: $template->channel,
            body: $validated['body'],
            subject: $validated['subject'] ?? $template->currentVersion?->subject,
            requiredVariables: array_values($validated['required_variables'] ?? ($template->currentVersion?->required_variables ?? [])),
            isTransactional: $template->is_transactional,
            isActive: (bool) ($validated['is_active'] ?? $template->is_active),
            changeNotes: $validated['change_notes'] ?? 'Updated via API',
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'New template version created.',
            'data' => $updated,
        ]);
    }

    public function sendTest(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless(
            $actor->can(PycPermission::NotificationsManageProviders->value) || $actor->can(PycPermission::NotificationsSend->value),
            403
        );

        $validated = $request->validate([
            'channel' => ['required', 'string', Rule::in(['sms', 'email'])],
            'destination' => ['required', 'string', 'max:190'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $delivery = $this->orchestrator->sendAuthorizedTest(
            channel: $validated['channel'],
            destination: $validated['destination'],
            messageBody: $validated['message'],
            subject: $validated['subject'] ?? null,
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification dispatched.',
            'data' => $delivery,
        ]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsSendBulk->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', Rule::in(['announcements', 'events'])],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in(['sms', 'email'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'sms_body' => ['nullable', 'string', 'max:640'],
            'email_body' => ['nullable', 'string', 'max:10000'],
            'dispatch_now' => ['boolean'],
        ]);

        $campaign = NotificationCampaign::query()->create([
            'reference' => 'PYC-CMP-'.now()->year.'-'.strtoupper(Str::random(6)),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'channels' => array_values($validated['channels']),
            'subject' => $validated['subject'] ?? $validated['title'],
            'sms_body' => $validated['sms_body'] ?? null,
            'email_body' => $validated['email_body'] ?? null,
            'status' => 'draft',
            'created_by' => $actor->id,
        ]);

        if (! empty($validated['dispatch_now'])) {
            $campaign = $this->orchestrator->dispatchBulkCampaign($campaign, $actor);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification campaign created.',
            'data' => $campaign,
        ], 201);
    }

    public function scheduleCampaign(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsSchedule->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        $this->auditLogger->log(
            event: 'notifications.campaign_scheduled',
            category: 'notifications',
            description: "Scheduled campaign [{$campaign->reference}] for {$validated['scheduled_at']}.",
            auditable: $campaign,
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Campaign scheduled.',
            'data' => $campaign->fresh(),
        ]);
    }

    public function cancelCampaign(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsCancel->value), 403);

        /** @var User $actor */
        $actor = $request->user();

        if ($campaign->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed campaigns cannot be cancelled.',
            ], 422);
        }

        $campaign->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
        ]);

        $this->auditLogger->log(
            event: 'notifications.campaign_cancelled',
            category: 'notifications',
            description: "Cancelled campaign [{$campaign->reference}].",
            auditable: $campaign,
            actor: $actor,
        );

        return response()->json([
            'success' => true,
            'message' => 'Campaign cancelled.',
            'data' => $campaign->fresh(),
        ]);
    }

    public function deliveries(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsViewDeliveryLogs->value), 403);

        $query = NotificationDelivery::query()->with(['log', 'templateVersion'])->latest('id');

        if ($request->filled('channel')) {
            $query->where('channel', $request->string('channel')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'success' => true,
            'message' => 'Delivery logs retrieved.',
            'data' => $query->paginate(25),
        ]);
    }

    public function retryDelivery(Request $request, NotificationDelivery $delivery): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsRetryFailed->value), 403);

        /** @var User $actor */
        $actor = $request->user();
        $this->orchestrator->retryFailedDelivery($delivery, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Delivery retry queued.',
            'data' => $delivery->fresh(),
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $pref = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'sms_enabled' => true,
                'email_enabled' => true,
                'in_app_enabled' => true,
                'category_preferences' => ['events' => true, 'announcements' => true],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Preferences retrieved.',
            'data' => $pref,
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'sms_enabled' => ['required', 'boolean'],
            'email_enabled' => ['required', 'boolean'],
            'in_app_enabled' => ['required', 'boolean'],
            'category_preferences' => ['nullable', 'array'],
        ]);

        $pref = $this->consentService->updatePreferences(
            user: $user,
            smsEnabled: (bool) $validated['sms_enabled'],
            emailEnabled: (bool) $validated['email_enabled'],
            inAppEnabled: (bool) $validated['in_app_enabled'],
            categoryPreferences: (array) ($validated['category_preferences'] ?? []),
            actor: $user,
        );

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated.',
            'data' => $pref,
        ]);
    }

    public function providerStatus(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsView->value), 403);

        $smsEndpoint = (string) config('notifications.sms.generic_http.endpoint', '');
        $mailHost = (string) config('mail.mailers.smtp.host', '');

        return response()->json([
            'success' => true,
            'message' => 'Provider status retrieved.',
            'data' => [
                'sms' => [
                    'enabled' => (bool) config('notifications.sms.enabled'),
                    'driver' => config('notifications.sms.driver'),
                    'endpoint_configured' => $smsEndpoint !== '',
                    'sender_id' => config('notifications.sms.generic_http.sender_id'),
                    'delivery_reports' => (bool) config('notifications.sms.generic_http.delivery_reports'),
                ],
                'email' => [
                    'enabled' => (bool) config('notifications.email.enabled'),
                    'mailer' => config('notifications.email.mailer'),
                    'smtp_host_configured' => $mailHost !== '',
                    'from_address' => config('mail.from.address'),
                ],
                'in_app' => [
                    'enabled' => (bool) config('notifications.in_app.enabled'),
                    'driver' => 'database',
                ],
            ],
        ]);
    }
}
