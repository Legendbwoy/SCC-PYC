<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Shared\SystemSettingService;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(SystemSettingService $settingService): void
    {
        $defaults = [
            'organization.name' => [
                'group' => 'organization',
                'value' => config('pyc.organization.name', 'St. Cecilia Catholic Church — Parish Youth Council'),
                'description' => 'Official organization display name',
                'public' => true,
            ],
            'organization.short_name' => [
                'group' => 'organization',
                'value' => config('pyc.organization.short_name', 'SCC-PYC'),
                'description' => 'Organization short code',
                'public' => true,
            ],
            'organization.parish_name' => [
                'group' => 'organization',
                'value' => config('pyc.organization.parish_name', 'St. Cecilia Catholic Church'),
                'description' => 'Parish name',
                'public' => true,
            ],
            'organization.currency' => [
                'group' => 'organization',
                'value' => 'GHS',
                'description' => 'Reporting currency',
                'public' => true,
            ],
            'organization.timezone' => [
                'group' => 'organization',
                'value' => 'Africa/Accra',
                'description' => 'Parish operational timezone',
                'public' => true,
            ],
            'branding.tagline' => [
                'group' => 'branding',
                'value' => config('pyc.branding.tagline', 'Faith · Service · Leadership'),
                'description' => 'Portal motto or tagline placeholder',
                'public' => true,
            ],
            'branding.primary_color' => [
                'group' => 'branding',
                'value' => config('pyc.branding.primary_color', '#0F2D59'),
                'description' => 'Primary brand hex color placeholder',
                'public' => true,
            ],
            'branding.accent_color' => [
                'group' => 'branding',
                'value' => config('pyc.branding.accent_color', '#F5C542'),
                'description' => 'Accent brand hex color placeholder',
                'public' => true,
            ],
        ];

        foreach ($defaults as $key => $meta) {
            if ($settingService->get($key) === null) {
                $settingService->set(
                    key: $key,
                    value: $meta['value'],
                    group: $meta['group'],
                    type: 'string',
                    description: $meta['description'],
                    isPublic: $meta['public'],
                );
            }
        }
    }
}
