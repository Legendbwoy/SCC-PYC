<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'parent_code',
        'is_postable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
