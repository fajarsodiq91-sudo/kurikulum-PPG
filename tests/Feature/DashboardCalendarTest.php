<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGrade;
use App\Models\DefaultSchedule;
use App\Models\Group;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ppg_daerah_user_sees_the_default_schedule_pattern_and_can_toggle_a_holiday(): void
    {
        [$level, $academicYear, $semester] = $this->createActivePeriod();
        $schedule = DefaultSchedule::create([
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '17:00',
        ]);

        $user = $this->createUserWithGlobalPermission('manage-learning-sessions');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee('Kalender KBM')->assertSee($level->name);

        // Liburkan a date this pattern occurs on, chosen so it's a weekday regardless of today.
        $date = now()->next(Carbon::MONDAY)->toDateString();

        $this->actingAs($user)
            ->post(route('default-schedules.exceptions.store', $schedule), ['exception_date' => $date])
            ->assertRedirect();

        $this->assertDatabaseHas('default_schedule_exceptions', ['default_schedule_id' => $schedule->id]);

        $this->actingAs($user)->get('/dashboard?date='.$date)
            ->assertOk()->assertSee('Batalkan libur');
    }

    public function test_a_kelompok_user_sees_and_can_add_their_own_sessions_from_the_calendar(): void
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => 'Kelas KBM 3', 'code' => 'KBM3', 'sort_order' => 3, 'is_active' => true]);

        LearningSession::create([
            'level' => LearningSession::LEVEL_GROUP,
            'group_id' => $group->id,
            'village_id' => $village->id,
            'class_grade_id' => $classGrade->id,
            'session_date' => now()->toDateString(),
            'status' => 'scheduled',
        ]);

        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok-'.$group->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-own-learning-sessions'],
            ['name' => 'Manage Own Learning Sessions', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $viewDashboard = Permission::firstOrCreate(
            ['slug' => 'view-dashboard'],
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true],
        );
        $role->permissions()->attach([$permission->id, $viewDashboard->id]);
        $user = User::create(['name' => 'Ahmad', 'email' => 'ahmad@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $group->id);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()->assertSee('Kalender KBM')->assertSee('Jadwal KBM Kelompok')->assertSee($classGrade->name);
    }

    /**
     * @return array{0: Level, 1: AcademicYear, 2: Semester}
     */
    private function createActivePeriod(): array
    {
        $level = Level::create(['name' => 'Caberawit', 'code' => 'CB', 'sort_order' => 1, 'is_active' => true]);

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

        return [$level, $academicYear, $semester];
    }

    private function createUserWithGlobalPermission(string $permissionSlug): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin-'.$permissionSlug, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => $permissionSlug],
            ['name' => $permissionSlug, 'module' => $permissionSlug, 'is_active' => true],
        );
        $viewDashboard = Permission::firstOrCreate(
            ['slug' => 'view-dashboard'],
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true],
        );
        $role->permissions()->attach([$permission->id, $viewDashboard->id]);

        $user = User::create(['name' => 'Admin', 'email' => $permissionSlug.'@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }
}
