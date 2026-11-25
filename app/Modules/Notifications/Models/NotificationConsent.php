<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationConsent extends Model
{
    protected $fillable = [
        'user_id',
        'member_id',
        'recipient_identifier',
        'channel',
        'purpose',
        'status',
        'lawful_basis',
        'source',
        'consented_at',
        'withdrawn_at',
        'recorded_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
