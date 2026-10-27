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

class KontrolKemandirianTest extends TestCase
{
    use RefreshDatabase;

    public function test_kelompok_can_record_kemandirian_score_for_a_generus_in_the_matching_class(): void
    {
        $context = $this->makeContext();

        $this->actingAs($context['user'])
            ->post('/kontrol/kemandirian', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'bulanan',
                'material_id' => $context['material']->id,
                'scores' => [
                    $context['generus']->id => [
                        'score' => 87,
                        'status' => 'completed',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ujians', [
            'generus_id' => $context['generus']->id,
            'material_id' => $context['material']->id,
            'type' => 'bulanan',
            'title' => $context['material']->title,
            'score' => 87,
        ]);

        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $context['generus']->id,
            'academic_year_id' => $context['academicYear']->id,
            'semester_id' => $context['semester']->id,
            'final_score' => 87,
        ]);
    }

    public function test_generus_from_a_different_class_grade_cannot_be_scored(): void
    {
        $context = $this->makeContext();

        $otherClassGrade = ClassGrade::create([
            'name' => 'Kelas 3',
            'code' => 'K3',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $outsider = Generus::create([
            'registration_number' => 'PPG-MANDIRI-002',
            'full_name' => 'Generus Kelas Lain',
            'status' => 'active',
            'learning_class_id' => $otherClassGrade->id,
        ]);

        GenerusAssignment::create([
            'generus_id' => $outsider->id,
            'group_id' => $context['group']->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($context['user'])
            ->post('/kontrol/kemandirian', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'bulanan',
                'material_id' => $context['material']->id,
                'scores' => [
                    $outsider->id => [
                        'score' => 70,
                        'status' => 'completed',
                    ],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('ujians', ['generus_id' => $outsider->id]);
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
            'name' => 'Kemandirian',
            'code' => 'kemandirian',
            'sort_order' => 6,
            'is_active' => true,
        ]);

        $materialChapter = MaterialChapter::create([
            'material_category_id' => $materialCategory->id,
            'name' => 'Kemandirian Pribadi',
            'code' => 'mandiri-pribadi',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $materialChapter->id,
            'title' => 'Merapikan Tempat Tidur Sendiri',
            'code' => 'mandiri-pribadi-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generus = Generus::create([
            'registration_number' => 'PPG-MANDIRI-001',
            'full_name' => 'Generus Kemandirian',
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
            'email' => 'pelaksana-kontrol-kemandirian@ppg.test',
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
