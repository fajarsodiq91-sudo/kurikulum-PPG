<?php

namespace Tests\Feature;

use App\Models\ActivitySchedule;
use App\Models\OrganizationUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_activity_execution_and_outcome(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Activity Executions',
            'slug' => 'manage-activity-executions',
            'module' => 'activity-executions',
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

        $schedule = ActivitySchedule::create([
            'organization_unit_id' => $unit->id,
            'title' => 'Rapat Koordinasi Program PPG',
            'type' => 'meeting',
            'scheduled_at' => '2026-10-05 08:00:00',
            'status' => 'planned',
        ]);

        $this->actingAs($user)
            ->post('/activity-executions', [
                'activity_schedule_id' => $schedule->id,
                'actual_date' => '2026-10-05',
                'status' => 'completed',
                'attendance_count' => 12,
                'outcome' => 'Jadwal pembinaan semester disepakati.',
                'notes' => 'Berjalan sesuai agenda.',
            ])
            ->assertRedirect('/activity-executions');

        $this->assertDatabaseHas('activity_executions', [
            'activity_schedule_id' => $schedule->id,
            'status' => 'completed',
            'attendance_count' => 12,
            'outcome' => 'Jadwal pembinaan semester disepakati.',
        ]);
    }
}
