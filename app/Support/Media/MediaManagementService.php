<?php

declare(strict_types=1);

namespace App\Support\Media;

use App\Models\User;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Membership\Models\Member;
use App\Support\Audit\AuditLogger;
use App\Support\Media\Models\ExecutiveProfile;
use App\Support\Media\Models\MediaAsset;
use App\Support\Shared\SystemSettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaManagementService
{
    /**
     * Allowed configurable site/branding image slots stored in system_settings.
     *
     * @var array<string, array{setting_key: string, directory: string, label: string}>
     */
    public const BRANDING_SLOTS = [
        'logo' => [
            'setting_key' => 'branding.logo_path',
            'directory' => 'branding/logos',
            'label' => 'Organization Crest / Logo',
        ],
        'favicon' => [
            'setting_key' => 'branding.favicon_path',
            'directory' => 'branding/favicons',
            'label' => 'Browser Favicon',
        ],
        'hero_image' => [
            'setting_key' => 'branding.hero_image_path',
            'directory' => 'branding/hero',
            'label' => 'Public Landing Hero Photo',
        ],
        'login_backdrop' => [
            'setting_key' => 'branding.login_bg_path',
            'directory' => 'branding/login',
            'label' => 'Login Screen Backdrop Image',
        ],
        'default_event_banner' => [
            'setting_key' => 'branding.default_event_banner_path',
            'directory' => 'branding/events',
            'label' => 'Default Event Cover Image',
        ],
    ];

    public function __construct(
        private readonly SystemSettingService $settingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Upload one or more gallery photos (linked to an event or general parish gallery).
     *
     * @param  list<UploadedFile>  $files
     * @param  array<string, mixed>  $metadata
     * @return list<MediaAsset>
     */
    public function uploadGalleryPhotos(array $files, array $metadata, User $actor): array
    {
        $eventId = ! empty($metadata['event_id']) ? (int) $metadata['event_id'] : null;
        $collection = $eventId !== null ? 'event_gallery' : (string) ($metadata['collection'] ?? 'parish_gallery');
        $directory = 'gallery/'.now()->format('Y');
        $created = [];

        foreach ($files as $file) {
            $storedPath = $file->store($directory, 'public');
            if ($storedPath === false) {
                continue;
            }

            $asset = MediaAsset::query()->create([
                'collection' => $collection,
                'event_id' => $eventId,
                'title' => ! empty($metadata['title']) ? trim((string) $metadata['title']) : null,
                'caption' => ! empty($metadata['caption']) ? trim((string) $metadata['caption']) : null,
                'disk' => 'public',
                'file_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => (int) $file->getSize(),
                'is_public' => (bool) ($metadata['is_public'] ?? true),
                'is_featured' => (bool) ($metadata['is_featured'] ?? false),
                'uploaded_by' => $actor->id,
            ]);

            $created[] = $asset;
        }

        if ($created !== []) {
            $count = count($created);
            $this->auditLogger->log(
                event: 'media.gallery_uploaded',
                category: 'media',
                description: "Uploaded {$count} photo(s) to [{$collection}]".($eventId ? " (Event #{$eventId})" : '').'.',
                auditable: $created[0],
                newValues: ['count' => $count, 'collection' => $collection, 'event_id' => $eventId],
                actor: $actor,
            );
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateGalleryAsset(MediaAsset $asset, array $attributes, User $actor): MediaAsset
    {
        $old = $asset->only(['title', 'caption', 'event_id', 'is_public', 'is_featured']);
        $eventId = array_key_exists('event_id', $attributes)
            ? (! empty($attributes['event_id']) ? (int) $attributes['event_id'] : null)
            : $asset->event_id;

        $asset->update([
            'event_id' => $eventId,
            'collection' => $eventId !== null ? 'event_gallery' : 'parish_gallery',
            'title' => array_key_exists('title', $attributes) ? ($attributes['title'] !== null ? trim((string) $attributes['title']) : null) : $asset->title,
            'caption' => array_key_exists('caption', $attributes) ? ($attributes['caption'] !== null ? trim((string) $attributes['caption']) : null) : $asset->caption,
            'is_public' => array_key_exists('is_public', $attributes) ? (bool) $attributes['is_public'] : $asset->is_public,
            'is_featured' => array_key_exists('is_featured', $attributes) ? (bool) $attributes['is_featured'] : $asset->is_featured,
        ]);

        $this->auditLogger->log(
            event: 'media.gallery_updated',
            category: 'media',
            description: "Updated gallery photo #{$asset->id}.",
            auditable: $asset,
            oldValues: $old,
            newValues: $asset->only(['title', 'caption', 'event_id', 'is_public', 'is_featured']),
            actor: $actor,
        );

        return $asset;
    }

    public function deleteGalleryAsset(MediaAsset $asset, User $actor): void
    {
        $path = $asset->file_path;
        $disk = $asset->disk ?: 'public';
        $assetId = $asset->id;

        $asset->delete();
        $this->deleteStoredFile($path, $disk);

        $this->auditLogger->log(
            event: 'media.gallery_deleted',
            category: 'media',
            description: "Deleted gallery photo #{$assetId} ({$path}).",
            oldValues: ['id' => $assetId, 'file_path' => $path],
            actor: $actor,
        );
    }

    public function updateEventBanner(PycEvent $event, UploadedFile $file, User $actor): PycEvent
    {
        $oldPath = $event->banner_image_path;
        $newPath = $file->store('events/banners', 'public');

        if ($newPath === false) {
            throw new InvalidArgumentException('Unable to store event banner image.');
        }

        $event->update(['banner_image_path' => $newPath]);

        if (! empty($oldPath)) {
            $this->deleteStoredFile($oldPath);
        }

        $this->auditLogger->log(
            event: 'events.banner_updated',
            category: 'events',
            description: "Updated cover banner image for event [{$event->reference}].",
            auditable: $event,
            oldValues: ['banner_image_path' => $oldPath],
            newValues: ['banner_image_path' => $newPath],
            actor: $actor,
        );

        return $event;
    }

    public function removeEventBanner(PycEvent $event, User $actor): PycEvent
    {
        $oldPath = $event->banner_image_path;
        if (! empty($oldPath)) {
            $event->update(['banner_image_path' => null]);
            $this->deleteStoredFile($oldPath);

            $this->auditLogger->log(
                event: 'events.banner_removed',
                category: 'events',
                description: "Removed cover banner image for event [{$event->reference}].",
                auditable: $event,
                oldValues: ['banner_image_path' => $oldPath],
                actor: $actor,
            );
        }

        return $event;
    }

    public function updateBrandingSlot(string $slot, UploadedFile $file, User $actor): string
    {
        if (! isset(self::BRANDING_SLOTS[$slot])) {
            throw new InvalidArgumentException("Unknown branding image slot [{$slot}].");
        }

        $meta = self::BRANDING_SLOTS[$slot];
        $oldPath = (string) $this->settingService->get($meta['setting_key'], '');
        $newPath = $file->store($meta['directory'], 'public');

        if ($newPath === false) {
            throw new InvalidArgumentException('Unable to store branding image.');
        }

        $this->settingService->set(
            key: $meta['setting_key'],
            value: $newPath,
            group: 'branding',
            type: 'string',
            description: $meta['label'],
            isPublic: true,
            actor: $actor,
        );

        if ($oldPath !== '') {
            $this->deleteStoredFile($oldPath);
        }

        return $newPath;
    }

    public function clearBrandingSlot(string $slot, User $actor): void
    {
        if (! isset(self::BRANDING_SLOTS[$slot])) {
            throw new InvalidArgumentException("Unknown branding image slot [{$slot}].");
        }

        $meta = self::BRANDING_SLOTS[$slot];
        $oldPath = (string) $this->settingService->get($meta['setting_key'], '');

        $this->settingService->set(
            key: $meta['setting_key'],
            value: '',
            group: 'branding',
            type: 'string',
            description: $meta['label'],
            isPublic: true,
            actor: $actor,
        );

        if ($oldPath !== '') {
            $this->deleteStoredFile($oldPath);
        }
    }

    public function updateUserAvatar(User $targetUser, UploadedFile $file, User $actor): User
    {
        $oldPath = $targetUser->avatar_path;
        $newPath = $file->store('avatars/users', 'public');

        if ($newPath === false) {
            throw new InvalidArgumentException('Unable to store profile photo.');
        }

        $targetUser->update(['avatar_path' => $newPath]);

        if (! empty($oldPath)) {
            $this->deleteStoredFile($oldPath);
        }

        $this->auditLogger->log(
            event: 'users.avatar_updated',
            category: 'users',
            description: "Updated profile photo for [{$targetUser->email}].",
            auditable: $targetUser,
            actor: $actor,
        );

        return $targetUser;
    }

    public function removeUserAvatar(User $targetUser, User $actor): User
    {
        $oldPath = $targetUser->avatar_path;
        if (! empty($oldPath)) {
            $targetUser->update(['avatar_path' => null]);
            $this->deleteStoredFile($oldPath);
        }

        return $targetUser;
    }

    public function updateMemberPhoto(Member $member, UploadedFile $file, User $actor): Member
    {
        $oldPath = $member->photo_path;
        $newPath = $file->store('avatars/members', 'public');

        if ($newPath === false) {
            throw new InvalidArgumentException('Unable to store member photo.');
        }

        $member->update(['photo_path' => $newPath]);

        if (! empty($oldPath)) {
            $this->deleteStoredFile($oldPath);
        }

        $this->auditLogger->log(
            event: 'members.photo_updated',
            category: 'membership',
            description: "Updated member photo for [{$member->fullName()}].",
            auditable: $member,
            actor: $actor,
        );

        return $member;
    }

    /**
     * Create or update a Know Your Executives (KYE) profile and optional portrait photo.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveExecutiveProfile(
        ?ExecutiveProfile $profile,
        array $attributes,
        ?UploadedFile $photo,
        User $actor,
    ): ExecutiveProfile {
        $isNew = $profile === null;
        $oldPhotoPath = $profile?->photo_path;
        $newPhotoPath = $oldPhotoPath;

        if ($photo !== null) {
            $stored = $photo->store('executives', 'public');
            if ($stored === false) {
                throw new InvalidArgumentException('Unable to store executive portrait photo.');
            }
            $newPhotoPath = $stored;
        }

        $payload = [
            'full_name' => trim((string) $attributes['full_name']),
            'position' => trim((string) $attributes['position']),
            'tenure' => ! empty($attributes['tenure']) ? trim((string) $attributes['tenure']) : null,
            'bio' => ! empty($attributes['bio']) ? trim((string) $attributes['bio']) : null,
            'photo_path' => $newPhotoPath,
            'sort_order' => isset($attributes['sort_order']) ? (int) $attributes['sort_order'] : 0,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'updated_by' => $actor->id,
        ];

        if ($isNew) {
            $profile = ExecutiveProfile::query()->create($payload);
        } else {
            $profile->update($payload);
        }

        if ($photo !== null && ! empty($oldPhotoPath) && $oldPhotoPath !== $newPhotoPath) {
            $this->deleteStoredFile($oldPhotoPath);
        }

        $this->auditLogger->log(
            event: $isNew ? 'executives.created' : 'executives.updated',
            category: 'media',
            description: ($isNew ? 'Added' : 'Updated')." KYE executive profile [{$profile->full_name} — {$profile->position}].",
            auditable: $profile,
            newValues: $profile->only(['full_name', 'position', 'tenure', 'sort_order', 'is_active', 'photo_path']),
            actor: $actor,
        );

        return $profile;
    }

    public function removeExecutivePhoto(ExecutiveProfile $profile, User $actor): ExecutiveProfile
    {
        $oldPath = $profile->photo_path;
        if (! empty($oldPath)) {
            $profile->update([
                'photo_path' => null,
                'updated_by' => $actor->id,
            ]);
            $this->deleteStoredFile($oldPath);

            $this->auditLogger->log(
                event: 'executives.photo_removed',
                category: 'media',
                description: "Removed portrait photo for executive [{$profile->full_name}].",
                auditable: $profile,
                oldValues: ['photo_path' => $oldPath],
                actor: $actor,
            );
        }

        return $profile;
    }

    public function deleteExecutiveProfile(ExecutiveProfile $profile, User $actor): void
    {
        $oldPath = $profile->photo_path;
        $summary = "{$profile->full_name} ({$profile->position})";
        $id = $profile->id;

        $profile->delete();

        if (! empty($oldPath)) {
            $this->deleteStoredFile($oldPath);
        }

        $this->auditLogger->log(
            event: 'executives.deleted',
            category: 'media',
            description: "Deleted KYE executive profile #{$id}: {$summary}.",
            oldValues: ['id' => $id, 'summary' => $summary, 'photo_path' => $oldPath],
            actor: $actor,
        );
    }

    private function deleteStoredFile(string $path, string $disk = 'public'): void
    {
        if ($path !== '' && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
