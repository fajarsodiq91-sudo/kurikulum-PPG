<?php

namespace Tests\Feature;

use App\Models\DownloadFile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DownloadFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_download_file(): void
    {
        $user = $this->userWithPermission('manage-download-files');

        $this->actingAs($user)
            ->post('/download-files', [
                'title' => 'Pedoman Kurikulum',
                'file_url' => 'https://example.com/pedoman.pdf',
                'description' => 'Pedoman pelaksanaan kurikulum.',
                'is_active' => true,
            ])
            ->assertRedirect('/download-files');

        $this->assertDatabaseHas('download_files', [
            'title' => 'Pedoman Kurikulum',
            'file_url' => 'https://example.com/pedoman.pdf',
        ]);
    }

    public function test_user_without_permission_cannot_create_download_file(): void
    {
        $user = User::create([
            'name' => 'Pengguna Biasa',
            'email' => 'biasa@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/download-files', [
                'title' => 'Tanpa Izin',
                'file_url' => 'https://example.com/tanpa-izin.pdf',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('download_files', ['title' => 'Tanpa Izin']);
    }

    public function test_user_with_view_only_permission_can_see_the_list_but_not_manage_it(): void
    {
        DownloadFile::create([
            'title' => 'Jadwal KBM',
            'file_url' => 'https://example.com/jadwal.pdf',
            'is_active' => true,
        ]);

        $user = $this->userWithPermission('view-download-files');

        $this->actingAs($user)
            ->get('/download-files')
            ->assertOk()
            ->assertSee('Jadwal KBM')
            ->assertDontSee('Tambah File');

        $this->actingAs($user)
            ->post('/download-files', [
                'title' => 'Tidak Boleh',
                'file_url' => 'https://example.com/tidak-boleh.pdf',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_guest_without_logging_in_can_see_the_list_and_download_link(): void
    {
        $guestRole = Role::create(['name' => 'Tamu', 'slug' => 'guest', 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'view-download-files'],
            ['name' => 'View Download Files', 'module' => 'download-files', 'is_active' => true]
        );
        $guestRole->permissions()->attach($permission->id);

        DownloadFile::create([
            'title' => 'Jadwal KBM',
            'file_url' => 'https://example.com/jadwal.pdf',
            'is_active' => true,
        ]);

        $this->get('/download-files')
            ->assertOk()
            ->assertSee('Jadwal KBM')
            ->assertSee('https://example.com/jadwal.pdf');
    }

    private function userWithPermission(string $permissionSlug): User
    {
        $role = Role::create(['name' => 'Peran '.$permissionSlug, 'slug' => $permissionSlug.'-'.uniqid(), 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => $permissionSlug],
            ['name' => $permissionSlug, 'module' => 'download-files', 'is_active' => true]
        );
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Pengguna '.$permissionSlug,
            'email' => $permissionSlug.'-'.uniqid().'@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }
}
