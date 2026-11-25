<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'reference',
        'payload_hash',
        'payload',
        'processing_status',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
