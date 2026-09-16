<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CurriculumAndMaterialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_curriculum_program_and_material(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $programPermission = Permission::create([
            'name' => 'Manage Curriculum',
            'slug' => 'manage-curriculum',
            'module' => 'curriculum',
            'is_active' => true,
        ]);

        $materialPermission = Permission::create([
            'name' => 'Manage Learning Materials',
            'slug' => 'manage-learning-materials',
            'module' => 'learning-materials',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$programPermission->id, $materialPermission->id]);

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

        $level = Level::create([
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/curriculum-programs', [
                'name' => 'Pendidikan Karakter',
                'code' => 'PK-01',
                'description' => 'Program pembinaan karakter generus',
                'is_active' => true,
            ])
            ->assertRedirect('/curriculum-programs');

        $this->assertDatabaseHas('curriculum_programs', [
            'name' => 'Pendidikan Karakter',
            'code' => 'PK-01',
        ]);

        $this->actingAs($user)
            ->post('/learning-materials', [
                'curriculum_program_id' => 1,
                'title' => 'Modul Etika dan Budaya',
                'code' => 'MAT-001',
                'level_id' => $level->id,
                'semester_id' => $semester->id,
                'academic_year_id' => $academicYear->id,
                'description' => 'Materi pembinaan karakter',
                'is_active' => true,
            ])
            ->assertRedirect('/learning-materials');

        $this->assertDatabaseHas('learning_materials', [
            'title' => 'Modul Etika dan Budaya',
            'code' => 'MAT-001',
        ]);
    }
}
