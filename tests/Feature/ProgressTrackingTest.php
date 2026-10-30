<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Permission;
use App\Models\ProgressTrack;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_progress_tracking_record_for_generus(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $progressPermission = Permission::create([
            'name' => 'Manage Progress Tracking',
            'slug' => 'manage-progress-tracking',
            'module' => 'progress-tracking',
            'is_active' => true,
        ]);

        $role->permissions()->attach($progressPermission->id);

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
            'code' => 'Ganjil',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generus = Generus::create([
            'registration_number' => 'GEN-PROGRESS-001',
            'full_name' => 'Generus Progress',
            'gender' => 'Perempuan',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/progress-tracks', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'period_label' => 'Minggu 1',
                'overall_status' => 'good',
                'score' => 84.5,
                'notes' => 'Progress stabil dan konsisten mengikuti pembinaan.',
                'next_goal' => 'Meningkatkan keaktifan pada sesi praktik.',
            ])
            ->assertRedirect('/progress-tracks');

        $this->assertDatabaseHas('progress_tracks', [
            'generus_id' => $generus->id,
            'period_label' => 'Minggu 1',
            'overall_status' => 'good',
        ]);
    }

    public function test_progress_track_index_renders_edit_and_delete_actions(): void
    {
        [$user, $academicYear, $semester, $generus] = $this->createScenario();

        ProgressTrack::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'period_label' => 'Minggu 1',
            'overall_status' => 'good',
            'score' => 84.5,
        ]);

        $this->actingAs($user)
            ->get('/progress-tracks')
            ->assertOk()
            ->assertSee('data-edit-progress-track', false);
    }

    public function test_admin_can_update_progress_tracking_record(): void
    {
        [$user, $academicYear, $semester, $generus] = $this->createScenario();

        $track = ProgressTrack::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'period_label' => 'Minggu 1',
            'overall_status' => 'good',
            'score' => 84.5,
        ]);

        $this->actingAs($user)
            ->put("/progress-tracks/{$track->id}", [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'period_label' => 'Minggu 2',
                'overall_status' => 'average',
                'score' => 70,
            ])
            ->assertRedirect('/progress-tracks');

        $this->assertDatabaseHas('progress_tracks', [
            'id' => $track->id,
            'period_label' => 'Minggu 2',
            'overall_status' => 'average',
        ]);
    }

    public function test_admin_can_delete_progress_tracking_record(): void
    {
        [$user, $academicYear, $semester, $generus] = $this->createScenario();

        $track = ProgressTrack::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'period_label' => 'Minggu 1',
            'overall_status' => 'good',
            'score' => 84.5,
        ]);

        $this->actingAs($user)
            ->delete("/progress-tracks/{$track->id}")
            ->assertRedirect('/progress-tracks');

        $this->assertDatabaseMissing('progress_tracks', ['id' => $track->id]);
    }

    /**
     * @return array{0: User, 1: AcademicYear, 2: Semester, 3: Generus}
     */
    private function createScenario(): array
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $progressPermission = Permission::create([
            'name' => 'Manage Progress Tracking',
            'slug' => 'manage-progress-tracking',
            'module' => 'progress-tracking',
            'is_active' => true,
        ]);

        $role->permissions()->attach($progressPermission->id);

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
            'code' => 'Ganjil',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $generus = Generus::create([
            'registration_number' => 'GEN-PROGRESS-001',
            'full_name' => 'Generus Progress',
            'gender' => 'Perempuan',
            'status' => 'active',
        ]);

        return [$user, $academicYear, $semester, $generus];
    }
}
