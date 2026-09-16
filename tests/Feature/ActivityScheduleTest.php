<?php

namespace Tests\Feature;

use App\Models\OrganizationUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_activity_schedule(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Activity Schedules',
            'slug' => 'manage-activity-schedules',
            'module' => 'activity-schedules',
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

        $unit = OrganizationUnit::create([
            'name' => 'Bidang Kurikulum',
            'code' => 'KUR',
            'leader_name' => 'Bapak Ahmad',
            'unit_type' => 'bidang',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/activity-schedules', [
                'organization_unit_id' => $unit->id,
                'title' => 'Rapat Koordinasi Program PPG',
                'type' => 'meeting',
                'scheduled_at' => '2026-10-05',
                'end_at' => '2026-10-05',
                'location' => 'Ruang Kurikulum',
                'status' => 'planned',
                'notes' => 'Menyusun jadwal dan pembagian tugas koordinasi.',
            ])
            ->assertRedirect('/activity-schedules');

        $this->assertDatabaseHas('activity_schedules', [
            'organization_unit_id' => $unit->id,
            'title' => 'Rapat Koordinasi Program PPG',
            'type' => 'meeting',
            'status' => 'planned',
        ]);
    }
}
