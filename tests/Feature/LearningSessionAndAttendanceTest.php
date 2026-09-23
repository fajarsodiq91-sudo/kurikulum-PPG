<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CurriculumProgram;
use App\Models\Generus;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SessionAttendance;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LearningSessionAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_learning_session_and_attendance_record(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $sessionPermission = Permission::create([
            'name' => 'Manage Learning Sessions',
            'slug' => 'manage-learning-sessions',
            'module' => 'learning-sessions',
            'is_active' => true,
        ]);

        $attendancePermission = Permission::create([
            'name' => 'Manage Learning Attendance',
            'slug' => 'manage-learning-attendance',
            'module' => 'learning-attendance',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$sessionPermission->id, $attendancePermission->id]);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $teacher = Teacher::create([
            'name' => 'Farah Salsabila',
            'gender' => 'perempuan',
            'phone' => '081122334455',
            'email' => 'farah@ppg.test',
            'status' => 'active',
            'notes' => 'Guru pembina',
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

        $this->actingAs($user)
            ->post('/learning-sessions', [
                'teacher_id' => $teacher->id,
                'material_id' => $material->id,
                'session_date' => '2026-09-16',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'location' => 'Ruang Kelas 1',
                'status' => 'scheduled',
                'notes' => 'Pertemuan awal',
            ])
            ->assertRedirect('/learning-sessions');

        $this->assertDatabaseHas('learning_sessions', [
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'location' => 'Ruang Kelas 1',
        ]);

        $generus = Generus::create([
            'registration_number' => 'PPG-010',
            'full_name' => 'Maya Sari',
            'gender' => 'perempuan',
            'birth_date' => '2014-02-09',
            'status' => 'active',
            'notes' => 'Generus aktif',
        ]);

        $session = LearningSession::query()->first();

        $this->actingAs($user)
            ->post('/session-attendances', [
                'learning_session_id' => $session->id,
                'generus_id' => $generus->id,
                'status' => 'present',
                'notes' => 'Hadir tepat waktu',
            ])
            ->assertRedirect('/session-attendances');

        $this->assertDatabaseHas('session_attendances', [
            'learning_session_id' => $session->id,
            'generus_id' => $generus->id,
            'status' => 'present',
        ]);
    }

    public function test_second_attendance_for_same_session_and_generus_is_rejected(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-attendance');
        [$teacher, $material] = $this->createTeachingContext();
        $generus = $this->createGenerus();
        $session = LearningSession::create([
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'session_date' => '2026-09-20',
        ]);
        SessionAttendance::create([
            'learning_session_id' => $session->id,
            'generus_id' => $generus->id,
            'status' => 'present',
        ]);

        $this->actingAs($user)
            ->from('/session-attendances')
            ->post('/session-attendances', [
                'learning_session_id' => $session->id,
                'generus_id' => $generus->id,
                'status' => 'absent',
            ])
            ->assertRedirect('/session-attendances')
            ->assertSessionHasErrors(['generus_id' => 'Presensi generus ini untuk sesi tersebut sudah dicatat.']);

        $this->assertDatabaseCount('session_attendances', 1);
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

    public function test_attendance_can_be_updated_and_deleted(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-attendance');
        [$teacher, $material] = $this->createTeachingContext();
        $generus = $this->createGenerus();
        $session = LearningSession::create([
            'teacher_id' => $teacher->id,
            'material_id' => $material->id,
            'session_date' => '2026-09-20',
        ]);
        $attendance = SessionAttendance::create([
            'learning_session_id' => $session->id,
            'generus_id' => $generus->id,
            'status' => 'absent',
        ]);

        $this->actingAs($user)->get("/session-attendances/{$attendance->id}/edit")->assertOk();

        $this->actingAs($user)
            ->put("/session-attendances/{$attendance->id}", [
                'learning_session_id' => $session->id,
                'generus_id' => $generus->id,
                'status' => 'excused',
            ])
            ->assertRedirect('/session-attendances')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('session_attendances', ['id' => $attendance->id, 'status' => 'excused']);

        $this->actingAs($user)
            ->delete("/session-attendances/{$attendance->id}")
            ->assertRedirect('/session-attendances');

        $this->assertModelMissing($attendance);
    }
}
