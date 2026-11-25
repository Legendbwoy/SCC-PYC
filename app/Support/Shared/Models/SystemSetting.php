<?php

declare(strict_types=1);

namespace App\Support\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Non-secret persistent system configuration (Spec Section 10.6 & 18).
 */
class SystemSetting extends Model
{
    private const FORBIDDEN_KEY_PATTERNS = [
        'secret',
        'api_token',
        'api_key',
        'password',
        'private_key',
        'webhook_secret',
    ];

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $setting): void {
            $lowerKey = strtolower($setting->key);

            foreach (self::FORBIDDEN_KEY_PATTERNS as $forbidden) {
                if (str_contains($lowerKey, $forbidden)) {
                    throw new InvalidArgumentException(
                        "Security policy violation: secret credential key [{$setting->key}] must be stored in environment configuration, not system_settings."
                    );
                }
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
