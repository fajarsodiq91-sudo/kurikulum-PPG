<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_manage_users_permission_can_create_role_with_permissions(): void
    {
        $adminRole = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $manageUsersPermission = Permission::create([
            'name' => 'Manage Users',
            'slug' => 'manage-users',
            'module' => 'users',
            'is_active' => true,
        ]);

        $adminRole->permissions()->attach($manageUsersPermission->id);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
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
            ->post('/roles', [
                'name' => 'Koordinator',
                'slug' => 'koordinator',
                'description' => 'Koordinator program',
                'is_active' => true,
                'permissions' => [$permission->id],
            ])
            ->assertRedirect('/roles');

        $this->assertDatabaseHas('roles', [
            'name' => 'Koordinator',
            'slug' => 'koordinator',
            'is_active' => true,
        ]);

        $role = Role::query()->where('slug', 'koordinator')->firstOrFail();

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_user_without_manage_users_permission_cannot_access_role_management(): void
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
            ->get('/roles')
            ->assertForbidden();
    }
}
