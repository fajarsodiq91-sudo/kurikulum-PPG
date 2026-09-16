<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_organization_unit(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Organization Units',
            'slug' => 'manage-organization-units',
            'module' => 'organization-units',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $this->actingAs($user)
            ->post('/organization-units', [
                'name' => 'Bidang Kurikulum',
                'code' => 'KUR',
                'leader_name' => 'Bapak Ahmad',
                'unit_type' => 'bidang',
                'status' => 'active',
                'description' => 'Koordinasi program dan pembinaan kurikulum.',
            ])
            ->assertRedirect('/organization-units');

        $this->assertDatabaseHas('organization_units', [
            'name' => 'Bidang Kurikulum',
            'code' => 'KUR',
            'unit_type' => 'bidang',
        ]);
    }
}
