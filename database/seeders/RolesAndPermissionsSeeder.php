<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Security\PycPermission;
use App\Support\Security\PycRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PycPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (PycRole::cases() as $pycRole) {
            $role = Role::findOrCreate($pycRole->value, 'web');
            $permissionNames = array_map(
                static fn (PycPermission $perm): string => $perm->value,
                $pycRole->defaultPermissions()
            );

            $role->syncPermissions($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
