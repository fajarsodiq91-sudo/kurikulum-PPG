<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\OrganizationUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Support\Sheets\AssignmentSheet;
use App\Support\Sheets\OrganizationUnitSheet;
use App\Support\Sheets\RoleSheet;
use App\Support\Sheets\UserSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SheetExportImportBatch2Test extends TestCase
{
    use RefreshDatabase;

    public function test_every_batch_two_module_can_be_exported_as_xlsx(): void
    {
        $user = $this->globalAdmin();

        OrganizationUnit::create(['name' => 'Bidang Kurikulum', 'code' => 'BK', 'unit_type' => 'bidang', 'status' => 'active']);
        $teacher = Teacher::create(['registration_number' => 'GR-FIX-001', 'name' => 'Guru Fixture', 'status' => 'active']);
        Assignment::create([
            'organization_unit_id' => OrganizationUnit::first()->id,
            'teacher_id' => $teacher->id,
            'assignment_title' => 'Koordinator',
            'assignment_type' => 'struktural',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $downloads = [
            '/organization-units/export' => 'struktur-organisasi.xlsx',
            '/assignments/export' => 'penempatan.xlsx',
            '/users/export' => 'pengguna.xlsx',
            '/roles/export' => 'peran.xlsx',
        ];

        foreach ($downloads as $url => $filename) {
            $this->actingAs($user)->get($url)->assertDownload($filename);
        }
    }

    public function test_organization_unit_sheet_import_creates_and_updates_by_code(): void
    {
        $sheet = new OrganizationUnitSheet;

        $sheet->importRow([
            'name' => 'Bidang Kurikulum',
            'code' => 'BK',
            'leader_name' => 'Fulan',
            'unit_type' => 'bidang',
            'status' => 'active',
            'description' => null,
        ]);

        $this->assertDatabaseHas('organization_units', ['code' => 'BK', 'leader_name' => 'Fulan']);

        $sheet->importRow([
            'name' => 'Bidang Kurikulum PPG',
            'code' => 'BK',
            'leader_name' => 'Fulan bin Fulan',
            'unit_type' => 'bidang',
            'status' => 'inactive',
            'description' => null,
        ]);

        $this->assertDatabaseCount('organization_units', 1);
        $this->assertDatabaseHas('organization_units', ['code' => 'BK', 'name' => 'Bidang Kurikulum PPG', 'status' => 'inactive']);
    }

    public function test_assignment_sheet_import_resolves_organization_unit_and_teacher_by_code(): void
    {
        $unit = OrganizationUnit::create(['name' => 'Bidang Kurikulum', 'code' => 'BK', 'unit_type' => 'bidang', 'status' => 'active']);
        $teacher = Teacher::create(['registration_number' => 'GR-010', 'name' => 'Guru Sepuluh', 'status' => 'active']);
        $sheet = new AssignmentSheet;

        $error = $sheet->importRow([
            'organization_unit_code' => 'BK',
            'teacher_registration_number' => 'GR-010',
            'assignment_title' => 'Koordinator',
            'assignment_type' => 'struktural',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'status' => 'active',
            'responsibility' => null,
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('assignments', ['organization_unit_id' => $unit->id, 'teacher_id' => $teacher->id, 'assignment_title' => 'Koordinator']);
    }

    public function test_assignment_sheet_import_rejects_unknown_organization_unit_or_teacher(): void
    {
        $sheet = new AssignmentSheet;

        $error = $sheet->importRow([
            'organization_unit_code' => 'TIDAK-ADA',
            'teacher_registration_number' => 'TIDAK-ADA',
            'assignment_title' => 'Koordinator',
            'assignment_type' => 'struktural',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_user_sheet_import_creates_a_new_user_with_a_password_and_updates_without_one(): void
    {
        $sheet = new UserSheet;

        $error = $sheet->importRow([
            'name' => 'Staff Baru',
            'email' => 'staff-baru@ppg.test',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('users', ['email' => 'staff-baru@ppg.test', 'name' => 'Staff Baru', 'status' => 'active']);

        $user = User::where('email', 'staff-baru@ppg.test')->first();
        $originalPassword = $user->password;

        $sheet->importRow([
            'name' => 'Staff Baru Diperbarui',
            'email' => 'staff-baru@ppg.test',
            'password' => null,
            'status' => 'inactive',
        ]);

        $user->refresh();
        $this->assertSame('Staff Baru Diperbarui', $user->name);
        $this->assertSame('inactive', $user->status);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_user_sheet_import_requires_a_password_for_a_new_user(): void
    {
        $sheet = new UserSheet;

        $error = $sheet->importRow([
            'name' => 'Tanpa Password',
            'email' => 'tanpa-password@ppg.test',
            'password' => null,
            'status' => 'active',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseMissing('users', ['email' => 'tanpa-password@ppg.test']);
    }

    public function test_role_sheet_import_resolves_permissions_by_slug_and_rejects_unknown_slugs(): void
    {
        $permission = Permission::create(['name' => 'Manage Teachers', 'slug' => 'manage-teachers', 'module' => 'teachers', 'is_active' => true]);
        $sheet = new RoleSheet;

        $error = $sheet->importRow([
            'name' => 'Pelaksana Guru',
            'slug' => 'pelaksana-guru',
            'description' => null,
            'status' => 'aktif',
            'permission_slugs' => 'manage-teachers',
        ]);

        $this->assertNull($error);
        $role = Role::where('slug', 'pelaksana-guru')->firstOrFail();
        $this->assertTrue($role->permissions->contains('id', $permission->id));

        $error = $sheet->importRow([
            'name' => 'Pelaksana Guru',
            'slug' => 'pelaksana-guru-2',
            'description' => null,
            'status' => 'aktif',
            'permission_slugs' => 'manage-tidak-ada',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseMissing('roles', ['slug' => 'pelaksana-guru-2']);
    }

    public function test_role_sheet_import_refuses_to_change_the_super_admin_role(): void
    {
        Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);
        $sheet = new RoleSheet;

        $error = $sheet->importRow([
            'name' => 'Super Admin Diubah',
            'slug' => 'super-admin',
            'description' => null,
            'status' => 'aktif',
            'permission_slugs' => '',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseHas('roles', ['slug' => 'super-admin', 'name' => 'Super Admin']);
    }

    private function globalAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);

        foreach (['manage-organization-units', 'manage-assignments', 'manage-users'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::create(['name' => 'Admin PPG', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }
}
