<?php

declare(strict_types=1);

namespace App\Support\Audit\Listeners;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Request;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

class SecurityEventSubscriber
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => Request::ip(),
        ])->saveQuietly();

        $this->auditLogger->log(
            event: 'auth.login',
            category: 'security',
            description: "User [{$event->user->email}] signed in.",
            auditable: $event->user,
            actor: $event->user,
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            event: 'auth.logout',
            category: 'security',
            description: "User [{$event->user->email}] signed out.",
            auditable: $event->user,
            actor: $event->user,
        );
    }

    public function handleFailed(Failed $event): void
    {
        $email = is_array($event->credentials) ? (string) ($event->credentials['email'] ?? 'unknown') : 'unknown';
        $actor = $event->user instanceof User ? $event->user : null;

        $this->auditLogger->log(
            event: 'auth.login_failed',
            category: 'security',
            description: "Failed authentication attempt for [{$email}].",
            auditable: $actor,
            metadata: ['attempted_email' => $email],
            actor: $actor,
        );
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            event: 'auth.password_reset',
            category: 'security',
            description: "Password reset completed for [{$event->user->email}].",
            auditable: $event->user,
            actor: $event->user,
        );
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            event: 'auth.2fa_confirmed',
            category: 'security',
            description: "Two-factor authentication confirmed for [{$event->user->email}].",
            auditable: $event->user,
            actor: $event->user,
        );
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            event: 'auth.2fa_disabled',
            category: 'security',
            description: "Two-factor authentication disabled for [{$event->user->email}].",
            auditable: $event->user,
            actor: $event->user,
        );
    }

    public function handleRecoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->auditLogger->log(
            event: 'auth.2fa_recovery_codes_regenerated',
            category: 'security',
            description: "Two-factor recovery codes regenerated for [{$event->user->email}].",
            auditable: $event->user,
            actor: $event->user,
        );
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
        $events->listen(Failed::class, [self::class, 'handleFailed']);
        $events->listen(PasswordReset::class, [self::class, 'handlePasswordReset']);
        $events->listen(TwoFactorAuthenticationConfirmed::class, [self::class, 'handleTwoFactorConfirmed']);
        $events->listen(TwoFactorAuthenticationDisabled::class, [self::class, 'handleTwoFactorDisabled']);
        $events->listen(RecoveryCodesGenerated::class, [self::class, 'handleRecoveryCodesGenerated']);
    }
}
