<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_region_from_master_data_page(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Master Data',
            'slug' => 'view-master-data',
            'module' => 'master-data',
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
            ->post('/master-data/regions', [
                'name' => 'Karawang Timur',
                'code' => 'KRT',
                'is_active' => true,
            ])
            ->assertRedirect('/master-data/regions');

        $this->assertDatabaseHas('regions', [
            'name' => 'Karawang Timur',
            'code' => 'KRT',
        ]);
    }
}
