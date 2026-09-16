<?php

namespace Tests\Feature;

use App\Models\OrganizationUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssignmentAndResponsibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_assignment_and_responsibility_record(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $assignmentPermission = Permission::create([
            'name' => 'Manage Assignments',
            'slug' => 'manage-assignments',
            'module' => 'assignments',
            'is_active' => true,
        ]);

        $role->permissions()->attach($assignmentPermission->id);

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

        $teacher = Teacher::create([
            'name' => 'Guru Pembina',
            'gender' => 'Laki-laki',
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/assignments', [
                'organization_unit_id' => $unit->id,
                'teacher_id' => $teacher->id,
                'assignment_title' => 'Koordinasi Materi Pembinaan',
                'assignment_type' => 'kurikulum',
                'start_date' => '2026-09-01',
                'end_date' => '2026-12-31',
                'status' => 'active',
                'responsibility' => 'Mengkoordinasikan materi dan monitoring pembelajaran.',
            ])
            ->assertRedirect('/assignments');

        $this->assertDatabaseHas('assignments', [
            'organization_unit_id' => $unit->id,
            'teacher_id' => $teacher->id,
            'assignment_title' => 'Koordinasi Materi Pembinaan',
            'assignment_type' => 'kurikulum',
        ]);
    }
}
