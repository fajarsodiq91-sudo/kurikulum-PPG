<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_region_from_master_data_page(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Master Data',
            'slug' => 'view-master-data',
            'module' => 'master-data',
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

        $this->actingAs($user)
            ->post('/master-data/regions', [
                'name' => 'Karawang Timur',
                'code' => 'KRT',
                'is_active' => true,
            ])
            ->assertRedirect('/master-data');

        $this->assertDatabaseHas('regions', [
            'name' => 'Karawang Timur',
            'code' => 'KRT',
        ]);
    }

    public function test_admin_can_create_dependent_master_data_from_central_page(): void
    {
        [$user, $region] = $this->createMasterDataUser();

        $villageResponse = $this->actingAs($user)->post('/master-data/villages', [
            'region_id' => $region->id,
            'name' => 'Desa A',
            'code' => 'DSA',
            'is_active' => true,
        ]);
        $villageResponse->assertRedirect('/master-data');

        $village = Village::firstOrFail();

        $this->actingAs($user)->post('/master-data/groups', [
            'village_id' => $village->id,
            'name' => 'Kelompok 1',
            'code' => 'K01',
            'is_active' => true,
        ])->assertRedirect('/master-data');

        $this->actingAs($user)->post('/master-data/levels', [
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ])->assertRedirect('/master-data');

        $this->actingAs($user)->post('/master-data/academic-years', [
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ])->assertRedirect('/master-data');

        $year = AcademicYear::firstOrFail();

        $this->actingAs($user)->post('/master-data/semesters', [
            'academic_year_id' => $year->id,
            'name' => 'Semester 1',
            'code' => 'S1',
            'sort_order' => 1,
            'is_active' => true,
        ])->assertRedirect('/master-data');

        $this->assertDatabaseHas('groups', ['name' => 'Kelompok 1', 'village_id' => $village->id]);
        $this->assertDatabaseHas('levels', ['code' => 'KELAS-1']);
        $this->assertDatabaseHas('semesters', ['code' => 'S1', 'academic_year_id' => $year->id]);
    }

    /**
     * @return array{0: User, 1: Region}
     */
    private function createMasterDataUser(): array
    {
        $role = Role::create(['name' => 'Master Data Admin', 'slug' => 'master-data-admin', 'is_active' => true]);
        $permission = Permission::create(['name' => 'View Master Data', 'slug' => 'view-master-data', 'module' => 'master-data', 'is_active' => true]);
        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Master Data Admin',
            'email' => 'master-data@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id);

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KRT', 'is_active' => true]);

        return [$user, $region];
    }
}
