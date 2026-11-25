<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Media\MediaManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class SecurityProfileController extends Controller
{
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('portal.security-profile', [
            'user' => $user,
            'twoFactorEnabled' => ! empty($user->two_factor_secret),
            'twoFactorConfirmed' => $user->hasConfirmedTwoFactorAuthentication(),
        ]);
    }

    public function updateAvatar(Request $request, MediaManagementService $mediaService): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var UploadedFile $file */
        $file = $request->file('avatar');

        $mediaService->updateUserAvatar($user, $file, $user);

        return back()->with('status', 'Your profile photo has been updated.');
    }

    public function removeAvatar(Request $request, MediaManagementService $mediaService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $mediaService->removeUserAvatar($user, $user);

        return back()->with('status', 'Your profile photo has been removed.');
    }
}
