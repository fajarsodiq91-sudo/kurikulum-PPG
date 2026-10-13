<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Munaqosah;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UjianReportCardAveragingTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_card_uses_munaqosah_score_alone_when_there_is_no_ujian(): void
    {
        [$generus, $academicYear, $semester] = $this->makeContext();

        Munaqosah::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Munaqosah Semester Ganjil',
            'type' => 'semester-final',
            'score' => 80,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'final_score' => 80,
        ]);
    }

    public function test_report_card_averages_munaqosah_and_ujian_scores_in_the_same_semester(): void
    {
        [$generus, $academicYear, $semester] = $this->makeContext();

        Munaqosah::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Munaqosah Semester Ganjil',
            'type' => 'semester-final',
            'score' => 80,
            'status' => 'completed',
        ]);

        Ujian::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Ulangan Harian 1',
            'type' => 'harian',
            'score' => 90,
            'status' => 'completed',
        ]);

        Ujian::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Ulangan Harian 2',
            'type' => 'harian',
            'score' => 100,
            'status' => 'completed',
        ]);

        // Average ujian = (90 + 100) / 2 = 95; final = (80 + 95) / 2 = 87.5.
        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'final_score' => 87.5,
        ]);
    }

    public function test_report_card_recalculates_when_a_ujian_score_is_deleted(): void
    {
        [$generus, $academicYear, $semester] = $this->makeContext();

        Munaqosah::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Munaqosah Semester Ganjil',
            'type' => 'semester-final',
            'score' => 80,
            'status' => 'completed',
        ]);

        $ujian = Ujian::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Ulangan Harian 1',
            'type' => 'harian',
            'score' => 100,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('report_cards', ['generus_id' => $generus->id, 'final_score' => 90]);

        $ujian->delete();

        $this->assertDatabaseHas('report_cards', ['generus_id' => $generus->id, 'final_score' => 80]);
    }

    public function test_admin_can_submit_ujian_through_the_form(): void
    {
        [$generus, $academicYear, $semester] = $this->makeContext();

        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $permission = Permission::create(['name' => 'Manage Ujian', 'slug' => 'manage-ujian', 'module' => 'ujian', 'is_active' => true]);
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin-ujian@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, 'global', null);

        $this->actingAs($user)
            ->post('/ujians', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Ulangan Mingguan 1',
                'type' => 'mingguan',
                'score' => 92,
                'status' => 'completed',
            ])
            ->assertRedirect('/ujians');

        $this->assertDatabaseHas('ujians', [
            'generus_id' => $generus->id,
            'title' => 'Ulangan Mingguan 1',
            'type' => 'mingguan',
            'score' => 92,
        ]);
    }

    /**
     * @return array{0: Generus, 1: AcademicYear, 2: Semester}
     */
    private function makeContext(): array
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

        $generus = Generus::create([
            'registration_number' => 'PPG-UJIAN-001',
            'full_name' => 'Generus Ujian',
            'status' => 'active',
        ]);

        return [$generus, $academicYear, $semester];
    }
}
