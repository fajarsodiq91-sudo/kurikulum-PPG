<?php

namespace Tests\Feature;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_users_permission_can_create_and_assign_roles(): void
    {
        $adminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Users',
            'slug' => 'manage-users',
            'module' => 'users',
            'is_active' => true,
        ]);

        $adminRole->permissions()->attach($permission->id);

        $staffRole = Role::create([
            'name' => 'Staff',
            'slug' => 'staff',
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $admin->assignRole($adminRole->id, 'global', null);

        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'Pengguna Baru',
                'email' => 'baru@ppg.test',
                'password' => 'password123',
                'status' => 'active',
                'roles' => [$staffRole->id],
            ])
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'name' => 'Pengguna Baru',
            'email' => 'baru@ppg.test',
            'status' => 'active',
        ]);

        $createdUser = User::query()->where('email', 'baru@ppg.test')->firstOrFail();

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $createdUser->id,
            'role_id' => $staffRole->id,
        ]);
    }

    public function test_roles_can_be_assigned_with_village_or_group_scope_and_scope_is_kept_on_edit(): void
    {
        $adminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $adminRole->permissions()->attach(Permission::create(['name' => 'Manage Users', 'slug' => 'manage-users', 'module' => 'users', 'is_active' => true])->id);
        $villageRole = Role::create(['name' => 'Perwakilan Desa', 'slug' => 'perwakilan-desa', 'is_active' => true]);
        $groupRole = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok', 'is_active' => true]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $admin->assignRole($adminRole->id);

        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa A', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 1', 'is_active' => true]);
        $base = ['name' => 'Pengguna Wilayah', 'email' => 'wilayah@ppg.test', 'password' => 'password123', 'status' => 'active'];

        $this->actingAs($admin)->post('/users', [...$base, 'roles' => [$villageRole->id], 'role_scopes' => [$villageRole->id => ['type' => 'village', 'id' => $village->id]]])->assertRedirect('/users');

        $user = User::where('email', 'wilayah@ppg.test')->firstOrFail();
        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $villageRole->id, 'scope_type' => 'village', 'scope_id' => $village->id]);

        $this->actingAs($admin)->put("/users/{$user->id}", [
            ...$base,
            'password' => '',
            'roles' => [$villageRole->id, $groupRole->id],
            'role_scopes' => [$groupRole->id => ['type' => 'group', 'id' => $group->id]],
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $villageRole->id, 'scope_type' => 'village', 'scope_id' => $village->id]);
        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $groupRole->id, 'scope_type' => 'group', 'scope_id' => $group->id]);

        $this->actingAs($admin)
            ->put("/users/{$user->id}", [...$base, 'password' => '', 'roles' => [$groupRole->id], 'role_scopes' => [$groupRole->id => ['type' => 'group', 'id' => 99999]]])
            ->assertSessionHasErrors('role_scopes');
    }

    public function test_user_form_offers_scope_selection(): void
    {
        $adminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $adminRole->permissions()->attach(Permission::create(['name' => 'Manage Users', 'slug' => 'manage-users', 'module' => 'users', 'is_active' => true])->id);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $admin->assignRole($adminRole->id);
        Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Pilihan', 'is_active' => true]);

        $this->actingAs($admin)->get('/users')->assertOk()->assertSee('Satu desa')->assertSee('Satu kelompok')->assertSee('Desa Pilihan');
    }

    public function test_assigning_a_fixed_scope_role_requires_a_matching_dapukan(): void
    {
        $adminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $adminRole->permissions()->attach(Permission::create(['name' => 'Manage Users', 'slug' => 'manage-users', 'module' => 'users', 'is_active' => true])->id);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $admin->assignRole($adminRole->id);

        $groupRole = Role::create(['name' => 'Pelaksana PPG Kelompok', 'slug' => 'pelaksana-ppg-kelompok', 'is_active' => true]);
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Dapukan', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Dapukan', 'is_active' => true]);
        $dapukan = Dapukan::create(['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => null, 'name' => 'Unsur Pembina Generus', 'sort_order' => 0, 'can_approve' => false, 'is_active' => true]);

        $this->actingAs($admin)->get('/users')->assertOk()->assertSee('Unsur Pembina Generus');

        $base = ['name' => 'Pelaksana Baru', 'email' => 'pelaksana-baru@ppg.test', 'password' => 'password123', 'status' => 'active'];

        $this->actingAs($admin)
            ->post('/users', [...$base, 'roles' => [$groupRole->id], 'role_scopes' => [$groupRole->id => ['type' => 'group', 'id' => $group->id]]])
            ->assertSessionHasErrors('role_dapukan');
        $this->assertDatabaseMissing('users', ['email' => 'pelaksana-baru@ppg.test']);

        $this->actingAs($admin)
            ->post('/users', [
                ...$base,
                'roles' => [$groupRole->id],
                'role_scopes' => [$groupRole->id => ['type' => 'group', 'id' => $group->id]],
                'role_dapukan' => [$groupRole->id => $dapukan->id],
            ])
            ->assertRedirect('/users');

        $user = User::where('email', 'pelaksana-baru@ppg.test')->firstOrFail();
        $this->assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role_id' => $groupRole->id, 'dapukan_id' => $dapukan->id]);
    }

    public function test_user_without_manage_users_permission_cannot_access_user_management(): void
    {
        $role = Role::create([
            'name' => 'Guru',
            'slug' => 'guru',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $this->actingAs($user)
            ->get('/users')
            ->assertForbidden();
    }
}
