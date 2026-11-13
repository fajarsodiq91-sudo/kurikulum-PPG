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
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KontrolBacaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kelompok_can_record_bacaan_score_for_a_generus_in_the_matching_class(): void
    {
        $context = $this->makeContext();

        $this->actingAs($context['user'])
            ->post('/kontrol/bacaan', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'harian',
                'material_id' => [$context['material']->id],
                'scores' => [
                    $context['material']->id => [
                        $context['generus']->id => [
                            'score' => 88,
                            'status' => 'completed',
                        ],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ujians', [
            'generus_id' => $context['generus']->id,
            'material_id' => $context['material']->id,
            'type' => 'harian',
            'title' => $context['material']->title,
            'score' => 88,
        ]);

        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $context['generus']->id,
            'academic_year_id' => $context['academicYear']->id,
            'semester_id' => $context['semester']->id,
            'final_score' => 88,
        ]);
    }

    public function test_the_only_manageable_group_and_taught_class_grade_are_preselected(): void
    {
        $context = $this->makeContext();

        $teacher = Teacher::create([
            'registration_number' => '260199001',
            'name' => 'Guru Kontrol',
            'gender' => 'male',
            'status' => 'active',
            'group_id' => $context['group']->id,
        ]);
        $teacher->classGrades()->attach($context['classGrade']->id);
        $context['user']->forceFill(['teacher_id' => $teacher->id])->save();

        $response = $this->actingAs($context['user'])->get('/kontrol/bacaan');

        $response->assertOk();
        $this->assertSame($context['group']->id, $response->viewData('group')?->id);
        $this->assertSame($context['classGrade']->id, $response->viewData('classGrade')?->id);
    }

    public function test_village_dropdown_lets_a_daerah_user_narrow_the_group_options(): void
    {
        $context = $this->makeContext();

        $otherVillage = Village::create(['region_id' => $context['region']->id, 'name' => 'Desa Lain', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $otherVillage->id, 'name' => 'Kelompok Lain', 'is_active' => true]);

        $daerahRole = Role::create(['name' => 'Pengurus PPG Daerah Test', 'slug' => 'ppg-daerah-test', 'is_active' => true]);
        $manageUjian = Permission::where('slug', 'manage-ujian')->first();
        $daerahRole->permissions()->attach($manageUjian->id);

        $daerahUser = User::create([
            'name' => 'Pengurus Daerah',
            'email' => 'daerah-kontrol@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $daerahUser->assignRole($daerahRole->id, 'global', null);

        $response = $this->actingAs($daerahUser)->get('/kontrol/bacaan');

        $response->assertOk();
        $villages = $response->viewData('villages');
        $groups = $response->viewData('groups');

        $this->assertTrue($villages->contains('id', $context['village']->id));
        $this->assertTrue($villages->contains('id', $otherVillage->id));
        $this->assertTrue($groups->contains('id', $context['group']->id));
        $this->assertTrue($groups->contains('id', $otherGroup->id));
        $this->assertNull($response->viewData('group'));
    }

    public function test_daerah_user_can_view_and_score_roster_across_all_groups_without_picking_one(): void
    {
        $context = $this->makeContext();

        $otherGroup = Group::create(['village_id' => $context['village']->id, 'name' => 'Kelompok Lain', 'is_active' => true]);

        $otherGenerus = Generus::create([
            'registration_number' => 'PPG-KONTROL-003',
            'full_name' => 'Generus Kelompok Lain',
            'status' => 'active',
            'learning_class_id' => $context['classGrade']->id,
        ]);

        GenerusAssignment::create([
            'generus_id' => $otherGenerus->id,
            'group_id' => $otherGroup->id,
            'assigned_at' => now(),
        ]);

        $daerahRole = Role::create(['name' => 'Pengurus PPG Daerah Test', 'slug' => 'ppg-daerah-test', 'is_active' => true]);
        $manageUjian = Permission::where('slug', 'manage-ujian')->first();
        $daerahRole->permissions()->attach($manageUjian->id);

        $daerahUser = User::create([
            'name' => 'Pengurus Daerah',
            'email' => 'daerah-kontrol-2@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $daerahUser->assignRole($daerahRole->id, 'global', null);

        $response = $this->actingAs($daerahUser)->get('/kontrol/bacaan?'.http_build_query([
            'class_grade_id' => $context['classGrade']->id,
            'academic_year_id' => $context['academicYear']->id,
            'semester_id' => $context['semester']->id,
            'material_id' => $context['material']->id,
        ]));

        $response->assertOk();
        $this->assertNull($response->viewData('group'));
        $rosterIds = $response->viewData('roster')->pluck('id');
        $this->assertTrue($rosterIds->contains($context['generus']->id));
        $this->assertTrue($rosterIds->contains($otherGenerus->id));

        $this->actingAs($daerahUser)
            ->post('/kontrol/bacaan', [
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'harian',
                'material_id' => [$context['material']->id],
                'scores' => [
                    $context['material']->id => [
                        $context['generus']->id => ['score' => 80, 'status' => 'completed'],
                        $otherGenerus->id => ['score' => 90, 'status' => 'completed'],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ujians', ['generus_id' => $context['generus']->id, 'score' => 80]);
        $this->assertDatabaseHas('ujians', ['generus_id' => $otherGenerus->id, 'score' => 90]);
    }

    public function test_scores_for_more_than_one_material_can_be_saved_in_one_submission(): void
    {
        $context = $this->makeContext();

        $secondMaterial = LearningMaterial::create([
            'material_chapter_id' => $context['materialChapter']->id,
            'title' => 'Iqro Halaman 6-10',
            'code' => 'iqro-1-h2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($context['user'])->get('/kontrol/bacaan?'.http_build_query([
            'group_id' => $context['group']->id,
            'class_grade_id' => $context['classGrade']->id,
            'academic_year_id' => $context['academicYear']->id,
            'semester_id' => $context['semester']->id,
            'material_id' => [$context['material']->id, $secondMaterial->id],
        ]));

        $response->assertOk();
        $this->assertSame(
            [$context['material']->id, $secondMaterial->id],
            $response->viewData('selectedMaterials')->pluck('id')->all()
        );

        $this->actingAs($context['user'])
            ->post('/kontrol/bacaan', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'harian',
                'material_id' => [$context['material']->id, $secondMaterial->id],
                'scores' => [
                    $context['material']->id => [
                        $context['generus']->id => ['score' => 88, 'status' => 'completed'],
                    ],
                    $secondMaterial->id => [
                        $context['generus']->id => ['score' => 91, 'status' => 'completed'],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ujians', ['generus_id' => $context['generus']->id, 'material_id' => $context['material']->id, 'score' => 88]);
        $this->assertDatabaseHas('ujians', ['generus_id' => $context['generus']->id, 'material_id' => $secondMaterial->id, 'score' => 91]);
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
            'registration_number' => 'PPG-KONTROL-002',
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
            ->post('/kontrol/bacaan', [
                'group_id' => $context['group']->id,
                'class_grade_id' => $context['classGrade']->id,
                'academic_year_id' => $context['academicYear']->id,
                'semester_id' => $context['semester']->id,
                'type' => 'harian',
                'material_id' => [$context['material']->id],
                'scores' => [
                    $context['material']->id => [
                        $outsider->id => [
                            'score' => 70,
                            'status' => 'completed',
                        ],
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
            'name' => 'Bacaan',
            'code' => 'bacaan',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $materialChapter = MaterialChapter::create([
            'material_category_id' => $materialCategory->id,
            'name' => 'Iqro Jilid 1',
            'code' => 'iqro-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $materialChapter->id,
            'title' => 'Iqro Halaman 1-5',
            'code' => 'iqro-1-h1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generus = Generus::create([
            'registration_number' => 'PPG-KONTROL-001',
            'full_name' => 'Generus Kontrol',
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
            'email' => 'pelaksana-kontrol@ppg.test',
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
