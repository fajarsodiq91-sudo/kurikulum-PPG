<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassGrade;
use App\Models\DefaultSchedule;
use App\Models\Group;
use App\Models\GroupDefaultSchedule;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\Musyawaroh;
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
            ->assertOk()->assertSee('Kalender KBM')->assertSee('Jadwal KBM Anda')->assertSee($classGrade->name);
    }

    public function test_a_desa_user_can_create_and_see_their_own_village_level_session_from_the_calendar(): void
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);

        $role = Role::create(['name' => 'Perwakilan Desa', 'slug' => 'perwakilan-desa-'.$village->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-own-learning-sessions'],
            ['name' => 'Manage Own Learning Sessions', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $viewDashboard = Permission::firstOrCreate(
            ['slug' => 'view-dashboard'],
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true],
        );
        $role->permissions()->attach([$permission->id, $viewDashboard->id]);
        $user = User::create(['name' => 'Siti', 'email' => 'siti@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'village', $village->id);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()->assertSee('Jadwal KBM Anda')->assertSee('+ Tambah Jadwal Desa');

        $this->actingAs($user)
            ->post('/learning-sessions/own-village', [
                'village_id' => $village->id,
                'session_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_sessions', [
            'level' => LearningSession::LEVEL_VILLAGE,
            'village_id' => $village->id,
            'group_id' => null,
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()->assertSee('Isi Daftar Hadir &amp; Jurnal', false);
    }

    public function test_a_desa_user_cannot_create_a_session_for_another_villages_scope(): void
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $otherVillage = Village::create(['region_id' => $region->id, 'name' => 'Desa Lain', 'code' => 'DL', 'slug' => 'desa-lain', 'is_active' => true]);

        $role = Role::create(['name' => 'Perwakilan Desa', 'slug' => 'perwakilan-desa-'.$village->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-own-learning-sessions'],
            ['name' => 'Manage Own Learning Sessions', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $role->permissions()->attach($permission->id);
        $user = User::create(['name' => 'Siti', 'email' => 'siti@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'village', $village->id);

        $this->actingAs($user)
            ->post('/learning-sessions/own-village', [
                'village_id' => $otherVillage->id,
                'session_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('village_id');
    }

    public function test_a_global_viewer_can_pick_a_group_and_see_its_own_schedule_override_the_daerah_one(): void
    {
        [$group, $level] = $this->createGroupWithOwnSchedule();
        $user = $this->createUserWithGlobalPermission('view-learning-sessions');

        $this->actingAs($user)->get('/dashboard?group_id='.$group->id)
            ->assertOk()
            ->assertSee('Kelompok')
            ->assertSee($group->name)
            ->assertSee($level->name);
    }

    public function test_a_groups_own_schedule_overrides_the_daerah_schedule_for_the_same_level_but_not_others(): void
    {
        [$group, $level, $village] = $this->createGroupWithOwnSchedule();
        $academicYear = AcademicYear::first();
        $semester = Semester::first();
        $otherLevel = Level::create(['name' => 'Pra Remaja', 'code' => 'PR', 'sort_order' => 2, 'is_active' => true]);

        DefaultSchedule::create([
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'start_time' => '15:00',
            'end_time' => '16:00',
        ]);
        DefaultSchedule::create([
            'level_id' => $otherLevel->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $user = $this->createUserWithGlobalPermission('view-learning-sessions');

        $response = $this->actingAs($user)->get('/dashboard?group_id='.$group->id);

        // The group's own pattern for $level (09:00-...no time set) wins over the Daerah one (15:00-16:00),
        // while the Daerah pattern for $otherLevel (09:00-10:00, no group override) still shows.
        $response->assertOk()->assertDontSee('15:00–16:00')->assertSee('09:00–10:00')->assertSee($otherLevel->name);
    }

    public function test_a_village_scoped_viewer_only_sees_their_own_villages_groups_to_pick_from(): void
    {
        [$group, $level, $village] = $this->createGroupWithOwnSchedule();

        $otherRegion = Region::create(['name' => 'Daerah Lain', 'code' => 'RL', 'slug' => 'daerah-lain', 'is_active' => true]);
        $otherVillage = Village::create(['region_id' => $otherRegion->id, 'name' => 'Desa Lain', 'code' => 'DL', 'slug' => 'desa-lain', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $otherVillage->id, 'name' => 'Kelompok Lain', 'code' => 'LAIN', 'slug' => 'kelompok-lain', 'is_active' => true]);
        $academicYear = AcademicYear::first();
        $semester = Semester::first();
        GroupDefaultSchedule::create([
            'group_id' => $otherGroup->id,
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $role = Role::create(['name' => 'Perwakilan Desa', 'slug' => 'perwakilan-desa-'.$village->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'view-learning-sessions'],
            ['name' => 'View Learning Sessions', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $viewDashboard = Permission::firstOrCreate(
            ['slug' => 'view-dashboard'],
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true],
        );
        $role->permissions()->attach([$permission->id, $viewDashboard->id]);
        $user = User::create(['name' => 'Perwakilan Desa', 'email' => 'desa@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'village', $village->id);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee($group->name)->assertDontSee($otherGroup->name);
    }

    public function test_a_group_scoped_viewer_only_sees_their_own_group_to_pick_from(): void
    {
        [$group, $level] = $this->createGroupWithOwnSchedule();

        $otherVillage = Village::create(['region_id' => Region::first()->id, 'name' => 'Desa Lain', 'code' => 'DL', 'slug' => 'desa-lain', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $otherVillage->id, 'name' => 'Kelompok Lain', 'code' => 'LAIN', 'slug' => 'kelompok-lain', 'is_active' => true]);
        $academicYear = AcademicYear::first();
        $semester = Semester::first();
        GroupDefaultSchedule::create([
            'group_id' => $otherGroup->id,
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok-'.$group->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-own-default-schedule'],
            ['name' => 'Manage Own Default Schedule', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $viewDashboard = Permission::firstOrCreate(
            ['slug' => 'view-dashboard'],
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true],
        );
        $role->permissions()->attach([$permission->id, $viewDashboard->id]);
        $user = User::create(['name' => 'Pelaksana', 'email' => 'pelaksana@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $group->id);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee($group->name)->assertDontSee($otherGroup->name);
    }

    public function test_a_guest_does_not_see_any_kelompok_schedule(): void
    {
        [$group] = $this->createGroupWithOwnSchedule();

        $guestRole = Role::create(['name' => 'Tamu', 'slug' => 'guest', 'is_active' => true]);
        $guestRole->permissions()->attach(Permission::firstOrCreate(
            ['slug' => 'view-dashboard'],
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true],
        )->id);

        // No groups are in scope, so nothing to pick from and no group override applies.
        $this->get('/dashboard')->assertOk()->assertDontSee($group->name);
    }

    public function test_a_published_event_shows_on_the_calendar_on_its_event_date(): void
    {
        $user = $this->createUserWithGlobalPermission('manage-announcements');

        Announcement::create([
            'title' => 'Rapat Akbar Tahunan',
            'body' => 'Seluruh pengurus diharapkan hadir.',
            'status' => 'published',
            'published_at' => now(),
            'event_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()->assertSee('Rapat Akbar Tahunan');
    }

    public function test_a_musyawaroh_shows_on_the_calendar_and_the_days_agenda_popup_links_to_it(): void
    {
        $role = Role::create(['name' => 'PPG', 'slug' => 'ppg', 'is_active' => true]);
        $role->permissions()->attach([
            Permission::firstOrCreate(['slug' => 'manage-tasks'], ['name' => 'Manage Tasks', 'module' => 'tasks', 'is_active' => true])->id,
            Permission::firstOrCreate(['slug' => 'view-dashboard'], ['name' => 'View Dashboard', 'module' => 'dashboard', 'is_active' => true])->id,
        ]);
        $user = User::create(['name' => 'Admin PPG', 'email' => 'ppg-daerah@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        $musyawaroh = Musyawaroh::create([
            'title' => 'Musyawaroh Pengurus',
            'meeting_date' => now()->toDateString(),
            'created_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee('Musyawaroh Pengurus');
        // The day cell itself still navigates to the dashboard's own agenda popup for that date,
        // not straight to the musyawaroh - clicking a date must surface everything happening that
        // day, not just jump into one item.
        $response->assertSee('date='.now()->toDateString(), false);
        // The agenda popup (pre-rendered for today, since no ?date was given) links on to it.
        $response->assertSee(route('musyawaroh.show', $musyawaroh), false);
    }

    /**
     * @return array{0: Group, 1: Level, 2: Village}
     */
    private function createGroupWithOwnSchedule(): array
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);

        [$level, $academicYear, $semester] = $this->createActivePeriod();

        GroupDefaultSchedule::create([
            'group_id' => $group->id,
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1, 2, 3, 4, 5],
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        return [$group, $level, $village];
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
