<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\DTOs\DispatchNotificationData;
use App\Modules\Notifications\Enums\DeliveryStatus;
use App\Modules\Notifications\Exceptions\TemplateValidationException;
use App\Modules\Notifications\Jobs\RetryNotificationJob;
use App\Modules\Notifications\Jobs\SendEmailNotificationJob;
use App\Modules\Notifications\Jobs\SendSmsNotificationJob;
use App\Modules\Notifications\Models\NotificationCampaign;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Models\NotificationLog;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Notifications\PortalAlertNotification;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Central Notification Orchestrator (Spec Section 10.2, 10.13, 10.16, 10.18).
 * Resolves templates, validates variables, checks consent/preferences, deduplicates on idempotency key,
 * creates independent channel delivery records, and dispatches priority queue jobs safely.
 */
class NotificationOrchestrator
{
    private const HIGH_PRIORITY_TEMPLATES = [
        'account_invitation',
        'payment_successful',
        'payment_receipt',
        'cash_payment_approved',
        'refund_processed',
    ];

    public function __construct(
        private readonly NotificationTemplateService $templateService,
        private readonly NotificationConsentService $consentService,
        private readonly RecipientResolver $recipientResolver,
        private readonly GsmSegmentEstimator $gsmEstimator,
        private readonly DeliveryTrackingService $trackingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function dispatch(DispatchNotificationData $data): ?NotificationLog
    {
        if (! (bool) config('notifications.enabled', true)) {
            return null;
        }

        try {
            return $this->dispatchOrThrow($data);
        } catch (TemplateValidationException $exception) {
            if (! $data->failSafely) {
                throw $exception;
            }

            Log::error('Notification template validation failed safely without rolling back caller', [
                'template_code' => $data->templateCode,
                'idempotency_key' => $data->idempotencyKey,
                'error' => $exception->getMessage(),
            ]);

            return null;
        } catch (\Throwable $exception) {
            if (! $data->failSafely) {
                throw $exception;
            }

            Log::error('Notification orchestrator failed safely without rolling back caller', [
                'template_code' => $data->templateCode,
                'idempotency_key' => $data->idempotencyKey,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Authorized test-message sender for verifying SMS gateway & SMTP email configuration (Spec 16.2 & 17.3).
     */
    public function sendAuthorizedTest(
        string $channel,
        string $destination,
        string $messageBody,
        ?string $subject,
        User $actor,
    ): NotificationDelivery {
        $normalizedAddress = $channel === 'sms'
            ? $this->recipientResolver->normalizePhone($destination)
            : $this->recipientResolver->normalizeEmail($destination);

        if ($normalizedAddress === null) {
            throw new \InvalidArgumentException("Invalid recipient destination [{$destination}] for [{$channel}] test.");
        }

        $log = NotificationLog::query()->create([
            'idempotency_key' => 'test:'.$channel.':'.Str::uuid()->toString(),
            'user_id' => $actor->id,
            'template_code' => null,
            'category' => 'security',
            'is_transactional' => true,
            'event_name' => 'notifications.test_send',
            'status' => DeliveryStatus::Queued->value,
            'initiated_by' => $actor->id,
        ]);

        $gsm = $channel === 'sms' ? $this->gsmEstimator->estimate($messageBody) : null;

        $delivery = NotificationDelivery::query()->create([
            'notification_log_id' => $log->id,
            'channel' => $channel,
            'provider' => $this->providerNameForChannel($channel),
            'recipient_address' => $normalizedAddress,
            'recipient_name' => $actor->name,
            'rendered_subject' => $channel === 'email' ? ($subject ?: 'SCC-PYC SMTP Configuration Test') : null,
            'rendered_body' => $channel === 'email' ? nl2br(e($messageBody)) : $messageBody,
            'gsm_segments' => $gsm['segments'] ?? null,
            'status' => DeliveryStatus::Queued->value,
        ]);

        $this->auditLogger->log(
            event: 'notifications.test_dispatched',
            category: 'notifications',
            description: "Dispatched authorized [{$channel}] test message to [{$normalizedAddress}].",
            auditable: $delivery,
            actor: $actor,
        );

        $queue = (string) config('notifications.queues.high_priority', 'high_priority');

        if ($channel === 'sms') {
            SendSmsNotificationJob::dispatch($delivery->id)->onQueue($queue);
        } else {
            SendEmailNotificationJob::dispatch($delivery->id)->onQueue($queue);
        }

        return $delivery->fresh() ?? $delivery;
    }

    /**
     * Dispatches an authorized bulk announcement campaign to eligible users respecting optional consent (Spec 10.15 & 10.17).
     */
    public function dispatchBulkCampaign(NotificationCampaign $campaign, User $actor): NotificationCampaign
    {
        if (in_array($campaign->status, ['completed', 'cancelled'], true)) {
            return $campaign;
        }

        $campaign->update([
            'status' => 'processing',
            'dispatched_at' => now(),
        ]);

        $users = User::query()->where('status', 'active')->get();
        $bulkQueue = (string) config('notifications.queues.bulk', 'bulk_notifications');
        $dispatchedCount = 0;

        foreach ($users as $targetUser) {
            $recipient = $this->recipientResolver->fromUser($targetUser);
            $idempotencyKey = "campaign:{$campaign->id}:user:{$targetUser->id}";

            if (NotificationLog::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                continue;
            }

            $log = NotificationLog::query()->create([
                'idempotency_key' => $idempotencyKey,
                'user_id' => $targetUser->id,
                'campaign_id' => $campaign->id,
                'category' => $campaign->category,
                'is_transactional' => false,
                'event_name' => 'notifications.bulk_campaign',
                'status' => DeliveryStatus::Queued->value,
                'initiated_by' => $actor->id,
            ]);

            $createdForRecipient = 0;

            foreach ((array) $campaign->channels as $channel) {
                if (! $this->consentService->canSend($recipient, (string) $channel, $campaign->category, false)) {
                    continue;
                }

                if ($channel === 'sms' && $recipient->phone !== null && ! empty($campaign->sms_body)) {
                    $gsm = $this->gsmEstimator->estimate($campaign->sms_body);
                    $delivery = NotificationDelivery::query()->create([
                        'notification_log_id' => $log->id,
                        'channel' => 'sms',
                        'provider' => $this->providerNameForChannel('sms'),
                        'recipient_address' => $recipient->phone,
                        'recipient_name' => $recipient->name,
                        'rendered_body' => $campaign->sms_body,
                        'gsm_segments' => $gsm['segments'],
                        'status' => DeliveryStatus::Queued->value,
                    ]);
                    SendSmsNotificationJob::dispatch($delivery->id)->onQueue($bulkQueue);
                    $createdForRecipient++;
                } elseif ($channel === 'email' && $recipient->email !== null && ! empty($campaign->email_body)) {
                    $delivery = NotificationDelivery::query()->create([
                        'notification_log_id' => $log->id,
                        'channel' => 'email',
                        'provider' => $this->providerNameForChannel('email'),
                        'recipient_address' => $recipient->email,
                        'recipient_name' => $recipient->name,
                        'rendered_subject' => $campaign->subject ?: $campaign->title,
                        'rendered_body' => nl2br(e($campaign->email_body)),
                        'status' => DeliveryStatus::Queued->value,
                    ]);
                    SendEmailNotificationJob::dispatch($delivery->id)->onQueue($bulkQueue);
                    $createdForRecipient++;
                }
            }

            if ($createdForRecipient > 0) {
                $dispatchedCount++;
            } else {
                $log->update(['status' => 'suppressed']);
            }
        }

        $campaign->update([
            'status' => 'completed',
            'total_recipients' => $dispatchedCount,
        ]);

        $this->auditLogger->log(
            event: 'notifications.bulk_campaign_dispatched',
            category: 'notifications',
            description: "Dispatched bulk campaign [{$campaign->reference}] ({$campaign->title}) to {$dispatchedCount} consenting recipients.",
            auditable: $campaign,
            metadata: [
                'channels' => $campaign->channels,
                'recipients_count' => $dispatchedCount,
            ],
            actor: $actor,
        );

        return $campaign->fresh() ?? $campaign;
    }

    public function retryFailedDelivery(NotificationDelivery $delivery, User $actor): void
    {
        $delivery->update([
            'status' => DeliveryStatus::Retrying->value,
            'attempts' => 0,
            'next_retry_at' => null,
        ]);

        $this->auditLogger->log(
            event: 'notifications.manual_retry_queued',
            category: 'notifications',
            description: "Authorized manual retry queued for delivery #{$delivery->id} ({$delivery->channel}).",
            auditable: $delivery,
            actor: $actor,
        );

        RetryNotificationJob::dispatch($delivery->id)
            ->onQueue((string) config('notifications.queues.high_priority', 'high_priority'));
    }

    private function dispatchOrThrow(DispatchNotificationData $data): NotificationLog
    {
        $existing = NotificationLog::query()
            ->with('deliveries')
            ->where('idempotency_key', $data->idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $templates = NotificationTemplate::query()
            ->with('currentVersion')
            ->where('code', $data->templateCode)
            ->whereIn('channel', $data->channels)
            ->where('is_active', true)
            ->get()
            ->keyBy('channel');

        if ($templates->isEmpty()) {
            throw new TemplateValidationException(
                "No active notification templates found for code [{$data->templateCode}]."
            );
        }

        // Pre-validate and render every active requested channel BEFORE creating records so missing variables prevent sending cleanly (Spec 17.3)
        $preparedDeliveries = [];
        $firstTemplate = $templates->first();
        $category = $firstTemplate->category;
        $isTransactional = $firstTemplate->is_transactional;

        foreach ($data->channels as $channel) {
            if (! $this->isChannelGloballyEnabled($channel)) {
                continue;
            }

            /** @var NotificationTemplate|null $template */
            $template = $templates->get($channel);
            if ($template === null || $template->currentVersion === null) {
                continue;
            }

            // Always validate variables even if recipient later turns out suppressed!
            $rendered = $this->templateService->renderVersion(
                version: $template->currentVersion,
                channel: $channel,
                variables: $data->variables,
            );

            if (! $this->consentService->canSend($data->recipient, $channel, $category, $isTransactional)) {
                continue;
            }

            $address = match ($channel) {
                'sms' => $this->recipientResolver->normalizePhone($data->recipient->phone),
                'email' => $this->recipientResolver->normalizeEmail($data->recipient->email),
                'in_app' => $data->recipient->userId !== null ? 'user:'.$data->recipient->userId : null,
                default => null,
            };

            if ($address === null) {
                continue;
            }

            $preparedDeliveries[] = [
                'channel' => $channel,
                'version_id' => $template->currentVersion->id,
                'address' => $address,
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'gsm_segments' => $rendered['gsm_estimate']['segments'] ?? null,
            ];
        }

        [$log, $createdDeliveries] = DB::transaction(function () use ($data, $category, $isTransactional, $preparedDeliveries): array {
            try {
                $log = NotificationLog::query()->create([
                    'idempotency_key' => $data->idempotencyKey,
                    'user_id' => $data->recipient->userId,
                    'member_id' => $data->recipient->memberId,
                    'campaign_id' => $data->campaignId,
                    'template_code' => $data->templateCode,
                    'category' => $category,
                    'is_transactional' => $isTransactional,
                    'event_name' => $data->eventName,
                    'related_type' => $data->relatedType,
                    'related_id' => $data->relatedId,
                    'variables' => $data->variables,
                    'status' => $preparedDeliveries === [] ? 'suppressed' : DeliveryStatus::Queued->value,
                    'initiated_by' => $data->initiatedByUserId,
                ]);
            } catch (QueryException) {
                $existingLog = NotificationLog::query()
                    ->with('deliveries')
                    ->where('idempotency_key', $data->idempotencyKey)
                    ->firstOrFail();

                return [$existingLog, []];
            }

            $deliveries = [];
            foreach ($preparedDeliveries as $item) {
                $deliveries[] = NotificationDelivery::query()->create([
                    'notification_log_id' => $log->id,
                    'notification_template_version_id' => $item['version_id'],
                    'channel' => $item['channel'],
                    'provider' => $this->providerNameForChannel($item['channel']),
                    'recipient_address' => $item['address'],
                    'recipient_name' => $data->recipient->name,
                    'rendered_subject' => $item['subject'],
                    'rendered_body' => $item['body'],
                    'gsm_segments' => $item['gsm_segments'],
                    'status' => DeliveryStatus::Queued->value,
                ]);
            }

            return [$log, $deliveries];
        });

        $queueName = $this->resolveQueueName($data);

        /** @var NotificationDelivery $delivery */
        foreach ($createdDeliveries as $delivery) {
            if ($delivery->channel === 'sms') {
                SendSmsNotificationJob::dispatch($delivery->id)->onQueue($queueName);
            } elseif ($delivery->channel === 'email') {
                SendEmailNotificationJob::dispatch($delivery->id)->onQueue($queueName);
            } elseif ($delivery->channel === 'in_app' && $data->recipient->userId !== null) {
                $user = User::query()->find($data->recipient->userId);
                if ($user !== null) {
                    $user->notify(new PortalAlertNotification(
                        title: $delivery->rendered_subject ?? Str::headline($data->templateCode),
                        message: $delivery->rendered_body,
                        category: $category,
                        templateCode: $data->templateCode,
                    ));
                    $this->trackingService->markSubmitted($delivery, DeliveryStatus::Delivered->value);
                }
            }
        }

        return $log->fresh(['deliveries']) ?? $log;
    }

    private function resolveQueueName(DispatchNotificationData $data): string
    {
        if ($data->priorityQueue !== null) {
            return $data->priorityQueue;
        }

        if ($data->campaignId !== null) {
            return (string) config('notifications.queues.bulk', 'bulk_notifications');
        }

        if (in_array($data->templateCode, self::HIGH_PRIORITY_TEMPLATES, true)) {
            return (string) config('notifications.queues.high_priority', 'high_priority');
        }

        return (string) config('notifications.queues.default', 'notifications');
    }

    private function isChannelGloballyEnabled(string $channel): bool
    {
        return match ($channel) {
            'sms' => (bool) config('notifications.sms.enabled', true),
            'email' => (bool) config('notifications.email.enabled', true),
            'in_app' => (bool) config('notifications.in_app.enabled', true),
            default => false,
        };
    }

    private function providerNameForChannel(string $channel): string
    {
        return match ($channel) {
            'sms' => (string) config('notifications.sms.driver', 'generic_http'),
            'email' => (string) config('notifications.email.mailer', 'smtp'),
            'in_app' => 'database',
            default => 'unknown',
        };
    }
}
