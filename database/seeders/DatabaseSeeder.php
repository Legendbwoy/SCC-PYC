<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the PYC foundation data.
     * Spec Section 18.2: Do NOT seed shared default passwords or production API secrets.
     * Use `php artisan pyc:provision-admin` for one-time administrator provisioning.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SystemSettingsSeeder::class,
        ]);
    }
}
