<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\MunaqosahAccelerationRequest;
use App\Models\MunaqosahSchedule;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Models\Village;
use App\Support\GradePromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunaqosahPromotionTest extends TestCase
{
    use RefreshDatabase;

    private Region $region;

    private Village $village;

    private Group $group;

    private AcademicYear $academicYear;

    private Semester $semesterTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $this->village = Village::create(['region_id' => $this->region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $this->group = Group::create(['village_id' => $this->village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);

        $this->academicYear = AcademicYear::create(['name' => '2025/2026', 'code' => 'TA', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
        Semester::create(['academic_year_id' => $this->academicYear->id, 'name' => 'S1', 'code' => 'S1', 'sort_order' => 1, 'is_active' => true]);
        $this->semesterTwo = Semester::create(['academic_year_id' => $this->academicYear->id, 'name' => 'S2', 'code' => 'S2', 'sort_order' => 2, 'is_active' => true]);
    }

    private function kelompokUser(): User
    {
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-ppg-kelompok-'.$this->group->id, 'is_active' => true]);
        foreach (['manage-munaqosah', 'propose-munaqosah-acceleration'] as $slug) {
            $role->permissions()->attach(Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true])->id);
        }
        $user = User::create(['name' => 'Pelaksana', 'email' => 'pelaksana@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $this->group->id);

        return $user;
    }

    private function desaUser(Village $village): User
    {
        $role = Role::create(['name' => 'Perwakilan Desa', 'slug' => 'perwakilan-ppg-desa-'.$village->id, 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'approve-munaqosah-acceleration'], ['name' => 'x', 'module' => 'x', 'is_active' => true])->id);
        $user = User::create(['name' => 'Desa', 'email' => 'desa-'.$village->id.'@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'village', $village->id);

        return $user;
    }

    private function placedGenerus(string $name, ?ClassGrade $classGrade = null): Generus
    {
        $generus = Generus::create([
            'registration_number' => fake()->unique()->numerify('2609####'),
            'full_name' => $name,
            'status' => 'active',
            'learning_class_id' => $classGrade?->id,
        ]);
        GenerusAssignment::create(['generus_id' => $generus->id, 'region_id' => $this->region->id, 'village_id' => $this->village->id, 'group_id' => $this->group->id]);

        return $generus;
    }

    public function test_grade_promotion_service_returns_the_next_class_grade_in_curriculum_order(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $nextLevel = Level::create(['name' => 'Pra Remaja', 'code' => 'PR', 'sort_order' => 2, 'is_active' => true]);

        $classOne = ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        $classTwo = ClassGrade::create(['level_id' => $level->id, 'name' => '2', 'code' => 'K2', 'sort_order' => 1, 'is_active' => true]);
        $classSeven = ClassGrade::create(['level_id' => $nextLevel->id, 'name' => '7', 'code' => 'K7', 'sort_order' => 0, 'is_active' => true]);

        $this->assertSame($classTwo->id, GradePromotionService::nextClassGradeFor($classOne)->id);
        // Last class grade in the level sequence promotes into the next level's first class.
        $this->assertSame($classSeven->id, GradePromotionService::nextClassGradeFor($classTwo)->id);
        // Nothing beyond the final class grade of the whole curriculum.
        $this->assertNull(GradePromotionService::nextClassGradeFor($classSeven));
    }

    public function test_semester_final_munaqosah_is_rejected_until_ppg_daerah_opens_the_schedule(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        $generus = $this->placedGenerus('Generus Satu', $classGrade);
        $user = $this->kelompokUser();

        $this->actingAs($user)
            ->post('/munaqosahs/batch', [
                'group_id' => $this->group->id,
                'academic_year_id' => $this->academicYear->id,
                'semester_id' => $this->semesterTwo->id,
                'type' => 'semester-final',
                'title' => 'Munaqosah Semester Genap',
                'scores' => [$generus->id => ['score' => 90, 'decision' => 'naik', 'status' => 'completed']],
            ]);

        $this->assertDatabaseMissing('munaqosahs', ['generus_id' => $generus->id]);

        MunaqosahSchedule::create([
            'class_grade_id' => $classGrade->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semesterTwo->id,
            'type' => 'semester-final',
            'is_open' => true,
        ]);

        $this->actingAs($user)
            ->post('/munaqosahs/batch', [
                'group_id' => $this->group->id,
                'academic_year_id' => $this->academicYear->id,
                'semester_id' => $this->semesterTwo->id,
                'type' => 'semester-final',
                'title' => 'Munaqosah Semester Genap',
                'scores' => [$generus->id => ['score' => 90, 'decision' => 'naik', 'status' => 'completed']],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('munaqosahs', ['generus_id' => $generus->id, 'decision' => 'naik']);
    }

    public function test_naik_decision_at_semester_two_promotes_the_generus_to_the_next_class_grade(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classOne = ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        $classTwo = ClassGrade::create(['level_id' => $level->id, 'name' => '2', 'code' => 'K2', 'sort_order' => 1, 'is_active' => true]);
        $generus = $this->placedGenerus('Generus Naik', $classOne);

        MunaqosahSchedule::create([
            'class_grade_id' => $classOne->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semesterTwo->id,
            'type' => 'semester-final',
            'is_open' => true,
        ]);

        $user = $this->kelompokUser();

        $this->actingAs($user)->post('/munaqosahs/batch', [
            'group_id' => $this->group->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semesterTwo->id,
            'type' => 'semester-final',
            'title' => 'Munaqosah Semester Genap',
            'scores' => [$generus->id => ['score' => 90, 'decision' => 'naik', 'status' => 'completed']],
        ])->assertRedirect();

        $this->assertSame($classTwo->id, $generus->fresh()->learning_class_id);
    }

    public function test_tinggal_decision_does_not_change_the_class_grade(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classOne = ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        ClassGrade::create(['level_id' => $level->id, 'name' => '2', 'code' => 'K2', 'sort_order' => 1, 'is_active' => true]);
        $generus = $this->placedGenerus('Generus Tinggal', $classOne);

        MunaqosahSchedule::create([
            'class_grade_id' => $classOne->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semesterTwo->id,
            'type' => 'semester-final',
            'is_open' => true,
        ]);

        $user = $this->kelompokUser();

        $this->actingAs($user)->post('/munaqosahs/batch', [
            'group_id' => $this->group->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semesterTwo->id,
            'type' => 'semester-final',
            'title' => 'Munaqosah Semester Genap',
            'scores' => [$generus->id => ['score' => 40, 'decision' => 'tinggal', 'status' => 'completed']],
        ])->assertRedirect();

        $this->assertSame($classOne->id, $generus->fresh()->learning_class_id);
    }

    public function test_acceleration_request_is_proposed_by_kelompok_and_approved_by_matching_desa_only(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classOne = ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        ClassGrade::create(['level_id' => $level->id, 'name' => '2', 'code' => 'K2', 'sort_order' => 1, 'is_active' => true]);
        $generus = $this->placedGenerus('Generus Akselerasi', $classOne);

        $kelompokUser = $this->kelompokUser();

        $this->actingAs($kelompokUser)
            ->post('/munaqosah-acceleration-requests', ['generus_id' => $generus->id, 'reason' => 'Sudah sangat menguasai materi.'])
            ->assertRedirect();

        $request = MunaqosahAccelerationRequest::firstOrFail();
        $this->assertSame('pending', $request->status);

        $otherVillage = Village::create(['region_id' => $this->region->id, 'name' => 'Desa Lain', 'code' => 'DL', 'slug' => 'desa-lain', 'is_active' => true]);
        $otherDesaUser = $this->desaUser($otherVillage);

        // A desa representative from a different village may not approve this request.
        $this->actingAs($otherDesaUser)->post("/munaqosah-acceleration-requests/{$request->id}/approve")->assertForbidden();

        $desaUser = $this->desaUser($this->village);

        $this->actingAs($desaUser)
            ->post("/munaqosah-acceleration-requests/{$request->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);

        // Semester 1 on purpose: akselerasi may promote outside the semester-2 cycle.
        $semesterOne = Semester::where('academic_year_id', $this->academicYear->id)->where('code', 'S1')->firstOrFail();

        $this->actingAs($kelompokUser)
            ->post("/munaqosah-acceleration-requests/{$request->id}/result", [
                'title' => 'Munaqosah Akselerasi',
                'academic_year_id' => $this->academicYear->id,
                'semester_id' => $semesterOne->id,
                'score' => 95,
                'decision' => 'naik',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('munaqosahs', ['generus_id' => $generus->id, 'type' => 'akselerasi', 'decision' => 'naik']);
        $this->assertNotSame($classOne->id, $generus->fresh()->learning_class_id);
    }
}
