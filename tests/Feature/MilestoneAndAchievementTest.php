<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MilestoneAndAchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_milestone_and_achievement_record(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $milestonePermission = Permission::create([
            'name' => 'Manage Milestones',
            'slug' => 'manage-milestones',
            'module' => 'milestone',
            'is_active' => true,
        ]);

        $role->permissions()->attach($milestonePermission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'Ganjil',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generus = Generus::create([
            'registration_number' => 'GEN-MILESTONE-001',
            'full_name' => 'Generus Milestone',
            'gender' => 'Laki-laki',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/milestones', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Mencapai target keaktifan pembelajaran',
                'description' => 'Generus aktif hadir dan menyelesaikan tugas dengan baik.',
                'achievement_date' => '2026-09-20',
                'status' => 'achieved',
                'score' => 90,
            ])
            ->assertRedirect('/milestones');

        $this->assertDatabaseHas('milestones', [
            'generus_id' => $generus->id,
            'title' => 'Mencapai target keaktifan pembelajaran',
            'status' => 'achieved',
        ]);
    }
}
