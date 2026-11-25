<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces Spec Section 4.4 & 17.4: designated administrators/officers must enable 2FA.
 */
class EnsureAdministratorTwoFactor
{
    /**
     * Routes permitted while an administrator is completing 2FA enrollment or logging out.
     *
     * @var list<string>
     */
    private const EXEMPT_ROUTE_PATTERNS = [
        'profile.security*',
        'two-factor.*',
        'password.confirm*',
        'user-password.*',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->requiresTwoFactor() || $user->hasConfirmedTwoFactorAuthentication()) {
            return $next($request);
        }

        foreach (self::EXEMPT_ROUTE_PATTERNS as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication setup is required for administrative accounts.',
            ], 403);
        }

        return redirect()
            ->route('profile.security')
            ->with('warning', 'Security Requirement: Two-factor authentication (2FA) must be enabled and confirmed for executive and administrative accounts before accessing the portal.');
    }
}
