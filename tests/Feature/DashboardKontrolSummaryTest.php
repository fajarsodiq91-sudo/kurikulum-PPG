<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardKontrolSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_average_score_per_kontrol_category_for_the_active_semester(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $permissions = [
            Permission::create(['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'module' => 'dashboard', 'is_active' => true]),
            Permission::create(['name' => 'Manage Ujian', 'slug' => 'manage-ujian', 'module' => 'ujian', 'is_active' => true]),
        ];
        $role->permissions()->attach(collect($permissions)->pluck('id'));

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin-dashboard-kontrol@ppg.test',
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

        $activeSemester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $inactiveSemester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Genap',
            'code' => 'GP',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $classGrade = ClassGrade::create(['name' => 'Kelas 2', 'code' => 'K2', 'sort_order' => 2, 'is_active' => true]);

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $activeSemester->id,
            'name' => 'Bacaan',
            'code' => 'bacaan',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Iqro Jilid 1',
            'code' => 'iqro-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Iqro Halaman 1-5',
            'code' => 'iqro-1-h1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generusA = Generus::create(['registration_number' => 'PPG-DASH-001', 'full_name' => 'Generus Satu', 'status' => 'active']);
        $generusB = Generus::create(['registration_number' => 'PPG-DASH-002', 'full_name' => 'Generus Dua', 'status' => 'active']);

        Ujian::create([
            'generus_id' => $generusA->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $activeSemester->id,
            'title' => $material->title,
            'material_id' => $material->id,
            'type' => 'harian',
            'score' => 80,
            'status' => 'completed',
        ]);

        Ujian::create([
            'generus_id' => $generusB->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $activeSemester->id,
            'title' => $material->title,
            'material_id' => $material->id,
            'type' => 'harian',
            'score' => 100,
            'status' => 'completed',
        ]);

        // Skor di semester tidak aktif tidak boleh ikut dirata-rata ke ringkasan dashboard.
        Ujian::create([
            'generus_id' => $generusA->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $inactiveSemester->id,
            'title' => $material->title,
            'material_id' => $material->id,
            'type' => 'harian',
            'score' => 10,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Kontrol Pencapaian Materi')
            ->assertSee('Bacaan')
            ->assertSee('90.0')
            ->assertSee('2 nilai tercatat');
    }
}
