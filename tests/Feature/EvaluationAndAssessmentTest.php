<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CurriculumProgram;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Generus;
use App\Models\LearningMaterial;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EvaluationAndAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_assessment_and_score_for_generus(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $assessmentPermission = Permission::create([
            'name' => 'Manage Evaluations',
            'slug' => 'manage-evaluations',
            'module' => 'evaluations',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$assessmentPermission->id]);

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
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $level = Level::create([
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $program = CurriculumProgram::create([
            'name' => 'Pendidikan Karakter',
            'code' => 'PK-01',
            'description' => 'Program pembinaan karakter',
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'curriculum_program_id' => $program->id,
            'title' => 'Modul Etika',
            'code' => 'MAT-ETIKA',
            'level_id' => $level->id,
            'semester_id' => $semester->id,
            'academic_year_id' => $academicYear->id,
            'description' => 'Materi pembinaan sikap',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'name' => 'Farah Salsabila',
            'gender' => 'perempuan',
            'phone' => '081122334455',
            'email' => 'farah@ppg.test',
            'status' => 'active',
            'notes' => 'Guru pembina',
        ]);

        $generus = Generus::create([
            'registration_number' => 'PPG-020',
            'full_name' => 'Rina Maulida',
            'gender' => 'perempuan',
            'birth_date' => '2014-02-10',
            'status' => 'active',
            'notes' => 'Evaluasi awal',
        ]);

        $this->actingAs($user)
            ->post('/evaluations', [
                'teacher_id' => $teacher->id,
                'material_id' => $material->id,
                'semester_id' => $semester->id,
                'academic_year_id' => $academicYear->id,
                'title' => 'Ujian Kompetensi Etika',
                'type' => 'quiz',
                'scheduled_at' => '2026-09-20',
                'status' => 'scheduled',
                'notes' => 'Penilaian sikap dan pemahaman',
            ])
            ->assertRedirect('/evaluations');

        $this->assertDatabaseHas('evaluations', [
            'title' => 'Ujian Kompetensi Etika',
            'type' => 'quiz',
        ]);

        $evaluation = Evaluation::query()->first();

        $this->actingAs($user)
            ->post('/evaluation-scores', [
                'evaluation_id' => $evaluation->id,
                'generus_id' => $generus->id,
                'score' => 88.5,
                'grade' => 'A',
                'notes' => 'Skor sangat baik',
            ])
            ->assertRedirect('/evaluation-scores');

        $this->assertDatabaseHas('evaluation_scores', [
            'evaluation_id' => $evaluation->id,
            'generus_id' => $generus->id,
            'grade' => 'A',
        ]);
    }

    public function test_second_score_for_same_evaluation_and_generus_is_rejected(): void
    {
        $user = $this->createAdminWithPermission('manage-evaluations');
        [$teacher, $material, $academicYear, $semester] = $this->createTeachingContext();
        $generus = $this->createGenerus();
        $evaluation = Evaluation::create([
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'semester_id' => $semester->id,
            'academic_year_id' => $academicYear->id,
            'title' => 'Ujian Etika',
        ]);
        EvaluationScore::create([
            'evaluation_id' => $evaluation->id,
            'generus_id' => $generus->id,
            'score' => 80,
            'grade' => 'B',
        ]);

        $this->actingAs($user)
            ->from('/evaluation-scores')
            ->post('/evaluation-scores', [
                'evaluation_id' => $evaluation->id,
                'generus_id' => $generus->id,
                'score' => 90,
                'grade' => 'A',
            ])
            ->assertRedirect('/evaluation-scores')
            ->assertSessionHasErrors(['generus_id' => 'Generus ini sudah memiliki nilai untuk evaluasi tersebut.']);

        $this->assertDatabaseCount('evaluation_scores', 1);
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

    /**
     * @return array{0: Teacher, 1: LearningMaterial, 2: AcademicYear, 3: Semester}
     */
    private function createTeachingContext(): array
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

        $level = Level::create([
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $program = CurriculumProgram::create([
            'name' => 'Pendidikan Karakter',
            'code' => 'PK-01',
            'is_active' => true,
        ]);

        $material = LearningMaterial::create([
            'curriculum_program_id' => $program->id,
            'title' => 'Modul Etika',
            'code' => 'MAT-ETIKA',
            'level_id' => $level->id,
            'semester_id' => $semester->id,
            'academic_year_id' => $academicYear->id,
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'name' => 'Farah Salsabila',
            'status' => 'active',
        ]);

        return [$teacher, $material, $academicYear, $semester];
    }

    private function createGenerus(): Generus
    {
        return Generus::create([
            'registration_number' => 'PPG-DUP-001',
            'full_name' => 'Rina Maulida',
            'status' => 'active',
        ]);
    }
}
