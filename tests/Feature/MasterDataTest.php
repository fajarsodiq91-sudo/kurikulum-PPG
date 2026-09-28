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

    public function test_admin_can_create_dependent_master_data_from_central_page(): void
    {
        [$user, $region] = $this->createMasterDataUser();

        $villageResponse = $this->actingAs($user)->post('/master-data/villages', [
            'name' => 'Desa A',
            'code' => 'DSA',
            'is_active' => true,
        ]);
        $villageResponse->assertRedirect('/master-data/villages');

        $village = Village::firstOrFail();
        $this->assertSame($region->id, $village->region_id);

        $this->actingAs($user)->post('/master-data/groups', [
            'village_id' => $village->id,
            'name' => 'Kelompok 1',
            'code' => 'K01',
            'is_active' => true,
        ])->assertRedirect('/master-data/groups');

        $this->actingAs($user)->post('/master-data/levels', [
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ])->assertRedirect('/master-data/levels');

        $this->actingAs($user)->post('/master-data/class-grades', [
            'name' => 'Kelas 4',
            'code' => 'SD-4',
            'sort_order' => 4,
            'is_active' => true,
        ])->assertRedirect('/master-data/class-grades');

        $this->actingAs($user)->post('/master-data/academic-years', [
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ])->assertRedirect('/master-data/academic-years');

        $year = AcademicYear::firstOrFail();

        $this->actingAs($user)->post('/master-data/semesters', [
            'academic_year_id' => $year->id,
            'name' => 'Semester 1',
            'code' => 'S1',
            'sort_order' => 1,
            'is_active' => true,
        ])->assertRedirect('/master-data/semesters');

        $this->assertDatabaseHas('groups', ['name' => 'Kelompok 1', 'village_id' => $village->id]);
        $this->assertDatabaseHas('levels', ['code' => 'KELAS-1']);
        $this->assertDatabaseHas('class_grades', ['code' => 'SD-4']);
        $this->assertDatabaseHas('semesters', ['code' => 'S1', 'academic_year_id' => $year->id]);
    }

    public function test_admin_can_update_village_from_its_master_data_page(): void
    {
        [$user, $region] = $this->createMasterDataUser();
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa A', 'code' => 'DSA', 'is_active' => true]);

        $this->actingAs($user)
            ->put("/master-data/villages/{$village->id}", ['name' => 'Desa B', 'code' => 'DSB', 'is_active' => false])
            ->assertRedirect('/master-data/villages');

        $this->assertDatabaseHas('villages', ['id' => $village->id, 'name' => 'Desa B', 'is_active' => false, 'region_id' => $region->id]);
    }

    public function test_master_data_root_redirects_to_villages_and_each_page_renders(): void
    {
        [$user] = $this->createMasterDataUser();

        $this->actingAs($user)->get('/master-data')->assertRedirect('/master-data/villages');

        foreach (['villages', 'groups', 'levels', 'class-grades', 'academic-years', 'semesters'] as $page) {
            $this->actingAs($user)->get("/master-data/{$page}")->assertOk();
        }
    }

    /**
     * @return array{0: User, 1: Region}
     */
    private function createMasterDataUser(): array
    {
        $role = Role::create(['name' => 'Master Data Admin', 'slug' => 'master-data-admin', 'is_active' => true]);
        foreach (['view-master-data', 'manage-master-data'] as $slug) {
            $role->permissions()->attach(Permission::create(['name' => $slug, 'slug' => $slug, 'module' => 'master-data', 'is_active' => true])->id);
        }

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
