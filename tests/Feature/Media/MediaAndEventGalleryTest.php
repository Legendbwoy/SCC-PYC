<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\User;
use App\Modules\Events\Models\PycEvent;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Support\Media\Models\MediaAsset;
use App\Support\Security\PycRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAndEventGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_executive_can_upload_edit_feature_and_delete_event_gallery_photos(): void
    {
        Storage::fake('public');

        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);

        $event = PycEvent::query()->create([
            'reference' => 'PYC-EVT-2026-0009',
            'title' => 'Lenten Youth Retreat',
            'venue' => 'St. Cecilia Parish Hall',
            'starts_at' => now()->addWeek(),
            'status' => 'published',
            'created_by' => $secretary->id,
        ]);

        $this->actingAs($secretary)
            ->post(route('admin.media.gallery.store'), [
                'event_id' => $event->id,
                'title' => 'Retreat Worship',
                'caption' => 'Youth praise and worship session',
                'is_public' => '1',
                'is_featured' => '1',
                'images' => [
                    UploadedFile::fake()->image('worship-1.jpg', 800, 600),
                    UploadedFile::fake()->image('worship-2.png', 800, 600),
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('media_assets', 2);

        /** @var MediaAsset $firstAsset */
        $firstAsset = MediaAsset::query()->firstOrFail();
        Storage::disk('public')->assertExists($firstAsset->file_path);

        // Public homepage renders the featured gallery photo
        $this->get('/')
            ->assertOk()
            ->assertSee('Event &amp; Fellowship Gallery', false)
            ->assertSee('Retreat Worship');

        // Event banner upload & removal
        $this->actingAs($secretary)
            ->post(route('events.banner.update', $event), [
                'banner_image' => UploadedFile::fake()->image('flyer.jpg', 900, 600),
            ])
            ->assertRedirect();

        $event->refresh();
        $this->assertNotNull($event->banner_image_path);
        Storage::disk('public')->assertExists($event->banner_image_path);

        // Delete gallery photo removes file and database row
        $pathToDelete = $firstAsset->file_path;
        $this->actingAs($secretary)
            ->delete(route('admin.media.gallery.destroy', $firstAsset))
            ->assertRedirect();

        $this->assertDatabaseMissing('media_assets', ['id' => $firstAsset->id]);
        Storage::disk('public')->assertMissing($pathToDelete);
    }

    public function test_super_admin_manages_branding_images_while_auditor_and_member_are_blocked(): void
    {
        Storage::fake('public');

        $admin = User::factory()->withConfirmedTwoFactor()->create();
        $admin->assignRole(PycRole::SuperAdministrator->value);

        $auditor = User::factory()->withConfirmedTwoFactor()->create();
        $auditor->assignRole(PycRole::Auditor->value);

        $memberUser = User::factory()->create();
        $memberUser->assignRole(PycRole::Member->value);

        // Super Admin uploads custom hero image
        $this->actingAs($admin)
            ->post(route('admin.media.branding.update', 'hero_image'), [
                'image' => UploadedFile::fake()->image('pyc-hero.jpg', 1200, 800),
            ])
            ->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('storage/branding/hero/', false);

        // Auditor can view media library read-only, but cannot upload gallery or branding images
        $this->actingAs($auditor)
            ->get(route('admin.media.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->post(route('admin.media.gallery.store'), [
                'images' => [UploadedFile::fake()->image('blocked.jpg')],
            ])
            ->assertForbidden();

        $this->actingAs($auditor)
            ->post(route('admin.media.branding.update', 'logo'), [
                'image' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertForbidden();

        // Member is forbidden from admin media library, but can upload own profile avatar
        $this->actingAs($memberUser)
            ->get(route('admin.media.index'))
            ->assertForbidden();

        $this->actingAs($memberUser)
            ->post(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
            ])
            ->assertRedirect();

        $this->assertNotNull($memberUser->fresh()?->avatar_path);

        // Secretary can upload member portrait photo
        $secretary = User::factory()->withConfirmedTwoFactor()->create();
        $secretary->assignRole(PycRole::GeneralSecretary->value);
        $member = Member::query()->create([
            'member_number' => 'PYC-2026-0099',
            'first_name' => 'Kojo',
            'last_name' => 'Owusu',
            'phone' => '+233241112233',
            'status' => MemberStatus::Active->value,
        ]);

        $this->actingAs($secretary)
            ->post(route('membership.photo', $member), [
                'photo' => UploadedFile::fake()->image('portrait.jpg', 400, 400),
            ])
            ->assertRedirect();

        $this->assertNotNull($member->fresh()?->photo_path);
    }
}
