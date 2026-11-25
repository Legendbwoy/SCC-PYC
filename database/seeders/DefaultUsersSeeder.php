<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Modules\Membership\Models\MemberAccount;
use App\Support\Security\PycRole;
use Illuminate\Database\Seeder;

/**
 * Seeds default officer and member accounts for local/staging environments using password: password123.
 * Never runs automatically in production.
 */
class DefaultUsersSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'password123';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipping DefaultUsersSeeder in production environment.');

            return;
        }

        $accounts = [
            [
                'name' => 'PYC Super Administrator',
                'email' => 'admin@stceciliapyc.org',
                'phone' => '+233240000001',
                'role' => PycRole::SuperAdministrator,
            ],
            [
                'name' => 'PYC President',
                'email' => 'president@stceciliapyc.org',
                'phone' => '+233240000002',
                'role' => PycRole::President,
            ],
            [
                'name' => 'PYC Treasurer',
                'email' => 'treasurer@stceciliapyc.org',
                'phone' => '+233240000003',
                'role' => PycRole::Treasurer,
            ],
            [
                'name' => 'PYC Financial Secretary',
                'email' => 'finsec@stceciliapyc.org',
                'phone' => '+233240000004',
                'role' => PycRole::FinancialSecretary,
            ],
            [
                'name' => 'PYC General Secretary',
                'email' => 'secretary@stceciliapyc.org',
                'phone' => '+233240000005',
                'role' => PycRole::GeneralSecretary,
            ],
            [
                'name' => 'PYC Auditor',
                'email' => 'auditor@stceciliapyc.org',
                'phone' => '+233240000006',
                'role' => PycRole::Auditor,
            ],
            [
                'name' => 'Ama Mensah (PYC Member)',
                'email' => 'member@stceciliapyc.org',
                'phone' => '+233240000007',
                'role' => PycRole::Member,
            ],
        ];

        foreach ($accounts as $item) {
            $user = User::query()->where('email', $item['email'])->first();

            if ($user === null) {
                $user = new User([
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'phone' => $item['phone'],
                    'password' => self::DEFAULT_PASSWORD,
                    'status' => 'active',
                    'must_change_password' => false,
                ]);
                $user->email_verified_at = now();
                $user->save();
            } else {
                $user->update([
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                    'password' => self::DEFAULT_PASSWORD,
                    'status' => 'active',
                    'must_change_password' => false,
                ]);
            }

            $user->syncRoles([$item['role']->value]);

            if ($item['role'] === PycRole::Member) {
                $member = Member::query()->firstOrCreate(
                    ['email' => $item['email']],
                    [
                        'member_number' => 'PYC-'.now()->year.'-0001',
                        'first_name' => 'Ama',
                        'last_name' => 'Mensah',
                        'phone' => $item['phone'],
                        'gender' => 'female',
                        'address' => 'St. Cecilia Parish Community, Accra',
                        'occupation' => 'University Student',
                        'joined_at' => now()->startOfYear()->toDateString(),
                        'approved_at' => now(),
                        'status' => MemberStatus::Active->value,
                    ]
                );

                MemberAccount::query()->updateOrCreate(
                    ['member_id' => $member->id],
                    [
                        'user_id' => $user->id,
                        'status' => 'active',
                        'activated_at' => now(),
                    ]
                );
            }
        }
    }
}
