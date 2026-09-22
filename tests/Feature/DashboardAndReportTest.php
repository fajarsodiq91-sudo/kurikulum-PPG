<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Communication;
use App\Models\Generus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_live_summary_and_report_page_is_available(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $dashboardPermission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ]);

        $reportPermission = Permission::create([
            'name' => 'View Reports',
            'slug' => 'view-reports',
            'module' => 'reports',
            'is_active' => true,
        ]);

        $generusPermission = Permission::create([
            'name' => 'Manage Generus',
            'slug' => 'manage-generus',
            'module' => 'generus',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$dashboardPermission->id, $reportPermission->id, $generusPermission->id]);

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

        Generus::create([
            'registration_number' => 'GEN-001',
            'full_name' => 'Generus Satu',
            'gender' => 'Laki-laki',
            'status' => 'active',
        ]);

        Generus::create([
            'registration_number' => 'GEN-002',
            'full_name' => 'Generus Dua',
            'gender' => 'Perempuan',
            'status' => 'active',
        ]);

        Teacher::create([
            'name' => 'Guru Satu',
            'gender' => 'Laki-laki',
            'phone' => '081111111',
            'status' => 'active',
        ]);

        Teacher::create([
            'name' => 'Guru Dua',
            'gender' => 'Perempuan',
            'phone' => '082222222',
            'status' => 'active',
        ]);

        $training = Training::create([
            'title' => 'Pelatihan Kinerja',
            'type' => 'training',
            'academic_year_id' => $academicYear->id,
            'scheduled_at' => '2026-10-02',
            'location' => 'Karawang',
            'status' => 'scheduled',
        ]);

        Communication::create([
            'training_id' => $training->id,
            'channel' => 'whatsapp',
            'subject' => 'Pengingat',
            'message' => 'Jangan lupa hadir',
            'status' => 'sent',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('2')
            ->assertSee('Generus')
            ->assertSee('Data Generus')
            ->assertSee(route('generus.index'))
            ->assertSee('2')
            ->assertSee('Guru');

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Laporan')
            ->assertSee('Pelatihan');
    }
}
