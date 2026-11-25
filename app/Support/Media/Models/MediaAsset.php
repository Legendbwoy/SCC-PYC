<?php

declare(strict_types=1);

namespace App\Support\Media\Models;

use App\Models\User;
use App\Modules\Events\Models\PycEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    protected $fillable = [
        'collection',
        'event_id',
        'title',
        'caption',
        'disk',
        'file_path',
        'mime_type',
        'file_size',
        'is_public',
        'is_featured',
        'sort_order',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function url(): string
    {
        return asset('storage/'.ltrim($this->file_path, '/'));
    }

    public function formattedSize(): string
    {
        if ($this->file_size < 1024) {
            return $this->file_size.' B';
        }

        if ($this->file_size < 1048576) {
            return number_format($this->file_size / 1024, 1).' KB';
        }

        return number_format($this->file_size / 1048576, 2).' MB';
    }

    /**
     * @return BelongsTo<PycEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(PycEvent::class, 'event_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
