<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\Permission;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunaqosahAndReportCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_munaqosah_and_report_card(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $munaqosahPermission = Permission::create([
            'name' => 'Manage Munaqosah',
            'slug' => 'manage-munaqosah',
            'module' => 'munaqosah',
            'is_active' => true,
        ]);

        $reportCardPermission = Permission::create([
            'name' => 'Manage Report Card',
            'slug' => 'manage-report-cards',
            'module' => 'report-card',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$munaqosahPermission->id, $reportCardPermission->id]);

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
            'registration_number' => 'GEN-2026-001',
            'full_name' => 'Generus Munaqosah',
            'gender' => 'Laki-laki',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/munaqosahs', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => 'Munaqosah Semester Ganjil',
                'type' => 'semester-final',
                'score' => 87.5,
                'result' => 'Lulus',
                'status' => 'completed',
                'notes' => 'Kompeten pada materi kurikulum dasar',
            ])
            ->assertRedirect('/munaqosahs');

        $this->assertDatabaseHas('munaqosahs', [
            'generus_id' => $generus->id,
            'title' => 'Munaqosah Semester Ganjil',
            'result' => 'Lulus',
        ]);

        $this->actingAs($user)
            ->post('/report-cards', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'final_score' => 88.5,
                'predicate' => 'A',
                'recommendation' => 'Tetap konsisten dan tingkatkan praktik pembelajaran.',
                'remarks' => 'Sangat baik',
            ])
            ->assertRedirect('/report-cards');

        $this->assertDatabaseHas('report_cards', [
            'generus_id' => $generus->id,
            'predicate' => 'A',
            'remarks' => 'Sangat baik',
        ]);
    }

    public function test_second_report_card_for_same_generus_and_semester_is_rejected(): void
    {
        $user = $this->createAdminWithPermission('manage-report-cards');
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
        $generus = $this->createGenerus();
        ReportCard::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
        ]);

        $this->actingAs($user)
            ->from('/report-cards')
            ->post('/report-cards', [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
            ])
            ->assertRedirect('/report-cards')
            ->assertSessionHasErrors(['generus_id' => 'Generus ini sudah memiliki rapor untuk tahun ajaran dan semester tersebut.']);

        $this->assertDatabaseCount('report_cards', 1);
    }

    private function createAdminWithPermission(string $permissionSlug): User
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $role->permissions()->attach(Permission::create([
            'name' => $permissionSlug,
            'slug' => $permissionSlug,
            'module' => $permissionSlug,
            'is_active' => true,
        ])->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function createGenerus(): Generus
    {
        return Generus::create([
            'registration_number' => 'PPG-DUP-001',
            'full_name' => 'Rina Maulida',
            'status' => 'active',
        ]);
    }

    public function test_report_card_can_be_updated_and_deleted(): void
    {
        $user = $this->createAdminWithPermission('manage-report-cards');
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
        $generus = $this->createGenerus();
        $reportCard = ReportCard::create([
            'generus_id' => $generus->id,
            'academic_year_id' => $academicYear->id,
            'semester_id' => $semester->id,
            'predicate' => 'B',
        ]);

        $this->actingAs($user)->get("/report-cards/{$reportCard->id}/edit")->assertOk();

        $this->actingAs($user)
            ->put("/report-cards/{$reportCard->id}", [
                'generus_id' => $generus->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'predicate' => 'A',
            ])
            ->assertRedirect('/report-cards')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('report_cards', ['id' => $reportCard->id, 'predicate' => 'A']);

        $this->actingAs($user)
            ->delete("/report-cards/{$reportCard->id}")
            ->assertRedirect('/report-cards');

        $this->assertModelMissing($reportCard);
    }
}
