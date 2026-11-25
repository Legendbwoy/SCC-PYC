<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'channel',
        'is_transactional',
        'current_version_id',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_transactional' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<NotificationTemplateVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplateVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<NotificationTemplateVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(NotificationTemplateVersion::class, 'notification_template_id')
            ->orderByDesc('version_number');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
