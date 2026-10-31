<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\DefaultSchedule;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_daerah_admin_can_generate_weeks_and_fill_in_the_optional_date(): void
    {
        $admin = $this->createAdminWithPermission('manage-learning-sessions');
        [$level, $academicYear, $semester] = $this->createContext();

        $this->actingAs($admin)
            ->post('/default-schedules/generate', [
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'week_count' => 20,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('default_schedules', 20);
        $this->assertDatabaseHas('default_schedules', [
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'week_number' => 20,
            'scheduled_date' => null,
        ]);

        $week = DefaultSchedule::where('week_number', 1)->first();

        $this->actingAs($admin)
            ->put('/default-schedules', [
                'level_id' => $level->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'weeks' => [
                    $week->id => ['scheduled_date' => '2026-01-05', 'day_of_week' => 1, 'start_time' => '16:00', 'end_time' => '17:30'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('2026-01-05', $week->fresh()->scheduled_date);
        $this->assertSame(1, $week->fresh()->day_of_week);
        $this->assertSame('16:00', $week->fresh()->start_time);
        $this->assertSame('17:30', $week->fresh()->end_time);

        // Regenerating does not wipe out the date already filled in.
        $this->actingAs($admin)->post('/default-schedules/generate', [
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'week_count' => 20,
        ]);
        $this->assertDatabaseHas('default_schedules', ['id' => $week->id, 'scheduled_date' => '2026-01-05']);
    }

    public function test_a_user_without_the_permission_cannot_manage_the_default_schedule(): void
    {
        $user = User::create(['name' => 'Tanpa Izin', 'email' => 'tanpaizin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        [$level, $academicYear, $semester] = $this->createContext();

        $this->actingAs($user)->post('/default-schedules/generate', [
            'level_id' => $level->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'week_count' => 5,
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
