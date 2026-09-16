<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnualAuditAndProgressSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_annual_audit_and_progress_summary(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $auditPermission = Permission::create([
            'name' => 'Manage Annual Audit',
            'slug' => 'manage-annual-audit',
            'module' => 'annual-audit',
            'is_active' => true,
        ]);

        $role->permissions()->attach($auditPermission->id);

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
            'registration_number' => 'GEN-AUDIT-001',
            'full_name' => 'Generus Audit',
            'gender' => 'Laki-laki',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/annual-audits', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'audit_type' => 'annual-review',
                'overall_status' => 'good',
                'summary' => 'Generus menunjukkan perkembangan kuat pada seluruh indikator pembinaan.',
                'recommendation' => 'Lanjutkan penguatan aspek praktik dan konsistensi kehadiran.',
            ])
            ->assertRedirect('/annual-audits');

        $this->assertDatabaseHas('annual_audits', [
            'generus_id' => $generus->id,
            'audit_type' => 'annual-review',
            'overall_status' => 'good',
        ]);
    }
}
