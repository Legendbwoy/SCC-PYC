<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            $this->auditLogger->log(
                event: 'auth.inactive_session_terminated',
                category: 'security',
                description: "Terminated active session for non-active user [{$user->email}] (status: {$user->status}).",
                auditable: $user,
                actor: $user,
            );

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is currently inactive. Please contact the PYC administrator.',
            ]);
        }

        return $next($request);
    }
}
