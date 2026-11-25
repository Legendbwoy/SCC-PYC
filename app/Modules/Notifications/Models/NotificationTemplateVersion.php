<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Models\User;
use App\Support\Shared\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable versioned template snapshot (Spec Section 10.10 & 10.11).
 */
class NotificationTemplateVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'notification_template_id',
        'version_number',
        'subject',
        'body',
        'required_variables',
        'change_notes',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'required_variables' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new ImmutableRecordException('Notification template versions are immutable; create a new version instead.');
        });

        static::deleting(static function (): never {
            throw new ImmutableRecordException('Notification template versions are immutable and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<NotificationTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
