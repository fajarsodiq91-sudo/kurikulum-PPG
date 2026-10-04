<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
}
