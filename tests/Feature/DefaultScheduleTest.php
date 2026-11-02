<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DefaultSchedule;
use App\Models\DefaultScheduleException;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_daerah_admin_can_set_the_weekly_pattern_and_mark_a_holiday(): void
    {
        $admin = $this->createAdminWithPermission('manage-learning-sessions');
        [$level, $academicYear, $semester] = $this->createContext();

        $this->actingAs($admin)
            ->put('/default-schedules', [
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'days_of_week' => [1, 2, 3, 4, 5],
                'start_date' => '2026-01-05',
                'end_date' => '2026-06-30',
                'start_time' => '16:00',
                'end_time' => '17:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('default_schedules', 1);
        $schedule = DefaultSchedule::first();
        $this->assertSame([1, 2, 3, 4, 5], $schedule->days_of_week);
        $this->assertTrue($schedule->occursOn(Carbon::parse('2026-01-05')));
        $this->assertFalse($schedule->occursOn(Carbon::parse('2026-01-04')));

        $this->actingAs($admin)
            ->post("/default-schedules/{$schedule->id}/exceptions", [
                'exception_date' => '2026-01-05',
                'reason' => 'Libur Tahun Baru',
            ])
            ->assertRedirect();

        $this->assertSame(1, DefaultScheduleException::where('default_schedule_id', $schedule->id)->whereDate('exception_date', '2026-01-05')->count());
        $this->assertFalse($schedule->fresh('exceptions')->occursOn(Carbon::parse('2026-01-05')));

        $exceptionId = DefaultScheduleException::first()->id;
        $this->actingAs($admin)->delete("/default-schedule-exceptions/{$exceptionId}")->assertRedirect();
        $this->assertDatabaseCount('default_schedule_exceptions', 0);
    }

    public function test_a_user_without_the_permission_cannot_manage_the_default_schedule(): void
    {
        $user = User::create(['name' => 'Tanpa Izin', 'email' => 'tanpaizin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        [$level, $academicYear, $semester] = $this->createContext();

        $this->actingAs($user)->put('/default-schedules', [
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'days_of_week' => [1],
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-30',
        ])->assertForbidden();
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

    /**
     * @return array{0: Level, 1: AcademicYear, 2: Semester}
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

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'G',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return [$level, $academicYear, $semester];
    }
}
