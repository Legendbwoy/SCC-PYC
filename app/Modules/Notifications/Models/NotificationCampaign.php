<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'reference',
        'title',
        'category',
        'channels',
        'audience_filter',
        'subject',
        'sms_body',
        'email_body',
        'status',
        'scheduled_at',
        'dispatched_at',
        'cancelled_at',
        'total_recipients',
        'created_by',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience_filter' => 'array',
            'scheduled_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'total_recipients' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<NotificationLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'campaign_id');
    }
}
