<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Secure one-time administrator provisioning without shared default passwords (Spec Section 18.2).
 */
class ProvisionAdminCommand extends Command
{
    protected $signature = 'pyc:provision-admin
        {--name=PYC Super Administrator : Full name of the initial administrator}
        {--email=admin@stceciliapyc.org : Email address of the initial administrator}
        {--phone= : Optional phone number}
        {--force : Allow provisioning even if a Super Administrator already exists (non-production only)}';

    protected $description = 'Provision a one-time initial Super Administrator account with a generated strong password.';

    public function handle(AuditLogger $auditLogger): int
    {
        Role::findOrCreate(PycRole::SuperAdministrator->value, 'web');

        $existingAdmins = User::role(PycRole::SuperAdministrator->value)->count();

        if ($existingAdmins > 0 && (! $this->option('force') || app()->isProduction())) {
            $this->error('A Super Administrator account already exists. One-time bootstrap aborted.');

            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $this->option('email')));
        $name = trim((string) $this->option('name'));
        $phone = $this->option('phone') ? trim((string) $this->option('phone')) : null;

        if (User::query()->where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        $oneTimePassword = Str::password(20, letters: true, numbers: true, symbols: true, spaces: false);

        $admin = DB::transaction(function () use ($name, $email, $phone, $oneTimePassword, $auditLogger): User {
            $user = new User([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $oneTimePassword,
                'status' => 'active',
                'must_change_password' => true,
            ]);
            $user->email_verified_at = now();
            $user->save();

            $user->assignRole(PycRole::SuperAdministrator->value);

            $auditLogger->log(
                event: 'users.bootstrap_admin_provisioned',
                category: 'security',
                description: "Provisioned initial Super Administrator [{$user->email}] via CLI.",
                auditable: $user,
                actor: $user,
            );

            return $user;
        });

        $this->info('Super Administrator provisioned successfully.');
        $this->warn('Copy this one-time password immediately — it will not be shown again and 2FA will be required on sign-in:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $admin->name],
                ['Email', $admin->email],
                ['Role', PycRole::SuperAdministrator->value],
                ['One-Time Password', $oneTimePassword],
            ]
        );

        return self::SUCCESS;
    }
}
