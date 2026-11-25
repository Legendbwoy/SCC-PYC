<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Membership\Services\MemberInvitationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class MemberInvitationController extends Controller
{
    public function __construct(
        private readonly MemberInvitationService $invitationService,
    ) {}

    public function show(string $token): View
    {
        $invitation = $this->invitationService->findUsableByToken($token);

        if ($invitation === null || $invitation->member === null) {
            return view('auth.invitation-invalid');
        }

        return view('auth.accept-invitation', [
            'token' => $token,
            'invitation' => $invitation,
            'member' => $invitation->member,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'optional_consent' => ['nullable', 'boolean'],
        ]);

        try {
            $user = $this->invitationService->acceptInvitation(
                plainToken: $token,
                email: $validated['email'],
                password: $validated['password'],
                optionalConsent: $request->boolean('optional_consent', true),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['invitation' => $exception->getMessage()])->withInput();
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Welcome to the St. Cecilia PYC Member Portal! Your account is now active.');
    }
}
