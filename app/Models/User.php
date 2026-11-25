<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Security\PycRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'must_change_password',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExecutiveOfficer(): bool
    {
        return $this->hasAnyRole(PycRole::executiveRoleNames());
    }

    public function requiresTwoFactor(): bool
    {
        if (! (bool) config('pyc.security.enforce_admin_2fa', true)) {
            return false;
        }

        /** @var list<string> $requiredRoles */
        $requiredRoles = config('pyc.security.two_factor_required_roles', PycRole::executiveRoleNames());

        return $this->hasAnyRole($requiredRoles);
    }

    public function hasConfirmedTwoFactorAuthentication(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function primaryRoleName(): string
    {
        /** @var string|null $role */
        $role = $this->getRoleNames()->first();

        return $role ?? PycRole::Member->value;
    }
}
