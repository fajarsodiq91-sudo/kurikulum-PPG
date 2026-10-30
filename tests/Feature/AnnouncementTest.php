<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_announcement_with_image(): void
    {
        Storage::fake('public');

        $role = Role::create(['name' => 'PPG', 'slug' => 'ppg', 'is_active' => true]);
        $permission = Permission::create([
            'name' => 'Manage Announcements',
            'slug' => 'manage-announcements',
            'module' => 'announcements',
            'is_active' => true,
        ]);
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG Daerah',
            'email' => 'ppg-daerah@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, 'global', null);

        $this->actingAs($user)
            ->post('/announcements', [
                'title' => 'Pengumuman Libur',
                'body' => 'Kegiatan KBM diliburkan pada tanggal 10 Oktober 2026.',
                'image' => UploadedFile::fake()->create('berita.jpg', 100, 'image/jpeg'),
                'template' => 'highlight',
                'status' => 'published',
            ])
            ->assertRedirect('/announcements');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Pengumuman Libur',
            'template' => 'highlight',
            'status' => 'published',
            'user_id' => $user->id,
        ]);

        $announcement = Announcement::query()->first();
        Storage::disk('public')->assertExists($announcement->image);
    }

    public function test_user_without_permission_cannot_create_announcement(): void
    {
        $user = User::create([
            'name' => 'Pengguna Biasa',
            'email' => 'biasa@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/announcements', [
                'title' => 'Berita Tanpa Izin',
                'body' => 'Harusnya ditolak.',
                'template' => 'default',
                'status' => 'published',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('announcements', ['title' => 'Berita Tanpa Izin']);
    }

    private function authorizedAnnouncementUser(): User
    {
        $role = Role::create(['name' => 'PPG', 'slug' => 'ppg-'.uniqid(), 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-announcements'],
            ['name' => 'Manage Announcements', 'module' => 'announcements', 'is_active' => true]
        );
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG Daerah',
            'email' => 'ppg-daerah-'.uniqid().'@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    public function test_publishing_a_new_announcement_emails_every_active_user(): void
    {
        Notification::fake();

        $author = $this->authorizedAnnouncementUser();
        $otherActiveUser = User::create(['name' => 'Warga', 'email' => 'warga@ppg.test', 'status' => 'active']);
        $inactiveUser = User::create(['name' => 'Nonaktif', 'email' => 'nonaktif@ppg.test', 'status' => 'inactive']);

        $this->actingAs($author)->post('/announcements', [
            'title' => 'Pengumuman Libur',
            'body' => 'Kegiatan KBM diliburkan.',
            'template' => 'default',
            'status' => 'published',
        ])->assertRedirect('/announcements');

        Notification::assertSentTo([$author, $otherActiveUser], AnnouncementPublished::class);
        Notification::assertNotSentTo($inactiveUser, AnnouncementPublished::class);
    }

    public function test_editing_an_already_published_announcement_does_not_resend_the_email(): void
    {
        $author = $this->authorizedAnnouncementUser();

        $this->actingAs($author)->post('/announcements', [
            'title' => 'Pengumuman Libur',
            'body' => 'Kegiatan KBM diliburkan.',
            'template' => 'default',
            'status' => 'published',
        ]);

        $announcement = Announcement::query()->firstOrFail();

        Notification::fake();

        $this->actingAs($author)->put("/announcements/{$announcement->id}", [
            'title' => 'Pengumuman Libur (diperbarui)',
            'body' => 'Kegiatan KBM diliburkan, direvisi.',
            'template' => 'default',
            'status' => 'published',
        ])->assertRedirect('/announcements');

        Notification::assertNothingSent();
    }

    public function test_a_draft_that_is_later_published_emails_active_users_only_once(): void
    {
        $author = $this->authorizedAnnouncementUser();

        $this->actingAs($author)->post('/announcements', [
            'title' => 'Draf Berita',
            'body' => 'Belum dipublikasikan.',
            'template' => 'default',
            'status' => 'draft',
        ]);

        $announcement = Announcement::query()->firstOrFail();

        Notification::fake();

        $this->actingAs($author)->put("/announcements/{$announcement->id}", [
            'title' => 'Draf Berita',
            'body' => 'Sekarang dipublikasikan.',
            'template' => 'default',
            'status' => 'published',
        ])->assertRedirect('/announcements');

        Notification::assertSentToTimes($author, AnnouncementPublished::class, 1);
    }
}
