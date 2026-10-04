<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Level;
use App\Models\MaterialCategory;
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

    public function test_admin_can_create_material_category_chapter_and_material(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $materialPermission = Permission::create([
            'name' => 'Manage Learning Materials',
            'slug' => 'manage-learning-materials',
            'module' => 'learning-materials',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$materialPermission->id]);

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
            'name' => 'Jenjang Dasar',
            'code' => 'JD',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $classGrade = ClassGrade::create([
            'level_id' => $level->id,
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
            ->post('/material-categories', [
                'class_grade_id' => $classGrade->id,
                'semester_id' => $semester->id,
                'name' => 'Akhlak',
                'code' => 'AKHLAK',
                'is_active' => true,
            ])
            ->assertRedirect('/material-categories');

        $this->assertDatabaseHas('material_categories', [
            'name' => 'Akhlak',
            'code' => 'AKHLAK',
        ]);

        $category = MaterialCategory::query()->where('code', 'AKHLAK')->firstOrFail();

        $this->actingAs($user)
            ->post('/material-chapters', [
                'material_category_id' => $category->id,
                'name' => 'Bab Etika dan Budaya',
                'code' => 'BAB-01',
                'is_active' => true,
            ])
            ->assertRedirect('/material-chapters');

        $this->assertDatabaseHas('material_chapters', [
            'name' => 'Bab Etika dan Budaya',
            'code' => 'BAB-01',
        ]);

        $chapter = $category->materialChapters()->where('code', 'BAB-01')->firstOrFail();

        $this->actingAs($user)
            ->post('/learning-materials', [
                'material_chapter_id' => $chapter->id,
                'title' => 'Modul Etika dan Budaya',
                'code' => 'MAT-001',
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
