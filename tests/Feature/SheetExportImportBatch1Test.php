<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Generus;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\MaterialCategory;
use App\Models\MaterialChapter;
use App\Models\Munaqosah;
use App\Models\Permission;
use App\Models\Region;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SessionAttendance;
use App\Models\Teacher;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Village;
use App\Support\Sheets\EvaluationScoreSheet;
use App\Support\Sheets\EvaluationSheet;
use App\Support\Sheets\MaterialCategorySheet;
use App\Support\Sheets\MaterialChapterSheet;
use App\Support\Sheets\MunaqosahSheet;
use App\Support\Sheets\SessionAttendanceSheet;
use App\Support\Sheets\UjianSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SheetExportImportBatch1Test extends TestCase
{
    use RefreshDatabase;

    public function test_every_batch_one_module_can_be_exported_as_xlsx(): void
    {
        $fixtures = $this->makeFixtures();
        $user = $this->globalAdmin();

        $downloads = [
            '/material-categories/export' => 'kategori-materi.xlsx',
            '/material-chapters/export' => 'bab-materi.xlsx',
            '/munaqosahs/export' => 'munaqosah.xlsx',
            '/ujians/export' => 'ujian.xlsx',
            '/report-cards/export' => 'rapor.xlsx',
            '/learning-sessions/export' => 'jadwal-kbm.xlsx',
            '/evaluations/export' => 'evaluasi.xlsx',
            '/evaluation-scores/export' => 'nilai-evaluasi.xlsx',
            '/session-attendances/export' => 'absensi-sesi.xlsx',
        ];

        foreach ($downloads as $url => $filename) {
            $this->actingAs($user)->get($url)->assertDownload($filename);
        }

        $this->assertNotNull($fixtures);
    }

    public function test_material_category_sheet_import_creates_and_updates_by_natural_key(): void
    {
        $classGrade = $this->makeClassGrade();
        $semester = $this->makeSemester();
        $sheet = new MaterialCategorySheet;

        $error = $sheet->importRow([
            'class_grade_code' => $classGrade->code,
            'semester_code' => $semester->code,
            'name' => 'Akhlak',
            'code' => 'AKHLAK',
            'sort_order' => '1',
            'status' => 'aktif',
            'description' => 'Kategori akhlak',
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('material_categories', ['code' => 'AKHLAK', 'name' => 'Akhlak', 'is_active' => true]);

        // Re-importing the same natural key updates in place instead of duplicating.
        $sheet->importRow([
            'class_grade_code' => $classGrade->code,
            'semester_code' => $semester->code,
            'name' => 'Akhlak Mulia',
            'code' => 'AKHLAK',
            'sort_order' => '2',
            'status' => 'nonaktif',
            'description' => null,
        ]);

        $this->assertDatabaseCount('material_categories', 1);
        $this->assertDatabaseHas('material_categories', ['code' => 'AKHLAK', 'name' => 'Akhlak Mulia', 'is_active' => false]);
    }

    public function test_material_chapter_sheet_import_requires_an_existing_category(): void
    {
        $classGrade = $this->makeClassGrade();
        $semester = $this->makeSemester();
        $sheet = new MaterialChapterSheet;

        $error = $sheet->importRow([
            'class_grade_code' => $classGrade->code,
            'semester_code' => $semester->code,
            'category_code' => 'TIDAK-ADA',
            'name' => 'Bab Etika',
            'code' => 'BAB-01',
            'status' => 'aktif',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseCount('material_chapters', 0);

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKHLAK',
            'is_active' => true,
        ]);

        $error = $sheet->importRow([
            'class_grade_code' => $classGrade->code,
            'semester_code' => $semester->code,
            'category_code' => 'AKHLAK',
            'name' => 'Bab Etika',
            'code' => 'BAB-01',
            'status' => 'aktif',
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('material_chapters', ['material_category_id' => $category->id, 'code' => 'BAB-01']);
    }

    public function test_munaqosah_sheet_import_resolves_generus_year_and_semester_by_code(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-M-001', 'full_name' => 'Generus Munaqosah', 'status' => 'active']);
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new MunaqosahSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'PPG-M-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'title' => 'Munaqosah Semester Ganjil',
            'type' => 'semester-final',
            'score' => '85',
            'result' => null,
            'status' => 'completed',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('munaqosahs', [
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'type' => 'semester-final',
            'score' => 85,
        ]);
    }

    public function test_munaqosah_sheet_import_rejects_unknown_generus(): void
    {
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new MunaqosahSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'TIDAK-ADA',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'title' => 'Munaqosah',
            'type' => 'semester-final',
            'status' => 'completed',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseCount('munaqosahs', 0);
    }

    public function test_ujian_sheet_import_allows_several_entries_of_the_same_type(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-U-001', 'full_name' => 'Generus Ujian', 'status' => 'active']);
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new UjianSheet;

        $sheet->importRow([
            'generus_registration_number' => 'PPG-U-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'title' => 'Ulangan Harian 1',
            'type' => 'harian',
            'score' => '80',
            'status' => 'completed',
        ]);

        $sheet->importRow([
            'generus_registration_number' => 'PPG-U-001',
            'academic_year_code' => $academicYear->code,
            'semester_code' => $semester->code,
            'title' => 'Ulangan Harian 2',
            'type' => 'harian',
            'score' => '90',
            'status' => 'completed',
        ]);

        $this->assertDatabaseCount('ujians', 2);
        $this->assertDatabaseHas('ujians', ['generus_id' => $generus->id, 'score' => 80]);
        $this->assertDatabaseHas('ujians', ['generus_id' => $generus->id, 'score' => 90]);
    }

    public function test_evaluation_sheet_import_resolves_teacher_material_semester_and_year(): void
    {
        $teacher = Teacher::create(['registration_number' => 'GR-001', 'name' => 'Guru Satu', 'status' => 'active']);
        $material = $this->makeLearningMaterial();
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $sheet = new EvaluationSheet;

        $error = $sheet->importRow([
            'teacher_registration_number' => 'GR-001',
            'material_code' => $material->code,
            'semester_code' => $semester->code,
            'academic_year_code' => $academicYear->code,
            'title' => 'Kuis 1',
            'type' => 'quiz',
            'scheduled_at' => '2026-01-10',
            'status' => 'scheduled',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('evaluations', ['teacher_id' => $teacher->id, 'material_id' => $material->id, 'title' => 'Kuis 1']);
    }

    public function test_evaluation_score_sheet_import_finds_the_matching_evaluation_and_generus(): void
    {
        $teacher = Teacher::create(['registration_number' => 'GR-002', 'name' => 'Guru Dua', 'status' => 'active']);
        $material = $this->makeLearningMaterial();
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);
        $generus = Generus::create(['registration_number' => 'PPG-E-001', 'full_name' => 'Generus Evaluasi', 'status' => 'active']);
        $evaluation = Evaluation::create([
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'semester_id' => $semester->id,
            'academic_year_id' => $academicYear->id,
            'title' => 'Kuis 1',
            'type' => 'quiz',
            'status' => 'scheduled',
        ]);

        $sheet = new EvaluationScoreSheet;

        $error = $sheet->importRow([
            'evaluation_teacher_registration_number' => 'GR-002',
            'evaluation_material_code' => $material->code,
            'evaluation_semester_code' => $semester->code,
            'evaluation_academic_year_code' => $academicYear->code,
            'evaluation_title' => 'Kuis 1',
            'evaluation_type' => 'quiz',
            'generus_registration_number' => 'PPG-E-001',
            'score' => '95',
            'grade' => 'A',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('evaluation_scores', ['evaluation_id' => $evaluation->id, 'generus_id' => $generus->id, 'score' => 95, 'grade' => 'A']);
    }

    public function test_session_attendance_sheet_import_finds_the_session_by_date_material_and_group(): void
    {
        $region = Region::firstOrCreate(['code' => 'RT'], ['name' => 'Daerah Test', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'DS1', 'slug' => 'desa-satu', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Satu', 'code' => 'KS1', 'slug' => 'kelompok-satu', 'is_active' => true]);
        $material = $this->makeLearningMaterial();
        $generus = Generus::create(['registration_number' => 'PPG-A-001', 'full_name' => 'Generus Absensi', 'status' => 'active']);

        $session = LearningSession::create([
            'material_id' => $material->id,
            'level' => LearningSession::LEVEL_GROUP,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'session_date' => '2026-01-19',
            'status' => 'scheduled',
        ]);

        $sheet = new SessionAttendanceSheet($this->globalAdmin());

        $error = $sheet->importRow([
            'session_date' => '2026-01-19',
            'material_title' => $material->title,
            'level' => LearningSession::LEVEL_GROUP,
            'village_name' => $village->name,
            'group_name' => $group->name,
            'generus_registration_number' => 'PPG-A-001',
            'status' => 'present',
            'notes' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('session_attendances', ['learning_session_id' => $session->id, 'generus_id' => $generus->id, 'status' => 'present']);
    }

    private function globalAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);

        $slugs = [
            'manage-learning-materials', 'manage-munaqosah', 'manage-ujian', 'manage-report-cards',
            'manage-learning-sessions', 'manage-evaluations', 'manage-learning-attendance',
        ];

        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::create(['name' => 'Admin PPG', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    /**
     * Creates one row of real data for every batch-1 module so export queries exercise their
     * relations instead of running against empty tables.
     *
     * @return array<string, mixed>
     */
    private function makeFixtures(): array
    {
        $classGrade = $this->makeClassGrade();
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKHLAK',
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab Etika',
            'code' => 'BAB-01',
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Modul Etika',
            'code' => 'MAT-01',
            'week_number' => 1,
            'is_active' => true,
        ]);

        $teacher = Teacher::create(['registration_number' => 'GR-FIX-001', 'name' => 'Guru Fixture', 'status' => 'active']);
        $generus = Generus::create(['registration_number' => 'PPG-FIX-001', 'full_name' => 'Generus Fixture', 'status' => 'active']);

        $region = Region::firstOrCreate(['code' => 'RT'], ['name' => 'Daerah Test', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Fixture', 'code' => 'DF', 'slug' => 'desa-fixture', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Fixture', 'code' => 'KF', 'slug' => 'kelompok-fixture', 'is_active' => true]);

        $munaqosah = Munaqosah::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Munaqosah Fixture',
            'type' => 'semester-final',
            'score' => 85,
            'status' => 'completed',
        ]);

        Ujian::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'title' => 'Ulangan Fixture',
            'type' => 'harian',
            'score' => 90,
            'status' => 'completed',
        ]);

        $reportCard = ReportCard::where('generus_id', $generus->id)->first();

        $session = LearningSession::create([
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'level' => LearningSession::LEVEL_GROUP,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'session_date' => '2026-01-19',
            'status' => 'scheduled',
        ]);

        SessionAttendance::create([
            'learning_session_id' => $session->id,
            'generus_id' => $generus->id,
            'status' => 'present',
        ]);

        $evaluation = Evaluation::create([
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'semester_id' => $semester->id,
            'academic_year_id' => $academicYear->id,
            'title' => 'Kuis Fixture',
            'type' => 'quiz',
            'status' => 'scheduled',
        ]);

        EvaluationScore::create([
            'evaluation_id' => $evaluation->id,
            'generus_id' => $generus->id,
            'score' => 95,
            'grade' => 'A',
        ]);

        return compact('category', 'chapter', 'material', 'teacher', 'generus', 'village', 'group', 'munaqosah', 'reportCard', 'session', 'evaluation');
    }

    private function makeClassGrade(): ClassGrade
    {
        $level = Level::create(['name' => 'Jenjang Dasar', 'code' => 'JD-'.uniqid(), 'sort_order' => 1, 'is_active' => true]);

        return ClassGrade::create(['level_id' => $level->id, 'name' => 'Kelas 1', 'code' => 'KELAS-1-'.uniqid(), 'sort_order' => 1, 'is_active' => true]);
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

    private function makeSemester(?AcademicYear $academicYear = null): Semester
    {
        return Semester::create([
            'academic_year_id' => ($academicYear ?? $this->makeAcademicYear())->id,
            'name' => 'Semester Ganjil',
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function makeLearningMaterial(): LearningMaterial
    {
        $classGrade = $this->makeClassGrade();
        $academicYear = $this->makeAcademicYear();
        $semester = $this->makeSemester($academicYear);

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Akhlak',
            'code' => 'AKHLAK-'.uniqid(),
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab Etika',
            'code' => 'BAB-'.uniqid(),
            'is_active' => true,
        ]);

        return LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Modul Etika',
            'code' => 'MAT-'.uniqid(),
            'is_active' => true,
        ]);
    }
}
