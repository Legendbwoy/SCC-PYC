<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    protected $fillable = [
        'provider',
        'name',
        'spreadsheet_id',
        'sheet_range',
        'field_mapping',
        'last_cursor',
        'last_synced_at',
        'status',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'field_mapping' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
