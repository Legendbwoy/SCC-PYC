<?php

declare(strict_types=1);

namespace App\Modules\Events\Models;

use App\Models\User;
use App\Modules\Contributions\Models\ContributionCampaign;
use App\Support\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PycEvent extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'reference',
        'title',
        'description',
        'venue',
        'banner_image_path',
        'starts_at',
        'ends_at',
        'registration_deadline',
        'capacity',
        'allow_registration',
        'allow_cancellation',
        'contribution_campaign_id',
        'status',
        'cancellation_reason',
        'announced_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_deadline' => 'datetime',
            'capacity' => 'integer',
            'allow_registration' => 'boolean',
            'allow_cancellation' => 'boolean',
            'announced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContributionCampaign, $this>
     */
    public function contributionCampaign(): BelongsTo
    {
        return $this->belongsTo(ContributionCampaign::class, 'contribution_campaign_id');
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    public function bannerUrl(): ?string
    {
        if (! empty($this->banner_image_path)) {
            return asset('storage/'.ltrim($this->banner_image_path, '/'));
        }

        return null;
    }

    /**
     * @return HasMany<MediaAsset, $this>
     */
    public function galleryPhotos(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'event_id')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
