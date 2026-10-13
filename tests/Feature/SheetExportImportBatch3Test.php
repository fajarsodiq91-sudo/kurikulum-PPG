<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityExecution;
use App\Models\ActivitySchedule;
use App\Models\AnnualAudit;
use App\Models\FollowUp;
use App\Models\Generus;
use App\Models\Milestone;
use App\Models\OrganizationUnit;
use App\Models\Permission;
use App\Models\ProgressTrack;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use App\Support\Sheets\ActivityExecutionSheet;
use App\Support\Sheets\ActivityScheduleSheet;
use App\Support\Sheets\AnnualAuditSheet;
use App\Support\Sheets\FollowUpSheet;
use App\Support\Sheets\MilestoneSheet;
use App\Support\Sheets\ProgressTrackSheet;
use App\Support\Sheets\TrainingSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SheetExportImportBatch3Test extends TestCase
{
    use RefreshDatabase;

    public function test_every_batch_three_module_can_be_exported_as_xlsx(): void
    {
        $user = $this->globalAdmin();

        $unit = OrganizationUnit::create(['name' => 'Bidang Kurikulum', 'code' => 'BK', 'unit_type' => 'bidang', 'status' => 'active']);
        $schedule = ActivitySchedule::create([
            'organization_unit_id' => $unit->id,
            'title' => 'Rapat Koordinasi',
            'type' => 'rapat',
            'scheduled_at' => '2026-01-19',
            'status' => 'scheduled',
        ]);
        ActivityExecution::create([
            'activity_schedule_id' => $schedule->id,
            'actual_date' => '2026-01-19',
            'status' => 'completed',
            'attendance_count' => 10,
            'outcome' => 'Berjalan lancar',
        ]);

        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $generus = Generus::create(['registration_number' => 'PPG-FIX-001', 'full_name' => 'Generus Fixture', 'status' => 'active']);

        Milestone::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Hafal Juz 1',
            'achievement_date' => '2026-01-10',
            'status' => 'achieved',
        ]);

        ProgressTrack::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'period_label' => 'Bulan 1',
            'overall_status' => 'baik',
        ]);

        AnnualAudit::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'audit_type' => 'tahunan',
            'overall_status' => 'baik',
        ]);

        Training::create(['title' => 'Workshop Kurikulum', 'type' => 'workshop', 'academic_year_id' => $academicYear->id, 'status' => 'scheduled']);

        $teacher = Teacher::create(['registration_number' => 'GR-FIX-001', 'name' => 'Guru Fixture', 'status' => 'active']);
        FollowUp::create([
            'generus_id' => $generus->id,
            'teacher_id' => $teacher->id,
            'title' => 'Perlu bimbingan tambahan',
            'priority' => 'high',
            'follow_up_date' => '2026-01-15',
            'status' => 'open',
        ]);

        $downloads = [
            '/activity-schedules/export' => 'jadwal-program.xlsx',
            '/activity-executions/export' => 'pelaksanaan-program.xlsx',
            '/milestones/export' => 'milestone.xlsx',
            '/progress-tracks/export' => 'progress-tracking.xlsx',
            '/trainings/export' => 'pelatihan-guru.xlsx',
            '/follow-ups/export' => 'follow-up.xlsx',
            '/annual-audits/export' => 'audit-tahunan.xlsx',
        ];

        foreach ($downloads as $url => $filename) {
            $this->actingAs($user)->get($url)->assertDownload($filename);
        }
    }

    public function test_activity_schedule_sheet_import_resolves_organization_unit_by_code(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Bidang Kurikulum', 'code' => 'BK', 'unit_type' => 'bidang', 'status' => 'active']);
        $sheet = new ActivityScheduleSheet;

        $error = $sheet->importRow([
            'organization_unit_code' => 'BK',
            'title' => 'Rapat Koordinasi',
            'type' => 'rapat',
            'scheduled_at' => '2026-01-19',
            'end_at' => null,
            'location' => null,
            'status' => 'scheduled',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('activity_schedules', ['organization_unit_id' => $unit->id, 'title' => 'Rapat Koordinasi']);
    }

    public function test_activity_execution_sheet_import_finds_the_matching_schedule(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Bidang Kurikulum', 'code' => 'BK', 'unit_type' => 'bidang', 'status' => 'active']);
        $schedule = ActivitySchedule::create([
            'organization_unit_id' => $unit->id,
            'title' => 'Rapat Koordinasi',
            'type' => 'rapat',
            'scheduled_at' => '2026-01-19',
            'status' => 'scheduled',
        ]);
        $sheet = new ActivityExecutionSheet;

        $error = $sheet->importRow([
            'schedule_organization_unit_code' => 'BK',
            'schedule_title' => 'Rapat Koordinasi',
            'schedule_scheduled_at' => '2026-01-19',
            'actual_date' => '2026-01-19',
            'status' => 'completed',
            'attendance_count' => '12',
            'outcome' => 'Berjalan lancar',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('activity_executions', ['activity_schedule_id' => $schedule->id, 'attendance_count' => 12]);
    }

    public function test_milestone_sheet_import_scopes_semester_to_its_own_academic_year(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-M2-001', 'full_name' => 'Generus Milestone', 'status' => 'active']);
        $otherAcademicYear = $this->makeAcademicYear();
        $this->makeSemester($otherAcademicYear); // another year reusing the same semester code "G".
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new MilestoneSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'PPG-M2-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'title' => 'Hafal Juz 1',
            'description' => null,
            'achievement_date' => '2026-01-10',
            'status' => 'achieved',
            'score' => '90',
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('milestones', ['generus_id' => $generus->id, 'semester_id' => $semester->id, 'academic_year_id' => $academicYear->id]);
    }

    public function test_progress_track_sheet_import_is_idempotent_per_period(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-P-001', 'full_name' => 'Generus Progress', 'status' => 'active']);
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new ProgressTrackSheet;

        $sheet->importRow([
            'generus_registration_number' => 'PPG-P-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'period_label' => 'Bulan 1',
            'overall_status' => 'baik',
            'score' => '80',
            'notes' => null,
            'next_goal' => null,
        ]);

        $sheet->importRow([
            'generus_registration_number' => 'PPG-P-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'period_label' => 'Bulan 1',
            'overall_status' => 'sangat baik',
            'score' => '95',
            'notes' => null,
            'next_goal' => null,
        ]);

        $this->assertDatabaseCount('progress_tracks', 1);
        $this->assertDatabaseHas('progress_tracks', ['generus_id' => $generus->id, 'overall_status' => 'sangat baik', 'score' => 95]);
    }

    public function test_training_sheet_import_resolves_academic_year_by_code(): void
    {
        $academicYear = $this->makeAcademicYear();
        $sheet = new TrainingSheet;

        $error = $sheet->importRow([
            'title' => 'Workshop Kurikulum',
            'type' => 'workshop',
            'academic_year_code' => $academicYear->code,
            'scheduled_at' => '2026-02-01',
            'location' => null,
            'status' => 'scheduled',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('trainings', ['title' => 'Workshop Kurikulum', 'academic_year_id' => $academicYear->id]);
    }

    public function test_follow_up_sheet_import_resolves_generus_and_teacher_by_registration_number(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-F-001', 'full_name' => 'Generus Follow Up', 'status' => 'active']);
        $teacher = Teacher::create(['registration_number' => 'GR-F-001', 'name' => 'Guru Follow Up', 'status' => 'active']);
        $sheet = new FollowUpSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'PPG-F-001',
            'teacher_registration_number' => 'GR-F-001',
            'title' => 'Perlu bimbingan tambahan',
            'priority' => 'high',
            'follow_up_date' => '2026-01-15',
            'status' => 'open',
            'notes' => null,
            'next_action' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('follow_ups', ['generus_id' => $generus->id, 'teacher_id' => $teacher->id, 'title' => 'Perlu bimbingan tambahan']);
    }

    public function test_annual_audit_sheet_import_resolves_generus_year_and_semester(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-A2-001', 'full_name' => 'Generus Audit', 'status' => 'active']);
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new AnnualAuditSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'PPG-A2-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'audit_type' => 'tahunan',
            'overall_status' => 'baik',
            'summary' => null,
            'recommendation' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('annual_audits', ['generus_id' => $generus->id, 'audit_type' => 'tahunan']);
    }

    private function globalAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);

        $slugs = [
            'manage-activity-schedules', 'manage-activity-executions', 'manage-milestones',
            'manage-progress-tracking', 'manage-training', 'manage-follow-ups', 'manage-annual-audit',
        ];

        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::create(['name' => 'Admin PPG', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function makeAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026-'.uniqid(),
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);
    }

    private function makeSemester(AcademicYear $academicYear): Semester
    {
        return Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}
