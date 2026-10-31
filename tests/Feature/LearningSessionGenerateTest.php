<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Group;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use App\Models\Level;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningSessionGenerateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_the_same_weekly_schedule_into_every_active_group(): void
    {
        $user = $this->createAdminWithPermission('manage-learning-sessions');
        $material = $this->createMaterial(week: 3);
        [$villageOne, $groupOne] = $this->createVillageAndGroup('Desa A', 'DA', 'Kelompok 1', 'K1');
        [, $groupTwo] = $this->createVillageAndGroup('Desa B', 'DB', 'Kelompok 2', 'K2');
        Group::create(['village_id' => $villageOne->id, 'name' => 'Kelompok Nonaktif', 'code' => 'K3', 'slug' => 'k3', 'is_active' => false]);

        $this->actingAs($user)
            ->post('/learning-sessions/generate', [
                'material_id' => $material->id,
                'session_date' => '2026-01-19',
                'level' => 'group',
                'status' => 'scheduled',
            ])
            ->assertRedirect('/learning-sessions');

        $classGradeId = $material->materialChapter->materialCategory->class_grade_id;

        $this->assertDatabaseCount('learning_sessions', 2);
        $this->assertDatabaseHas('learning_sessions', [
            'material_id' => $material->id,
            'class_grade_id' => $classGradeId,
            'group_id' => $groupOne->id,
            'village_id' => $villageOne->id,
            'session_date' => '2026-01-19',
            'teacher_id' => null,
            'status' => 'scheduled',
        ]);
        $this->assertDatabaseHas('learning_sessions', [
            'material_id' => $material->id,
            'group_id' => $groupTwo->id,
        ]);
    }

    public function test_regenerating_updates_the_date_without_wiping_a_group_that_already_filled_its_teacher(): void
    {
        $admin = $this->createAdminWithPermission('manage-learning-sessions');
        $material = $this->createMaterial(week: 1);
        [, $group] = $this->createVillageAndGroup('Desa A', 'DA', 'Kelompok 1', 'K1');
        $teacher = Teacher::create(['name' => 'Guru Satu', 'status' => 'active']);

        $this->actingAs($admin)->post('/learning-sessions/generate', [
            'material_id' => $material->id,
            'session_date' => '2026-01-19',
            'level' => 'group',
            'status' => 'scheduled',
        ]);

        $session = LearningSession::where('group_id', $group->id)->firstOrFail();
        $session->update(['teacher_id' => $teacher->id, 'start_time' => '08:00', 'end_time' => '09:30']);

        $this->actingAs($admin)->post('/learning-sessions/generate', [
            'material_id' => $material->id,
            'session_date' => '2026-01-26',
            'level' => 'group',
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseCount('learning_sessions', 1);
        $this->assertDatabaseHas('learning_sessions', [
            'id' => $session->id,
            'session_date' => '2026-01-26',
            'teacher_id' => $teacher->id,
            'start_time' => '08:00',
        ]);
    }

    public function test_group_scoped_user_can_complete_only_their_own_generated_session(): void
    {
        $admin = $this->createAdminWithPermission('manage-learning-sessions');
        $material = $this->createMaterial(week: 2);
        [, $ownGroup] = $this->createVillageAndGroup('Desa A', 'DA', 'Kelompok 1', 'K1');
        [, $otherGroup] = $this->createVillageAndGroup('Desa B', 'DB', 'Kelompok 2', 'K2');

        $this->actingAs($admin)->post('/learning-sessions/generate', [
            'material_id' => $material->id,
            'session_date' => '2026-01-19',
            'level' => 'group',
            'status' => 'scheduled',
        ]);

        $ownSession = LearningSession::where('group_id', $ownGroup->id)->firstOrFail();
        $otherSession = LearningSession::where('group_id', $otherGroup->id)->firstOrFail();

        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok', 'is_active' => true]);
        $role->permissions()->attach(Permission::create([
            'name' => 'Manage Learning Attendance',
            'slug' => 'manage-learning-attendance',
            'module' => 'learning-attendance',
            'is_active' => true,
        ])->id);

        $kelompokUser = User::create(['name' => 'Pelaksana', 'email' => 'pelaksana@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $kelompokUser->assignRole($role->id, 'group', $ownGroup->id);

        $teacher = Teacher::create(['name' => 'Guru Kelompok', 'status' => 'active']);

        $this->actingAs($kelompokUser)
            ->get("/learning-sessions/{$ownSession->id}/edit")
            ->assertOk();

        $this->actingAs($kelompokUser)
            ->put("/learning-sessions/{$ownSession->id}", [
                'teacher_id' => $teacher->id,
                'start_time' => '08:00',
                'end_time' => '09:30',
                'status' => 'completed',
            ])
            ->assertRedirect('/learning-sessions');

        $this->assertDatabaseHas('learning_sessions', [
            'id' => $ownSession->id,
            'teacher_id' => $teacher->id,
            'status' => 'completed',
        ]);

        $this->actingAs($kelompokUser)
            ->get("/learning-sessions/{$otherSession->id}/edit")
            ->assertForbidden();

        $this->actingAs($kelompokUser)
            ->put("/learning-sessions/{$otherSession->id}", [
                'teacher_id' => $teacher->id,
                'status' => 'completed',
            ])
            ->assertForbidden();
    }

    private function createAdminWithPermission(string $permissionSlug): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $role->permissions()->attach(Permission::create([
            'name' => $permissionSlug,
            'slug' => $permissionSlug,
            'module' => $permissionSlug,
            'is_active' => true,
        ])->id);

        $user = User::create(['name' => 'Admin PPG', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function createMaterial(int $week): LearningMaterial
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

        $level = Level::create(['name' => 'Kelas 1', 'code' => 'KELAS-1', 'sort_order' => 1, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => 'Kelas 1', 'code' => 'KELAS-1', 'sort_order' => 1, 'is_active' => true]);

        $category = MaterialCategory::create([
            'class_grade_id' => $classGrade->id,
            'semester_id' => $semester->id,
            'name' => 'Pendidikan Karakter',
            'code' => 'PK-01-'.$week,
            'is_active' => true,
        ]);

        $chapter = MaterialChapter::create([
            'material_category_id' => $category->id,
            'name' => 'Bab Etika',
            'code' => 'BAB-ETIKA-'.$week,
            'is_active' => true,
        ]);

        return LearningMaterial::create([
            'material_chapter_id' => $chapter->id,
            'title' => 'Modul Minggu '.$week,
            'code' => 'MAT-'.$week,
            'week_number' => $week,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Village, 1: Group}
     */
    private function createVillageAndGroup(string $villageName, string $villageCode, string $groupName, string $groupCode): array
    {
        $region = Region::firstOrCreate(['code' => 'RT'], ['name' => 'Daerah Test', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => $villageName, 'code' => $villageCode, 'slug' => Str::slug($villageName), 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => $groupName, 'code' => $groupCode, 'slug' => Str::slug($groupName), 'is_active' => true]);

        return [$village, $group];
    }
}
