<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'default_income_account_code',
        'is_recurring',
        'is_mandatory',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_recurring' => 'boolean',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ContributionCampaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(ContributionCampaign::class);
    }
}
