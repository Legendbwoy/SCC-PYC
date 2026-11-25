<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Membership\Models\MemberGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MemberGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'CYO — Catholic Youth Organization', 'description' => 'Catholic Youth Organization parish unit'],
            ['name' => 'YCS — Young Christian Students', 'description' => 'Young Christian Students movement'],
            ['name' => 'CASU — Catholic Action Students Union', 'description' => 'Catholic Action Students Union'],
            ['name' => 'KLBS — Knights & Ladies of the Blessed Sacrament', 'description' => 'Altar servers and liturgical youth society'],
            ['name' => 'Youth Choir & Liturgical Music', 'description' => 'Parish Youth Choir ministry'],
            ['name' => 'Youth Ushers & Hospitality', 'description' => 'Hospitality and liturgical ushering team'],
        ];

        foreach ($groups as $group) {
            MemberGroup::query()->firstOrCreate(
                ['slug' => Str::slug($group['name'])],
                [
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
