<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PersonalityItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonalityItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_personality_item(): void
    {
        $user = $this->userWithPermission('manage-personality-items');

        $this->actingAs($user)
            ->post('/personality-items', [
                'name' => 'Kejujuran',
                'code' => 'kejujuran',
                'description' => 'Konsistensi antara ucapan dan perbuatan.',
                'is_active' => true,
            ])
            ->assertRedirect('/personality-items');

        $this->assertDatabaseHas('personality_items', [
            'name' => 'Kejujuran',
            'code' => 'kejujuran',
        ]);
    }

    public function test_user_without_permission_cannot_create_personality_item(): void
    {
        $user = User::create([
            'name' => 'Pengguna Biasa',
            'email' => 'biasa@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/personality-items', [
                'name' => 'Tanpa Izin',
                'code' => 'tanpa-izin',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('personality_items', ['code' => 'tanpa-izin']);
    }

    public function test_user_with_view_only_permission_can_see_the_list_but_not_manage_it(): void
    {
        PersonalityItem::create([
            'name' => 'Kedisiplinan',
            'code' => 'kedisiplinan',
            'is_active' => true,
        ]);

        $user = $this->userWithPermission('view-personality-items');

        $this->actingAs($user)
            ->get('/personality-items')
            ->assertOk()
            ->assertSee('Kedisiplinan')
            ->assertDontSee('Tambah Item');

        $this->actingAs($user)
            ->post('/personality-items', [
                'name' => 'Tidak Boleh',
                'code' => 'tidak-boleh',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    private function userWithPermission(string $permissionSlug): User
    {
        $role = Role::create(['name' => 'Peran '.$permissionSlug, 'slug' => $permissionSlug.'-'.uniqid(), 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => $permissionSlug],
            ['name' => $permissionSlug, 'module' => 'personality-items', 'is_active' => true]
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
