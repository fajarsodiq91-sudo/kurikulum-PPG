<?php

namespace Tests\Feature;

use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\Group;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LearningSessionTodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_with_one_class_grade_gets_todays_session_automatically(): void
    {
        [$group, $classGrade] = $this->createPlacementAndClass();
        [$user, $teacher] = $this->createTeacherUser($group);
        $teacher->classGrades()->attach($classGrade->id);

        $this->actingAs($user)->get('/learning-sessions/today')->assertRedirect();

        $this->assertDatabaseCount('learning_sessions', 1);
        $this->assertDatabaseHas('learning_sessions', [
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'class_grade_id' => $classGrade->id,
            'session_date' => today()->toDateString(),
        ]);

        // Opening it again the same day does not create a duplicate session.
        $this->actingAs($user)->get('/learning-sessions/today')->assertRedirect();
        $this->assertDatabaseCount('learning_sessions', 1);
    }

    public function test_a_teacher_with_several_class_grades_must_pick_one_first(): void
    {
        [$group, $existingClassGrade] = $this->createPlacementAndClass();
        [$user, $teacher] = $this->createTeacherUser($group);
        $classGradeOne = $existingClassGrade;
        $classGradeTwo = ClassGrade::create(['level_id' => $classGradeOne->level_id, 'name' => 'Kelas KBM 4', 'code' => 'KBM4', 'sort_order' => 4, 'is_active' => true]);
        $teacher->classGrades()->attach([$classGradeOne->id, $classGradeTwo->id]);

        $this->actingAs($user)->get('/learning-sessions/today')
            ->assertOk()->assertSee('Kelas KBM 3')->assertSee('Kelas KBM 4');

        $this->assertDatabaseCount('learning_sessions', 0);

        $this->actingAs($user)->get('/learning-sessions/today?class_grade_id='.$classGradeTwo->id)->assertRedirect();
        $this->assertDatabaseHas('learning_sessions', ['teacher_id' => $teacher->id, 'class_grade_id' => $classGradeTwo->id]);
    }

    public function test_a_user_not_linked_to_a_teacher_cannot_open_todays_session(): void
    {
        [$group] = $this->createPlacementAndClass();
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok', 'is_active' => true]);
        $role->permissions()->attach(Permission::create([
            'name' => 'Manage Learning Attendance',
            'slug' => 'manage-learning-attendance',
            'module' => 'learning-attendance',
            'is_active' => true,
        ])->id);
        $user = User::create(['name' => 'Tanpa Guru', 'email' => 'tanpaguru@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $group->id);

        $this->actingAs($user)->get('/learning-sessions/today')->assertForbidden();
    }

    /**
     * @return array{0: Group, 1: ClassGrade}
     */
    private function createPlacementAndClass(): array
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);

        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => 'Kelas KBM 3', 'code' => 'KBM3', 'sort_order' => 3, 'is_active' => true]);

        Generus::create(['registration_number' => 'PPG-001', 'full_name' => 'Ahmad Junior', 'status' => 'active', 'learning_class_id' => $classGrade->id])
            ->assignments()->create(['village_id' => $village->id, 'group_id' => $group->id, 'assigned_at' => '2026-01-01']);

        return [$group, $classGrade];
    }

    /**
     * @return array{0: User, 1: Teacher}
     */
    private function createTeacherUser(Group $group): array
    {
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok-'.$group->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-learning-attendance'],
            ['name' => 'Manage Learning Attendance', 'module' => 'learning-attendance', 'is_active' => true],
        );
        $role->permissions()->attach($permission->id);

        $teacher = Teacher::create(['name' => 'Ahmad', 'status' => 'active', 'group_id' => $group->id]);

        $user = User::create(['name' => 'Ahmad', 'email' => 'ahmad@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $group->id);
        $user->forceFill(['teacher_id' => $teacher->id])->save();

        return [$user, $teacher];
    }
}
