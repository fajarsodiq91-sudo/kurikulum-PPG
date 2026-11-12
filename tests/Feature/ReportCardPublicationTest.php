<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\ReportCardGroupConfirmation;
use App\Models\ReportCardPublication;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportCardPublicationTest extends TestCase
{
    use RefreshDatabase;

    private Region $region;

    private Village $village;

    private Group $group;

    private AcademicYear $academicYear;

    private Semester $semesterOne;

    private Semester $semesterTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $this->village = Village::create(['region_id' => $this->region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $this->group = Group::create(['village_id' => $this->village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);

        $this->academicYear = AcademicYear::create(['name' => '2025/2026', 'code' => 'TA', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
        $this->semesterOne = Semester::create(['academic_year_id' => $this->academicYear->id, 'name' => 'S1', 'code' => 'S1', 'sort_order' => 1, 'is_active' => true]);
        $this->semesterTwo = Semester::create(['academic_year_id' => $this->academicYear->id, 'name' => 'S2', 'code' => 'S2', 'sort_order' => 2, 'is_active' => true]);
    }

    private function ppgUser(): User
    {
        $role = Role::create(['name' => 'PPG Daerah', 'slug' => 'ppg', 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'manage-report-card-publishing'], ['name' => 'x', 'module' => 'x', 'is_active' => true])->id);
        $user = User::create(['name' => 'PPG', 'email' => 'ppg@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function kelompokUser(): User
    {
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-ppg-kelompok-'.$this->group->id, 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'confirm-report-card-publishing'], ['name' => 'x', 'module' => 'x', 'is_active' => true])->id);
        $user = User::create(['name' => 'Pelaksana', 'email' => 'pelaksana@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $this->group->id);

        return $user;
    }

    private function placedGenerus(string $name, ClassGrade $classGrade): Generus
    {
        $generus = Generus::create([
            'registration_number' => fake()->unique()->numerify('2609####'),
            'full_name' => $name,
            'status' => 'active',
            'learning_class_id' => $classGrade->id,
        ]);
        GenerusAssignment::create(['generus_id' => $generus->id, 'region_id' => $this->region->id, 'village_id' => $this->village->id, 'group_id' => $this->group->id]);

        return $generus;
    }

    public function test_semester_one_publication_does_not_need_a_group_confirmation_and_publishes_immediately(): void
    {
        $ppg = $this->ppgUser();

        $this->actingAs($ppg)
            ->post('/report-card-publications', [
                'academic_year_id' => $this->academicYear->id,
                'semester_id' => $this->semesterOne->id,
            ])
            ->assertRedirect();

        $publication = ReportCardPublication::firstOrFail();
        $this->assertTrue($publication->isPublished());
        $this->assertFalse($publication->requires_group_confirmation);
        $this->assertDatabaseCount('report_card_group_confirmations', 0);
    }

    public function test_semester_two_publication_waits_for_every_groups_confirmation_before_publishing(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classOne = ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        $classTwo = ClassGrade::create(['level_id' => $level->id, 'name' => '2', 'code' => 'K2', 'sort_order' => 1, 'is_active' => true]);

        $naik = $this->placedGenerus('Generus Naik', $classOne);
        $tinggal = $this->placedGenerus('Generus Tinggal', $classOne);

        $ppg = $this->ppgUser();
        $kelompok = $this->kelompokUser();

        $this->actingAs($ppg)
            ->post('/report-card-publications', [
                'academic_year_id' => $this->academicYear->id,
                'semester_id' => $this->semesterTwo->id,
            ])
            ->assertRedirect();

        $publication = ReportCardPublication::firstOrFail();
        $this->assertFalse($publication->isPublished());
        $this->assertTrue($publication->requires_group_confirmation);

        $confirmation = ReportCardGroupConfirmation::where('group_id', $this->group->id)->firstOrFail();

        $this->actingAs($kelompok)->get("/report-card-group-confirmations/{$confirmation->id}/edit")
            ->assertOk()->assertSee('Generus Naik')->assertSee('Generus Tinggal');

        // Only $tinggal is checked; everyone else (including $naik) defaults to naik kelas.
        $this->actingAs($kelompok)
            ->put("/report-card-group-confirmations/{$confirmation->id}", [
                'retained_generus_ids' => [$tinggal->id],
            ])
            ->assertRedirect();

        $this->assertSame($classTwo->id, $naik->fresh()->learning_class_id);
        $this->assertSame($classOne->id, $tinggal->fresh()->learning_class_id);

        $this->assertTrue($confirmation->fresh()->status === 'confirmed');
        $this->assertTrue($publication->fresh()->isPublished());
    }

    public function test_a_kelompok_cannot_confirm_another_groups_confirmation(): void
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        ClassGrade::create(['level_id' => $level->id, 'name' => '1', 'code' => 'K1', 'sort_order' => 0, 'is_active' => true]);
        $classOne = ClassGrade::first();
        $this->placedGenerus('Generus Satu', $classOne);

        $ppg = $this->ppgUser();

        $this->actingAs($ppg)->post('/report-card-publications', [
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semesterTwo->id,
        ]);

        $confirmation = ReportCardGroupConfirmation::firstOrFail();

        $otherGroup = Group::create(['village_id' => $this->village->id, 'name' => 'Kelompok Lain', 'code' => 'LAIN', 'slug' => 'kelompok-lain', 'is_active' => true]);
        $role = Role::create(['name' => 'Pelaksana Lain', 'slug' => 'pelaksana-ppg-kelompok-'.$otherGroup->id, 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'confirm-report-card-publishing'], ['name' => 'x', 'module' => 'x', 'is_active' => true])->id);
        $otherUser = User::create(['name' => 'Lain', 'email' => 'lain@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $otherUser->assignRole($role->id, 'group', $otherGroup->id);

        $this->actingAs($otherUser)->get("/report-card-group-confirmations/{$confirmation->id}/edit")->assertForbidden();
    }
}
