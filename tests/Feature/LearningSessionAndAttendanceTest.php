<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CurriculumProgram;
use App\Models\Generus;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SessionAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Test', 'code' => 'KT', 'slug' => 'kelompok-test', 'is_active' => true]);

        $this->actingAs($user)
            ->post('/learning-sessions', [
                'teacher_id' => $teacher->id,
                'material_id' => $material->id,
                'level' => 'group',
                'group_id' => $group->id,
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
            'level' => 'group',
            'group_id' => $group->id,
            'village_id' => $village->id,
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

    public function test_village_representative_fills_attendance_only_for_village_level_sessions(): void
    {
        [$village, $group, $otherGroup] = $this->createPlacements();
        $user = $this->createScopedUser('village', $village->id);
        [$teacher, $material] = $this->createTeachingContext();
        $inVillage = $this->createGenerusAt('Ali', ['village_id' => $village->id, 'group_id' => $group->id]);
        $elsewhere = $this->createGenerusAt('Budi', ['village_id' => $otherGroup->village_id, 'group_id' => $otherGroup->id]);
        $villageSession = $this->createSession($teacher, $material, ['level' => 'village', 'village_id' => $village->id]);
        $groupSession = $this->createSession($teacher, $material, ['level' => 'group', 'group_id' => $group->id, 'village_id' => $village->id]);

        $this->actingAs($user)->get("/learning-sessions/{$villageSession->id}/attendance")
            ->assertOk()->assertSee('Ali')->assertDontSee('Budi');

        $this->actingAs($user)
            ->put("/learning-sessions/{$villageSession->id}/attendance", ['attendance' => [
                $inVillage->id => ['status' => 'present', 'notes' => 'Tepat waktu'],
            ]])
            ->assertRedirect("/learning-sessions/{$villageSession->id}/attendance");

        $this->assertDatabaseHas('session_attendances', ['learning_session_id' => $villageSession->id, 'generus_id' => $inVillage->id, 'status' => 'present']);

        $this->actingAs($user)->get("/learning-sessions/{$groupSession->id}/attendance")->assertNotFound();
        $this->actingAs($user)->put("/learning-sessions/{$groupSession->id}/attendance", ['attendance' => [$inVillage->id => ['status' => 'present']]])->assertNotFound();
        $this->actingAs($user)->post('/session-attendances', ['learning_session_id' => $groupSession->id, 'generus_id' => $inVillage->id, 'status' => 'present'])
            ->assertSessionHasErrors('learning_session_id');

        $this->actingAs($user)->put("/learning-sessions/{$villageSession->id}/attendance", ['attendance' => [$elsewhere->id => ['status' => 'present']]])->assertStatus(422);
        $this->assertDatabaseMissing('session_attendances', ['generus_id' => $elsewhere->id]);
    }

    public function test_group_executor_fills_attendance_only_for_own_group_sessions_and_students(): void
    {
        [$village, $group, $otherGroup] = $this->createPlacements();
        $user = $this->createScopedUser('group', $group->id);
        $teacher = Teacher::create(['name' => 'Farah Salsabila', 'status' => 'active', 'group_id' => $group->id]);
        $user->forceFill(['teacher_id' => $teacher->id])->save();
        [, $material] = $this->createTeachingContext();
        $mine = $this->createGenerusAt('Ali', ['village_id' => $village->id, 'group_id' => $group->id]);
        $notMine = $this->createGenerusAt('Citra', ['village_id' => $village->id, 'group_id' => $group->id]);
        $teacher->students()->attach($mine->id);
        $groupSession = $this->createSession($teacher, $material, ['level' => 'group', 'group_id' => $group->id, 'village_id' => $village->id]);
        $otherSession = $this->createSession($teacher, $material, ['level' => 'group', 'group_id' => $otherGroup->id, 'village_id' => $otherGroup->village_id]);
        $villageSession = $this->createSession($teacher, $material, ['level' => 'village', 'village_id' => $village->id]);

        $this->actingAs($user)->get("/learning-sessions/{$groupSession->id}/attendance")
            ->assertOk()->assertSee('Ali')->assertDontSee('Citra');

        $this->actingAs($user)
            ->put("/learning-sessions/{$groupSession->id}/attendance", ['attendance' => [$mine->id => ['status' => 'excused']]])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('session_attendances', ['learning_session_id' => $groupSession->id, 'generus_id' => $mine->id, 'status' => 'excused']);

        $this->actingAs($user)->put("/learning-sessions/{$groupSession->id}/attendance", ['attendance' => [$notMine->id => ['status' => 'present']]])->assertStatus(422);
        $this->actingAs($user)->get("/learning-sessions/{$otherSession->id}/attendance")->assertNotFound();
        $this->actingAs($user)->get("/learning-sessions/{$villageSession->id}/attendance")->assertNotFound();
    }

    public function test_saving_the_sheet_again_updates_and_skips_blank_statuses(): void
    {
        [$village, $group] = $this->createPlacements();
        $user = $this->createScopedUser('group', $group->id);
        [$teacher, $material] = $this->createTeachingContext();
        $first = $this->createGenerusAt('Ali', ['village_id' => $village->id, 'group_id' => $group->id]);
        $second = $this->createGenerusAt('Dina', ['village_id' => $village->id, 'group_id' => $group->id]);
        $session = $this->createSession($teacher, $material, ['level' => 'group', 'group_id' => $group->id, 'village_id' => $village->id]);

        $this->actingAs($user)->put("/learning-sessions/{$session->id}/attendance", ['attendance' => [
            $first->id => ['status' => 'absent'], $second->id => ['status' => ''],
        ]]);
        $this->actingAs($user)->put("/learning-sessions/{$session->id}/attendance", ['attendance' => [
            $first->id => ['status' => 'late'],
        ]]);

        $this->assertDatabaseCount('session_attendances', 1);
        $this->assertDatabaseHas('session_attendances', ['generus_id' => $first->id, 'status' => 'late']);

        $this->actingAs($user)->put("/learning-sessions/{$session->id}/attendance", ['attendance' => [$first->id => ['status' => 'bogus']]])
            ->assertSessionHasErrors('attendance.'.$first->id.'.status');
    }

    public function test_scans_record_presence_by_qr_rfid_and_face_within_the_roster(): void
    {
        Storage::fake();
        [$village, $group, $otherGroup] = $this->createPlacements();
        $user = $this->createScopedUser('group', $group->id);
        [$teacher, $material] = $this->createTeachingContext();
        $qr = $this->createGenerusAt('Ali', ['village_id' => $village->id, 'group_id' => $group->id]);
        $rfid = $this->createGenerusAt('Dina', ['village_id' => $village->id, 'group_id' => $group->id]);
        $face = $this->createGenerusAt('Eka', ['village_id' => $village->id, 'group_id' => $group->id]);
        $outsider = $this->createGenerusAt('Fani', ['village_id' => $otherGroup->village_id, 'group_id' => $otherGroup->id]);
        $rfid->update(['rfid_uid' => 'A1B2C3D4']);
        $outsider->update(['rfid_uid' => 'FFFF0000']);
        $face->update(['photo' => UploadedFile::fake()->create('eka.jpg', 10, 'image/jpeg')->store(Generus::PHOTO_DIRECTORY)]);
        $session = $this->createSession($teacher, $material, ['level' => 'group', 'group_id' => $group->id, 'village_id' => $village->id]);
        $url = "/learning-sessions/{$session->id}/attendance/scan";

        $this->actingAs($user)->postJson($url, ['method' => 'qr', 'code' => $qr->registration_number])
            ->assertOk()->assertJson(['generus_id' => $qr->id, 'already_present' => false]);
        $this->actingAs($user)->postJson($url, ['method' => 'qr', 'code' => $qr->registration_number])
            ->assertOk()->assertJson(['already_present' => true]);
        $this->actingAs($user)->postJson($url, ['method' => 'rfid', 'code' => ' a1b2 c3d4 '])->assertOk()->assertJson(['generus_id' => $rfid->id]);
        $this->actingAs($user)->postJson($url, ['method' => 'face', 'generus_id' => $face->id])->assertOk();

        $this->assertDatabaseHas('session_attendances', ['generus_id' => $qr->id, 'status' => 'present', 'method' => 'qr']);
        $this->assertDatabaseHas('session_attendances', ['generus_id' => $rfid->id, 'method' => 'rfid']);
        $this->assertDatabaseHas('session_attendances', ['generus_id' => $face->id, 'method' => 'face']);

        $this->actingAs($user)->postJson($url, ['method' => 'qr', 'code' => $outsider->registration_number])->assertNotFound();
        $this->actingAs($user)->postJson($url, ['method' => 'rfid', 'code' => 'FFFF0000'])->assertNotFound();
        $this->actingAs($user)->postJson($url, ['method' => 'face', 'generus_id' => $outsider->id])->assertNotFound();
        $this->actingAs($user)->postJson($url, ['method' => 'rfid'])->assertUnprocessable();
        $this->assertDatabaseMissing('session_attendances', ['generus_id' => $outsider->id]);

        $this->actingAs($user)->getJson("/learning-sessions/{$session->id}/attendance/faces")
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['id' => $face->id, 'name' => 'Eka']);
        $this->actingAs($user)->get("/learning-sessions/{$session->id}/attendance/faces/{$face->id}/photo")->assertOk();
        $this->actingAs($user)->get("/learning-sessions/{$session->id}/attendance/faces/{$outsider->id}/photo")->assertNotFound();
    }

    public function test_scan_is_not_allowed_on_sessions_outside_the_users_scope(): void
    {
        [$village, $group, $otherGroup] = $this->createPlacements();
        $user = $this->createScopedUser('group', $group->id);
        [$teacher, $material] = $this->createTeachingContext();
        $member = $this->createGenerusAt('Ali', ['village_id' => $village->id, 'group_id' => $group->id]);
        $foreign = $this->createSession($teacher, $material, ['level' => 'group', 'group_id' => $otherGroup->id, 'village_id' => $otherGroup->village_id]);

        $this->actingAs($user)->postJson("/learning-sessions/{$foreign->id}/attendance/scan", ['method' => 'qr', 'code' => $member->registration_number])->assertNotFound();
        $this->actingAs($user)->getJson("/learning-sessions/{$foreign->id}/attendance/faces")->assertNotFound();
    }

    public function test_rfid_uid_is_normalized_and_unique_on_the_generus_form(): void
    {
        $user = $this->createAdminWithPermission('manage-generus');
        $existing = $this->createGenerus();
        $existing->update(['rfid_uid' => 'AABBCCDD']);

        $this->actingAs($user)->put("/generus/{$existing->id}", ['rfid_uid' => 'aa bb cc dd', 'full_name' => 'Rina Maulida', 'status' => 'pindah_sambung', 'transfer_destination' => 'external'])
            ->assertSessionHasNoErrors();
        $this->assertSame('AABBCCDD', $existing->fresh()->rfid_uid);

        $other = Generus::create(['registration_number' => 'PPG-OTH-001', 'full_name' => 'Lain', 'status' => 'active']);
        $this->actingAs($user)->put("/generus/{$other->id}", ['rfid_uid' => 'aabbccdd', 'full_name' => 'Lain', 'status' => 'pindah_sambung', 'transfer_destination' => 'external'])
            ->assertSessionHasErrors('rfid_uid');
    }

    public function test_learning_session_requires_placement_matching_its_level(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-sessions');
        [$teacher, $material] = $this->createTeachingContext();

        $this->actingAs($user)
            ->post('/learning-sessions', ['teacher_id' => $teacher->id, 'material_id' => $material->id, 'level' => 'village', 'session_date' => '2026-09-20', 'status' => 'scheduled'])
            ->assertSessionHasErrors('village_id');
    }

    /**
     * @return array{0: Village, 1: Group, 2: Group}
     */
    private function createPlacements(): array
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa A', 'code' => 'DA', 'slug' => 'desa-a', 'is_active' => true]);
        $otherVillage = Village::create(['region_id' => $region->id, 'name' => 'Desa B', 'code' => 'DB', 'slug' => 'desa-b', 'is_active' => true]);

        return [
            $village,
            Group::create(['village_id' => $village->id, 'name' => 'Kelompok 1', 'code' => 'K1', 'slug' => 'kelompok-1', 'is_active' => true]),
            Group::create(['village_id' => $otherVillage->id, 'name' => 'Kelompok 2', 'code' => 'K2', 'slug' => 'kelompok-2', 'is_active' => true]),
        ];
    }

    private function createScopedUser(string $scopeType, int $scopeId): User
    {
        $role = Role::create(['name' => 'Peran '.$scopeType, 'slug' => 'peran-'.$scopeType, 'is_active' => true]);
        $role->permissions()->attach(Permission::create([
            'name' => 'Manage Learning Attendance',
            'slug' => 'manage-learning-attendance',
            'module' => 'learning-attendance',
            'is_active' => true,
        ])->id);

        $user = User::create(['name' => 'Petugas '.$scopeType, 'email' => $scopeType.'@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, $scopeType, $scopeId);

        return $user;
    }

    /**
     * @param  array<string, int>  $placement
     */
    private function createGenerusAt(string $name, array $placement): Generus
    {
        $generus = Generus::create(['registration_number' => 'PPG-'.strtoupper($name), 'full_name' => $name, 'status' => 'active']);
        $generus->assignments()->create($placement + ['assigned_at' => '2026-01-01']);

        return $generus;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSession(Teacher $teacher, LearningMaterial $material, array $attributes): LearningSession
    {
        return LearningSession::create($attributes + ['teacher_id' => $teacher->id, 'material_id' => $material->id, 'session_date' => '2026-09-20']);
    }
}
