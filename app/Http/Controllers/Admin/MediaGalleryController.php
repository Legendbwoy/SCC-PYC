<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Events\Models\PycEvent;
use App\Support\Media\MediaManagementService;
use App\Support\Media\Models\MediaAsset;
use App\Support\Security\PycPermission;
use App\Support\Shared\Models\SystemSetting;
use App\Support\Shared\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class MediaGalleryController extends Controller
{
    public function __construct(
        private readonly MediaManagementService $mediaService,
        private readonly SystemSettingService $settingService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->isExecutiveOfficer(), 403);

        $eventFilter = $request->string('event_id')->trim()->toString();
        $visibility = $request->string('visibility')->trim()->toString();

        $query = MediaAsset::query()
            ->with(['event', 'uploader'])
            ->orderByDesc('is_featured')
            ->latest('id');

        if ($eventFilter === 'general') {
            $query->whereNull('event_id');
        } elseif ($eventFilter !== '' && ctype_digit($eventFilter)) {
            $query->where('event_id', (int) $eventFilter);
        }

        if ($visibility === 'featured') {
            $query->where('is_featured', true);
        } elseif ($visibility === 'public') {
            $query->where('is_public', true);
        } elseif ($visibility === 'private') {
            $query->where('is_public', false);
        }

        $branding = $this->settingService->branding();
        $configuredSlotsCount = collect([
            $branding['logo_path'] ?? '',
            $branding['favicon_path'] ?? '',
            $branding['hero_image_path'] ?? '',
            $branding['login_bg_path'] ?? '',
            $branding['default_event_banner_path'] ?? '',
        ])->filter(static fn (string $v): bool => $v !== '')->count();

        return view('admin.media.index', [
            'assets' => $query->paginate(16)->withQueryString(),
            'events' => PycEvent::query()->orderByDesc('starts_at')->get(['id', 'reference', 'title', 'starts_at']),
            'branding' => $branding,
            'brandingSlots' => MediaManagementService::BRANDING_SLOTS,
            'canManageGallery' => $this->canManageGallery($user),
            'canManageBranding' => $user->can('update', SystemSetting::class),
            'filters' => [
                'event_id' => $eventFilter,
                'visibility' => $visibility,
            ],
            'stats' => [
                'total_photos' => MediaAsset::query()->count(),
                'featured_photos' => MediaAsset::query()->where('is_featured', true)->count(),
                'events_with_photos' => (int) MediaAsset::query()->whereNotNull('event_id')->distinct('event_id')->count('event_id'),
                'configured_branding' => $configuredSlotsCount,
            ],
        ]);
    }

    public function storeGallery(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageGallery($actor), 403);

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'title' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:500'],
            'is_public' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        /** @var list<UploadedFile> $files */
        $files = array_values($request->file('images', []));

        $uploaded = $this->mediaService->uploadGalleryPhotos(
            files: $files,
            metadata: [
                'event_id' => $validated['event_id'] ?? null,
                'title' => $validated['title'] ?? null,
                'caption' => $validated['caption'] ?? null,
                'is_public' => $request->boolean('is_public', true),
                'is_featured' => $request->boolean('is_featured', false),
            ],
            actor: $actor,
        );

        return back()->with('status', count($uploaded).' photo(s) uploaded to the gallery.');
    }

    public function updateGallery(Request $request, MediaAsset $mediaAsset): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageGallery($actor), 403);

        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'title' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:500'],
            'is_public' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $this->mediaService->updateGalleryAsset(
            asset: $mediaAsset,
            attributes: [
                'event_id' => $validated['event_id'] ?? null,
                'title' => $validated['title'] ?? null,
                'caption' => $validated['caption'] ?? null,
                'is_public' => $request->boolean('is_public'),
                'is_featured' => $request->boolean('is_featured'),
            ],
            actor: $actor,
        );

        return back()->with('status', 'Gallery photo details updated.');
    }

    public function destroyGallery(Request $request, MediaAsset $mediaAsset): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageGallery($actor), 403);

        $this->mediaService->deleteGalleryAsset($mediaAsset, $actor);

        return back()->with('status', 'Gallery photo deleted.');
    }

    public function updateBrandingImage(Request $request, string $slot): RedirectResponse
    {
        Gate::authorize('update', SystemSetting::class);

        abort_unless(isset(MediaManagementService::BRANDING_SLOTS[$slot]), 404);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('image');

        $this->mediaService->updateBrandingSlot($slot, $file, $actor);

        $label = MediaManagementService::BRANDING_SLOTS[$slot]['label'];

        return back()->with('status', "{$label} updated.");
    }

    public function removeBrandingImage(Request $request, string $slot): RedirectResponse
    {
        Gate::authorize('update', SystemSetting::class);

        abort_unless(isset(MediaManagementService::BRANDING_SLOTS[$slot]), 404);

        /** @var User $actor */
        $actor = $request->user();
        $this->mediaService->clearBrandingSlot($slot, $actor);

        $label = MediaManagementService::BRANDING_SLOTS[$slot]['label'];

        return back()->with('status', "{$label} reset to default.");
    }

    private function canManageGallery(User $user): bool
    {
        return $user->isExecutiveOfficer() && (
            $user->can(PycPermission::NotificationsSend->value)
            || $user->can(PycPermission::SettingsManage->value)
        );
    }
}
