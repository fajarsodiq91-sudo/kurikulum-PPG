<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\Group;
use App\Models\GroupDefaultSchedule;
use App\Models\GroupDefaultScheduleException;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GroupDefaultScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_kelompok_executor_can_set_their_own_groups_weekly_pattern_and_mark_a_holiday(): void
    {
        [$level, $group] = $this->createContext();
        $pelaksana = $this->createPelaksanaKelompok($group);

        $this->actingAs($pelaksana)
            ->put('/group-default-schedules', [
                'level_id' => $level->id,
                'days_of_week' => [1, 2, 3, 4, 5],
                'start_date' => '2026-01-05',
                'end_date' => '2026-06-30',
                'start_time' => '16:00',
                'end_time' => '17:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('group_default_schedules', 1);
        $schedule = GroupDefaultSchedule::first();
        $this->assertSame($group->id, $schedule->group_id);
        $this->assertSame([1, 2, 3, 4, 5], $schedule->days_of_week);
        $this->assertTrue($schedule->occursOn(Carbon::parse('2026-01-05')));
        $this->assertFalse($schedule->occursOn(Carbon::parse('2026-01-04')));

        $this->actingAs($pelaksana)
            ->post("/group-default-schedules/{$schedule->id}/exceptions", [
                'exception_date' => '2026-01-05',
                'reason' => 'Libur Tahun Baru',
            ])
            ->assertRedirect();

        $this->assertSame(1, GroupDefaultScheduleException::where('group_default_schedule_id', $schedule->id)->whereDate('exception_date', '2026-01-05')->count());
        $this->assertFalse($schedule->fresh('exceptions')->occursOn(Carbon::parse('2026-01-05')));

        $exceptionId = GroupDefaultScheduleException::first()->id;
        $this->actingAs($pelaksana)->delete("/group-default-schedule-exceptions/{$exceptionId}")->assertRedirect();
        $this->assertDatabaseCount('group_default_schedule_exceptions', 0);
    }

    public function test_a_kelompok_executor_can_tag_a_schedule_with_material_classifications(): void
    {
        [$level, $group] = $this->createContext();
        $pelaksana = $this->createPelaksanaKelompok($group);

        $this->actingAs($pelaksana)
            ->put('/group-default-schedules', [
                'level_id' => $level->id,
                'days_of_week' => [1, 2, 3, 4, 5],
                'start_date' => '2026-01-05',
                'end_date' => '2026-06-30',
                'categories' => ['bacaan', 'hafalan'],
            ])
            ->assertRedirect();

        $schedule = GroupDefaultSchedule::first();
        $this->assertSame(['bacaan', 'hafalan'], $schedule->categories);

        $this->actingAs($pelaksana)->get('/group-default-schedules?level_id='.$level->id)
            ->assertOk()->assertSee('Bacaan')->assertSee('Hafalan');
    }

    public function test_a_user_without_the_permission_cannot_manage_a_group_default_schedule(): void
    {
        [$level] = $this->createContext();
        $user = User::create(['name' => 'Tanpa Izin', 'email' => 'tanpaizin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);

        $this->actingAs($user)->put('/group-default-schedules', [
            'level_id' => $level->id,
            'days_of_week' => [1],
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-30',
        ])->assertForbidden();
    }

    public function test_a_kelompok_executor_cannot_manage_another_groups_schedule_exceptions(): void
    {
        [$level, $group] = $this->createContext();
        $pelaksana = $this->createPelaksanaKelompok($group);

        $otherGroup = Group::create(['village_id' => $group->village_id, 'name' => 'Kelompok Lain', 'code' => 'LAIN', 'slug' => 'kelompok-lain', 'is_active' => true]);
        $academicYear = AcademicYear::first();
        $semester = Semester::first();

        $otherSchedule = GroupDefaultSchedule::create([
            'group_id' => $otherGroup->id,
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1],
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-30',
        ]);

        $this->actingAs($pelaksana)
            ->post("/group-default-schedules/{$otherSchedule->id}/exceptions", ['exception_date' => '2026-01-05'])
            ->assertForbidden();
    }

    public function test_a_teacher_can_set_the_default_schedule_for_their_own_group_restricted_to_their_classs_level(): void
    {
        [$level, $group] = $this->createContext();
        $otherLevel = Level::create(['name' => 'Pra Remaja', 'code' => 'PR', 'sort_order' => 2, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => 'Kelas KBM 1', 'code' => 'KBM1', 'sort_order' => 1, 'is_active' => true]);

        $teacher = Teacher::create([
            'registration_number' => 'GR-001',
            'name' => 'Pak Guru',
            'gender' => 'male',
            'group_id' => $group->id,
            'status' => 'active',
        ]);
        $teacher->classGrades()->attach($classGrade->id);
        $guru = User::where('username', 'GR-001')->first();

        $this->actingAs($guru)->get('/group-default-schedules')
            ->assertOk()->assertSee($level->name)->assertDontSee($otherLevel->name);

        $this->actingAs($guru)
            ->put('/group-default-schedules', [
                'level_id' => $level->id,
                'days_of_week' => [1, 2, 3],
                'start_date' => '2026-01-05',
                'end_date' => '2026-06-30',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('group_default_schedules', ['group_id' => $group->id, 'level_id' => $level->id]);

        $this->actingAs($guru)
            ->put('/group-default-schedules', [
                'level_id' => $otherLevel->id,
                'days_of_week' => [1],
                'start_date' => '2026-01-05',
                'end_date' => '2026-06-30',
            ])
            ->assertSessionHasErrors('level_id');
    }

    /**
     * @return array{0: Level, 1: Group}
     */
    private function createContext(): array
    {
        $level = Level::create(['name' => 'PAUD', 'code' => 'PD', 'sort_order' => 1, 'is_active' => true]);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'G',
            'sort_order' => 1,
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);

        return [$level, $group];
    }

    private function createPelaksanaKelompok(Group $group): User
    {
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok-'.$group->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-own-default-schedule'],
            ['name' => 'Manage Own Default Schedule', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $role->permissions()->attach($permission->id);

        $user = User::create(['name' => 'Pelaksana', 'email' => 'pelaksana-'.$group->id.'@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $group->id);

        return $user;
    }
}
