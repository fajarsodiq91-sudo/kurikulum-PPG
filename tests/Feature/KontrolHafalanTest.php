<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KontrolHafalanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kelompok_can_record_hafalan_score_for_a_generus_in_the_matching_class(): void
    {
        $context = $this->makeContext();

        $this->actingAs($context['user'])
            ->post('/kontrol/hafalan', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'mingguan',
                'material_id' => $context['material']->id,
                'scores' => [
                    $context['generus']->id => [
                        'score' => 92,
                        'status' => 'completed',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ujians', [
            'generus_id' => $context['generus']->id,
            'material_id' => $context['material']->id,
            'type' => 'mingguan',
            'title' => $context['material']->title,
            'score' => 92,
        ]);

        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $context['generus']->id,
            'academic_year_id' => $context['academicYear']->id,
            'semester_id' => $context['semester']->id,
            'final_score' => 92,
        ]);
    }

    public function test_bacaan_material_cannot_be_scored_through_the_hafalan_menu(): void
    {
        $context = $this->makeContext();

        $bacaanCategory = MaterialCategory::create([
            'class_grade_id' => $context['classGrade']->id,
            'semester_id' => $context['semester']->id,
            'name' => 'Bacaan',
            'code' => 'bacaan',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $bacaanChapter = MaterialChapter::create([
            'material_category_id' => $bacaanCategory->id,
            'name' => 'Iqro Jilid 1',
            'code' => 'iqro-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $bacaanMaterial = LearningMaterial::create([
            'material_chapter_id' => $bacaanChapter->id,
            'title' => 'Iqro Halaman 1-5',
            'code' => 'iqro-1-h1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($context['user'])
            ->post('/kontrol/hafalan', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'mingguan',
                'material_id' => $bacaanMaterial->id,
                'scores' => [
                    $context['generus']->id => [
                        'score' => 92,
                        'status' => 'completed',
                    ],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('ujians', ['material_id' => $bacaanMaterial->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeContext(): array
    {
        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Kontrol', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Kontrol', 'is_active' => true]);

        $classGrade = ClassGrade::create([
            'name' => 'Kelas 2',
            'code' => 'K2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

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

        $materialCategory = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Hafalan',
            'code' => 'hafalan',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $materialChapter = MaterialChapter::create([
            'material_category_id' => $materialCategory->id,
            'name' => 'Doa Harian',
            'code' => 'doa-harian',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $materialChapter->id,
            'title' => 'Doa Sebelum Tidur',
            'code' => 'doa-sebelum-tidur',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generus = Generus::create([
            'registration_number' => 'PPG-HAFALAN-001',
            'full_name' => 'Generus Hafalan',
            'status' => 'active',
            'learning_class_id' => $classGrade->id,
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'group_id' => $group->id,
            'assigned_at' => now(),
        ]);

        $role = Role::create(['name' => 'Pelaksana PPG Kelompok', 'slug' => 'pelaksana-ppg-kelompok-test', 'is_active' => true]);
        $permission = Permission::create(['name' => 'Manage Ujian', 'slug' => 'manage-ujian', 'module' => 'ujian', 'is_active' => true]);
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Pelaksana Kontrol',
            'email' => 'pelaksana-kontrol-hafalan@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, 'global', null);

        return [
            'region' => $region,
            'village' => $village,
            'group' => $group,
            'classGrade' => $classGrade,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'materialCategory' => $materialCategory,
            'materialChapter' => $materialChapter,
            'material' => $material,
            'generus' => $generus,
            'user' => $user,
        ];
    }
}
