<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
