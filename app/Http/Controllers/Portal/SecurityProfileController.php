<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
}
