<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'notification_log_id',
        'notification_template_version_id',
        'channel',
        'provider',
        'recipient_address',
        'recipient_name',
        'rendered_subject',
        'rendered_body',
        'gsm_segments',
        'provider_reference',
        'status',
        'attempts',
        'cost_pesewas',
        'cost_currency',
        'last_error',
        'is_retryable',
        'next_retry_at',
        'ambiguous_timeout_at',
        'sent_at',
        'delivered_at',
        'failed_at',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'gsm_segments' => 'integer',
            'attempts' => 'integer',
            'cost_pesewas' => 'integer',
            'is_retryable' => 'boolean',
            'next_retry_at' => 'datetime',
            'ambiguous_timeout_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<NotificationLog, $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class, 'notification_log_id');
    }

    /**
     * @return BelongsTo<NotificationTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplateVersion::class, 'notification_template_version_id');
    }
}
