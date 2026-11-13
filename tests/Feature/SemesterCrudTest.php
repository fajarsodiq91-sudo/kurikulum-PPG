<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SemesterCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_semester_with_a_code_already_used_in_the_same_academic_year_fails_validation_instead_of_500(): void
    {
        $user = $this->createAdminWithPermission('manage-master-data');
        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Genap',
            'code' => 'S2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester Ganjil',
            'code' => 'S1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('master-data.semesters.update', $semester), [
            'academic_year_id' => $academicYear->id,
            'name' => $semester->name,
            'code' => 'S2',
            'sort_order' => $semester->sort_order,
            'is_active' => '0',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNotEquals(500, $response->status());
        $this->assertSame('S1', $semester->refresh()->code);
    }

    public function test_updating_a_semester_keeping_its_own_code_succeeds(): void
    {
        $user = $this->createAdminWithPermission('manage-master-data');
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
            'code' => 'S1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('master-data.semesters.update', $semester), [
            'academic_year_id' => $academicYear->id,
            'name' => $semester->name,
            'code' => 'S1',
            'sort_order' => $semester->sort_order,
            'is_active' => '0',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('master-data.semesters.index'));
        $this->assertFalse((bool) $semester->refresh()->is_active);
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
}
