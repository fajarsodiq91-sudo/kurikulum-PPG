<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\GradeScale;
use App\Models\Group;
use App\Models\Munaqosah;
use App\Models\Permission;
use App\Models\Region;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunaqosahAndReportCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_munaqosah_and_report_card(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $munaqosahPermission = Permission::create([
            'name' => 'Manage Munaqosah',
            'slug' => 'manage-munaqosah',
            'module' => 'munaqosah',
            'is_active' => true,
        ]);

        $reportCardPermission = Permission::create([
            'name' => 'Manage Report Card',
            'slug' => 'manage-report-cards',
            'module' => 'report-card',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$munaqosahPermission->id, $reportCardPermission->id]);

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
            'registration_number' => 'GEN-2026-001',
            'full_name' => 'Generus Munaqosah',
            'gender' => 'Laki-laki',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/munaqosahs', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Munaqosah Semester Ganjil',
                'type' => 'semester-final',
                'score' => 87.5,
                'result' => 'Lulus',
                'status' => 'completed',
                'notes' => 'Kompeten pada materi kurikulum dasar',
            ])
            ->assertRedirect('/munaqosahs');

        $this->assertDatabaseHas('munaqosahs', [
            'generus_id' => $generus->id,
            'title' => 'Munaqosah Semester Ganjil',
            'result' => 'Lulus',
        ]);

        $this->actingAs($user)
            ->post('/report-cards', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'final_score' => 88.5,
                'predicate' => 'A',
                'recommendation' => 'Tetap konsisten dan tingkatkan praktik pembelajaran.',
                'remarks' => 'Sangat baik',
            ])
            ->assertRedirect('/report-cards');

        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $generus->id,
            'predicate' => 'A',
            'remarks' => 'Sangat baik',
        ]);
    }

    public function test_munaqosah_score_is_converted_to_grade_using_master_data_scale(): void
    {
        $user = $this->createAdminWithPermission('manage-munaqosah');

        GradeScale::create([
            'grade' => 'A',
            'min_score' => 96,
            'max_score' => 100,
            'description' => 'Generus memahami materi dan mampu mempraktikkannya dengan sempurna.',
            'sort_order' => 1,
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
        $generus = $this->createGenerus();

        $this->actingAs($user)
            ->post('/munaqosahs', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Munaqosah Semester Ganjil',
                'type' => 'semester-final',
                'score' => 98,
                'status' => 'completed',
            ])
            ->assertRedirect('/munaqosahs');

        $munaqosah = Munaqosah::firstOrFail();

        $this->assertSame('A', $munaqosah->result);
        $this->assertSame('Generus memahami materi dan mampu mempraktikkannya dengan sempurna.', $munaqosah->grade_description);
    }

    public function test_duplicate_munaqosah_for_same_generus_semester_and_type_is_rejected(): void
    {
        $user = $this->createAdminWithPermission('manage-munaqosah');
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
        $generus = $this->createGenerus();

        Munaqosah::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Munaqosah Semester Ganjil',
            'type' => 'semester-final',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->from('/munaqosahs')
            ->post('/munaqosahs', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Munaqosah Susulan',
                'type' => 'semester-final',
                'status' => 'completed',
            ])
            ->assertRedirect('/munaqosahs')
            ->assertSessionHasErrors(['generus_id' => 'Generus ini sudah memiliki munaqosah untuk tahun ajaran, semester, dan tipe tersebut.']);

        $this->assertDatabaseCount('munaqosahs', 1);
    }

    public function test_munaqosah_can_be_updated_and_deleted(): void
    {
        $user = $this->createAdminWithPermission('manage-munaqosah');
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
        $generus = $this->createGenerus();
        $munaqosah = Munaqosah::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Munaqosah Semester Ganjil',
            'type' => 'semester-final',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)->get("/munaqosahs/{$munaqosah->id}/edit")->assertOk();

        $this->actingAs($user)
            ->put("/munaqosahs/{$munaqosah->id}", [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Munaqosah Semester Ganjil',
                'type' => 'semester-final',
                'status' => 'completed',
                'score' => 70,
            ])
            ->assertRedirect('/munaqosahs')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('munaqosahs', ['id' => $munaqosah->id, 'status' => 'completed', 'score' => 70]);

        $this->actingAs($user)
            ->delete("/munaqosahs/{$munaqosah->id}")
            ->assertRedirect('/munaqosahs');

        $this->assertModelMissing($munaqosah);
    }

    public function test_group_scoped_user_can_fill_munaqosah_batch_for_their_own_group_only(): void
    {
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok', 'is_active' => true]);
        $role->permissions()->attach(Permission::create([
            'name' => 'Manage Munaqosah',
            'slug' => 'manage-munaqosah',
            'module' => 'munaqosah',
            'is_active' => true,
        ])->id);

        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa A', 'code' => 'DA', 'slug' => 'desa-a', 'is_active' => true]);
        $ownGroup = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 1', 'code' => 'K1', 'slug' => 'kelompok-1', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 2', 'code' => 'K2', 'slug' => 'kelompok-2', 'is_active' => true]);

        $user = User::create(['name' => 'Pelaksana', 'email' => 'pelaksana@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $ownGroup->id);

        GradeScale::create([
            'grade' => 'A',
            'min_score' => 90,
            'max_score' => 100,
            'description' => 'Sangat baik.',
            'sort_order' => 1,
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

        $ownGenerus = Generus::create(['registration_number' => 'PPG-OWN-001', 'full_name' => 'Generus Kelompok Sendiri', 'status' => 'active']);
        GenerusAssignment::create(['generus_id' => $ownGenerus->id, 'region_id' => $region->id, 'village_id' => $village->id, 'group_id' => $ownGroup->id]);

        $otherGenerus = Generus::create(['registration_number' => 'PPG-OTHER-001', 'full_name' => 'Generus Kelompok Lain', 'status' => 'active']);
        GenerusAssignment::create(['generus_id' => $otherGenerus->id, 'region_id' => $region->id, 'village_id' => $village->id, 'group_id' => $otherGroup->id]);

        $batchResponse = $this->actingAs($user)->get('/munaqosahs/batch?group_id='.$ownGroup->id.'&academic_year_id='.$academicYear->id.'&semester_id='.$semester->id.'&type=semester-final');
        $batchResponse->assertOk();
        $batchResponse->assertSee('Generus Kelompok Sendiri');
        $batchResponse->assertDontSee('Generus Kelompok Lain');

        $this->actingAs($user)
            ->post('/munaqosahs/batch', [
                'group_id' => $ownGroup->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'type' => 'semester-final',
                'title' => 'Munaqosah Semester Ganjil',
                'scores' => [
                    $ownGenerus->id => ['score' => 95, 'status' => 'completed'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('munaqosahs', [
            'generus_id' => $ownGenerus->id,
            'score' => 95,
            'result' => 'A',
            'grade_description' => 'Sangat baik.',
        ]);

        $this->actingAs($user)
            ->post('/munaqosahs/batch', [
                'group_id' => $otherGroup->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'type' => 'semester-final',
                'title' => 'Munaqosah Semester Ganjil',
                'scores' => [
                    $otherGenerus->id => ['score' => 80, 'status' => 'completed'],
                ],
            ])
            ->assertSessionHasErrors(['group_id']);

        $this->assertDatabaseMissing('munaqosahs', ['generus_id' => $otherGenerus->id]);
    }

    public function test_second_report_card_for_same_generus_and_semester_is_rejected(): void
    {
        $user = $this->createAdminWithPermission('manage-report-cards');
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
        $generus = $this->createGenerus();
        ReportCard::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $this->actingAs($user)
            ->from('/report-cards')
            ->post('/report-cards', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
            ])
            ->assertRedirect('/report-cards')
            ->assertSessionHasErrors(['generus_id' => 'Generus ini sudah memiliki rapor untuk tahun ajaran dan semester tersebut.']);

        $this->assertDatabaseCount('report_cards', 1);
    }

    private function createAdminWithPermission(string $permissionSlug): User
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $role->permissions()->attach(Permission::create([
            'name' => $permissionSlug,
            'slug' => $permissionSlug,
            'module' => $permissionSlug,
            'is_active' => true,
        ])->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function createGenerus(): Generus
    {
        return Generus::create([
            'registration_number' => 'PPG-DUP-001',
            'full_name' => 'Rina Maulida',
            'status' => 'active',
        ]);
    }

    public function test_report_card_can_be_updated_and_deleted(): void
    {
        $user = $this->createAdminWithPermission('manage-report-cards');
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
        $generus = $this->createGenerus();
        $reportCard = ReportCard::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'predicate' => 'B',
        ]);

        $this->actingAs($user)->get("/report-cards/{$reportCard->id}/edit")->assertOk();

        $this->actingAs($user)
            ->put("/report-cards/{$reportCard->id}", [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'predicate' => 'A',
            ])
            ->assertRedirect('/report-cards')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('report_cards', ['id' => $reportCard->id, 'predicate' => 'A']);

        $this->actingAs($user)
            ->delete("/report-cards/{$reportCard->id}")
            ->assertRedirect('/report-cards');

        $this->assertModelMissing($reportCard);
    }
}
