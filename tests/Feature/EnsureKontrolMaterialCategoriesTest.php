<?php

namespace Tests\Feature;

use App\Http\Controllers\KontrolController;
use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\MaterialCategory;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureKontrolMaterialCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_category_per_active_class_grade_and_semester_without_duplicating(): void
    {
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
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $activeClassGrade = ClassGrade::create(['name' => 'Kelas 1', 'code' => 'K1', 'sort_order' => 1, 'is_active' => true]);
        ClassGrade::create(['name' => 'Kelas Nonaktif', 'code' => 'KX', 'sort_order' => 2, 'is_active' => false]);

        MaterialCategory::create([
            'class_grade_id' => $activeClassGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Sudah Ada',
            'code' => 'akhlaq',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->artisan('kontrol:ensure-categories')->assertSuccessful();

        $this->assertSame(
            count(KontrolController::CATEGORIES),
            MaterialCategory::where('class_grade_id', $activeClassGrade->id)->where('semester_id', $semester->id)->count(),
        );

        $this->assertSame(1, MaterialCategory::where('code', 'akhlaq')->where('name', 'Sudah Ada')->count());

        $this->assertDatabaseMissing('material_categories', ['class_grade_id' => null]);
        $this->assertSame(0, MaterialCategory::whereHas(
            'classGrade',
            fn ($query) => $query->where('is_active', false)
        )->count());

        $this->artisan('kontrol:ensure-categories')->assertSuccessful();

        $this->assertSame(
            count(KontrolController::CATEGORIES),
            MaterialCategory::where('class_grade_id', $activeClassGrade->id)->where('semester_id', $semester->id)->count(),
            'running the command twice must not duplicate categories',
        );
    }
}
