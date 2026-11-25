<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'member_id',
        'sms_enabled',
        'email_enabled',
        'in_app_enabled',
        'category_preferences',
    ];

    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'category_preferences' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allowsOptionalChannel(string $channel, string $category): bool
    {
        $channelAllowed = match ($channel) {
            'sms' => $this->sms_enabled,
            'email' => $this->email_enabled,
            'in_app' => $this->in_app_enabled,
            default => false,
        };

        if (! $channelAllowed) {
            return false;
        }

        $categories = $this->category_preferences ?? [];

        if (array_key_exists($category, $categories)) {
            return (bool) $categories[$category];
        }

        return true;
    }
}
