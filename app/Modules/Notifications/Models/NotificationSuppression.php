<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSuppression extends Model
{
    protected $fillable = [
        'channel',
        'recipient_address',
        'reason',
        'source',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
