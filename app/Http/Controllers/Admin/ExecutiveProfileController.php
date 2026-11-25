<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Media\MediaManagementService;
use App\Support\Media\Models\ExecutiveProfile;
use App\Support\Security\PycPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ExecutiveProfileController extends Controller
{
    public function __construct(
        private readonly MediaManagementService $mediaService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->isExecutiveOfficer(), 403);

        $executives = ExecutiveProfile::query()
            ->with('editor')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.executives.index', [
            'executives' => $executives,
            'canManageExecutives' => $this->canManageExecutives($user),
            'nextSortOrder' => ((int) $executives->max('sort_order')) + 1,
            'stats' => [
                'total' => $executives->count(),
                'active' => $executives->where('is_active', true)->count(),
                'with_photos' => $executives->filter(static fn (ExecutiveProfile $e): bool => ! empty($e->photo_path))->count(),
                'hidden' => $executives->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageExecutives($actor), 403);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'tenure' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:400'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var UploadedFile|null $photo */
        $photo = $request->file('photo');

        $profile = $this->mediaService->saveExecutiveProfile(
            profile: null,
            attributes: [
                'full_name' => $validated['full_name'],
                'position' => $validated['position'],
                'tenure' => $validated['tenure'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ],
            photo: $photo,
            actor: $actor,
        );

        return redirect()
            ->route('admin.executives.index')
            ->with('status', "Added {$profile->full_name} ({$profile->position}) to Know Your Executives.");
    }

    public function update(Request $request, ExecutiveProfile $executive): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageExecutives($actor), 403);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'tenure' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:400'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var UploadedFile|null $photo */
        $photo = $request->file('photo');

        $this->mediaService->saveExecutiveProfile(
            profile: $executive,
            attributes: [
                'full_name' => $validated['full_name'],
                'position' => $validated['position'],
                'tenure' => $validated['tenure'] ?? null,
                'bio' => $validated['bio'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $executive->sort_order,
                'is_active' => $request->boolean('is_active'),
            ],
            photo: $photo,
            actor: $actor,
        );

        return redirect()
            ->route('admin.executives.index')
            ->with('status', "Updated executive profile for {$executive->full_name}.");
    }

    public function removePhoto(Request $request, ExecutiveProfile $executive): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageExecutives($actor), 403);

        $this->mediaService->removeExecutivePhoto($executive, $actor);

        return redirect()
            ->route('admin.executives.index')
            ->with('status', "Removed photo for {$executive->full_name}.");
    }

    public function destroy(Request $request, ExecutiveProfile $executive): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($this->canManageExecutives($actor), 403);

        $name = $executive->full_name;
        $this->mediaService->deleteExecutiveProfile($executive, $actor);

        return redirect()
            ->route('admin.executives.index')
            ->with('status', "Removed {$name} from Know Your Executives.");
    }

    private function canManageExecutives(User $user): bool
    {
        return $user->isExecutiveOfficer() && (
            $user->can(PycPermission::SettingsManage->value)
            || $user->can(PycPermission::UsersManage->value)
            || $user->can(PycPermission::NotificationsSend->value)
        );
    }
}
