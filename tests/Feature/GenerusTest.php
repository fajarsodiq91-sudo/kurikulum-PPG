<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GenerusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_generus_with_assignment_history(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Generus',
            'slug' => 'manage-generus',
            'module' => 'generus',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $region = Region::create([
            'name' => 'Karawang Timur',
            'code' => 'KRT',
            'is_active' => true,
        ]);

        $village = Village::create([
            'region_id' => $region->id,
            'name' => 'Desa A',
            'code' => 'DSA',
            'is_active' => true,
        ]);

        $group = Group::create([
            'village_id' => $village->id,
            'name' => 'Kelompok 1',
            'code' => 'K01',
            'is_active' => true,
        ]);

        $level = Level::create([
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/generus', [
                'registration_number' => 'PPG-001',
                'full_name' => 'Ayu Lestari',
                'gender' => 'perempuan',
                'birth_date' => '2014-05-12',
                'status' => 'active',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
                'academic_year_id' => $year->id,
                'assignment_status' => 'active',
                'notes' => 'Data awal generus',
            ])
            ->assertRedirect('/generus');

        $this->assertDatabaseHas('generus', [
            'registration_number' => 'PPG-001',
            'full_name' => 'Ayu Lestari',
        ]);

        $this->assertDatabaseHas('generus_assignments', [
            'status' => 'active',
        ]);
    }
}
