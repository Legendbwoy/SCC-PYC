<?php

declare(strict_types=1);

namespace App\Support\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutiveProfile extends Model
{
    protected $fillable = [
        'full_name',
        'position',
        'tenure',
        'bio',
        'photo_path',
        'sort_order',
        'is_active',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function photoUrl(): ?string
    {
        if (! empty($this->photo_path)) {
            return asset('storage/'.ltrim($this->photo_path, '/'));
        }

        return null;
    }

    public function initials(): string
    {
        $cleaned = preg_replace('/^(Rev\.?|Fr\.?|Sr\.?|Mr\.?|Mrs\.?|Ms\.?|Dr\.?)\s+/i', '', trim($this->full_name)) ?? $this->full_name;
        $parts = preg_split('/\s+/', trim($cleaned)) ?: ['P'];

        return strtoupper(substr($parts[0] ?? 'P', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
