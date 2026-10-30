<?php

namespace Tests\Feature;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_organization_unit(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Organization Units',
            'slug' => 'manage-organization-units',
            'module' => 'organization-units',
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
            ->post('/organization-units', [
                'name' => 'Bidang Kurikulum',
                'code' => 'KUR',
                'leader_name' => 'Bapak Ahmad',
                'unit_type' => 'bidang',
                'status' => 'active',
                'description' => 'Koordinasi program dan pembinaan kurikulum.',
            ])
            ->assertRedirect('/organization-units');

        $this->assertDatabaseHas('organization_units', [
            'name' => 'Bidang Kurikulum',
            'code' => 'KUR',
            'unit_type' => 'bidang',
        ]);
    }

    public function test_index_shows_active_pengurus_grouped_by_daerah_desa_and_kelompok(): void
    {
        $viewPermission = Permission::create([
            'name' => 'View Organization Units',
            'slug' => 'view-organization-units',
            'module' => 'organization-units',
            'is_active' => true,
        ]);

        $viewerRole = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'is_active' => true]);
        $viewerRole->permissions()->attach($viewPermission->id);

        $viewer = User::create([
            'name' => 'Peninjau',
            'email' => 'peninjau@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $viewer->assignRole($viewerRole->id, 'global', null);

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'DS1', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok A', 'code' => 'KA', 'is_active' => true]);

        $ppgRole = Role::create(['name' => 'Pengurus PPG Daerah', 'slug' => 'ppg', 'is_active' => true]);
        $desaRole = Role::create(['name' => 'Perwakilan PPG Desa', 'slug' => 'perwakilan-ppg-desa', 'is_active' => true]);
        $kelompokRole = Role::create(['name' => 'Pelaksana PPG Kelompok', 'slug' => 'pelaksana-ppg-kelompok', 'is_active' => true]);

        $ketuaDaerah = Dapukan::create(['role_slug' => 'ppg', 'name' => 'Ketua PPG', 'sort_order' => 1, 'can_approve' => true, 'is_active' => true]);
        $pembinaDesa = Dapukan::create(['role_slug' => 'perwakilan-ppg-desa', 'name' => 'Pembina Desa', 'sort_order' => 1, 'can_approve' => true, 'is_active' => true]);
        $guruGenerus = Dapukan::create(['role_slug' => 'pelaksana-ppg-kelompok', 'name' => 'Guru Generus', 'sort_order' => 1, 'can_approve' => false, 'is_active' => true]);

        $daerahUser = User::create(['name' => 'Ketua Daerah', 'email' => 'ketua@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $daerahUser->assignRole($ppgRole->id, 'global', null, $ketuaDaerah->id);

        $desaUser = User::create(['name' => 'Pembina Desa Satu', 'email' => 'desa@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $desaUser->assignRole($desaRole->id, 'village', $village->id, $pembinaDesa->id);

        $kelompokUser = User::create(['name' => 'Guru Kelompok A', 'email' => 'kelompok@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $kelompokUser->assignRole($kelompokRole->id, 'group', $group->id, $guruGenerus->id);

        $response = $this->actingAs($viewer)->get('/organization-units');

        $response->assertOk();
        $response->assertSeeText('Ketua Daerah');
        $response->assertSeeText('Pembina Desa Satu');
        $response->assertSeeText('Guru Kelompok A');
        $response->assertSeeText('Desa Satu');
        $response->assertSeeText('Kelompok A');
    }

    public function test_village_scoped_user_only_sees_the_daerah_chart_and_their_own_desa_branch(): void
    {
        $viewPermission = Permission::create(['name' => 'View Organization Units', 'slug' => 'view-organization-units', 'module' => 'organization-units', 'is_active' => true]);

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $villageOne = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'DS1', 'is_active' => true]);
        $villageTwo = Village::create(['region_id' => $region->id, 'name' => 'Desa Dua', 'code' => 'DS2', 'is_active' => true]);
        $groupOne = Group::create(['village_id' => $villageOne->id, 'name' => 'Kelompok A', 'code' => 'KA', 'is_active' => true]);
        $groupTwo = Group::create(['village_id' => $villageTwo->id, 'name' => 'Kelompok B', 'code' => 'KB', 'is_active' => true]);

        $ppgRole = Role::create(['name' => 'Pengurus PPG Daerah', 'slug' => 'ppg', 'is_active' => true]);
        $desaRole = Role::create(['name' => 'Perwakilan PPG Desa', 'slug' => 'perwakilan-ppg-desa', 'is_active' => true]);
        $kelompokRole = Role::create(['name' => 'Pelaksana PPG Kelompok', 'slug' => 'pelaksana-ppg-kelompok', 'is_active' => true]);
        $ppgRole->permissions()->attach($viewPermission->id);
        $desaRole->permissions()->attach($viewPermission->id);
        $kelompokRole->permissions()->attach($viewPermission->id);

        $ketuaDaerah = Dapukan::create(['role_slug' => 'ppg', 'name' => 'Ketua PPG', 'sort_order' => 1, 'can_approve' => true, 'is_active' => true]);
        $pembinaDesa = Dapukan::create(['role_slug' => 'perwakilan-ppg-desa', 'name' => 'Pembina Desa', 'sort_order' => 1, 'can_approve' => true, 'is_active' => true]);
        $guruGenerus = Dapukan::create(['role_slug' => 'pelaksana-ppg-kelompok', 'name' => 'Guru Generus', 'sort_order' => 1, 'can_approve' => false, 'is_active' => true]);

        $daerahUser = User::create(['name' => 'Ketua Daerah', 'email' => 'ketua@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $daerahUser->assignRole($ppgRole->id, 'global', null, $ketuaDaerah->id);

        $desaOneUser = User::create(['name' => 'Pembina Desa Satu', 'email' => 'desa1@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $desaOneUser->assignRole($desaRole->id, 'village', $villageOne->id, $pembinaDesa->id);

        $desaTwoUser = User::create(['name' => 'Pembina Desa Dua', 'email' => 'desa2@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $desaTwoUser->assignRole($desaRole->id, 'village', $villageTwo->id, $pembinaDesa->id);

        $kelompokAUser = User::create(['name' => 'Guru Kelompok A', 'email' => 'kelompoka@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $kelompokAUser->assignRole($kelompokRole->id, 'group', $groupOne->id, $guruGenerus->id);

        $kelompokBUser = User::create(['name' => 'Guru Kelompok B', 'email' => 'kelompokb@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $kelompokBUser->assignRole($kelompokRole->id, 'group', $groupTwo->id, $guruGenerus->id);

        $response = $this->actingAs($desaOneUser)->get('/organization-units');

        $response->assertOk();
        $response->assertSeeText('Ketua Daerah');
        $response->assertSeeText('Desa Satu');
        $response->assertSeeText('Pembina Desa Satu');
        $response->assertSeeText('Kelompok A');
        $response->assertSeeText('Guru Kelompok A');
        $response->assertDontSeeText('Desa Dua');
        $response->assertDontSeeText('Pembina Desa Dua');
        $response->assertDontSeeText('Kelompok B');
        $response->assertDontSeeText('Guru Kelompok B');
    }

    public function test_group_scoped_user_only_sees_the_daerah_chart_and_their_own_desa_branch(): void
    {
        $viewPermission = Permission::create(['name' => 'View Organization Units', 'slug' => 'view-organization-units', 'module' => 'organization-units', 'is_active' => true]);

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $villageOne = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'DS1', 'is_active' => true]);
        $villageTwo = Village::create(['region_id' => $region->id, 'name' => 'Desa Dua', 'code' => 'DS2', 'is_active' => true]);
        $groupOne = Group::create(['village_id' => $villageOne->id, 'name' => 'Kelompok A', 'code' => 'KA', 'is_active' => true]);
        $groupTwo = Group::create(['village_id' => $villageTwo->id, 'name' => 'Kelompok B', 'code' => 'KB', 'is_active' => true]);

        $kelompokRole = Role::create(['name' => 'Pelaksana PPG Kelompok', 'slug' => 'pelaksana-ppg-kelompok', 'is_active' => true]);
        $kelompokRole->permissions()->attach($viewPermission->id);
        $guruGenerus = Dapukan::create(['role_slug' => 'pelaksana-ppg-kelompok', 'name' => 'Guru Generus', 'sort_order' => 1, 'can_approve' => false, 'is_active' => true]);

        $kelompokAUser = User::create(['name' => 'Guru Kelompok A', 'email' => 'kelompoka@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $kelompokAUser->assignRole($kelompokRole->id, 'group', $groupOne->id, $guruGenerus->id);

        $kelompokBUser = User::create(['name' => 'Guru Kelompok B', 'email' => 'kelompokb@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $kelompokBUser->assignRole($kelompokRole->id, 'group', $groupTwo->id, $guruGenerus->id);

        $response = $this->actingAs($kelompokAUser)->get('/organization-units');

        $response->assertOk();
        $response->assertSeeText('Desa Satu');
        $response->assertSeeText('Kelompok A');
        $response->assertSeeText('Guru Kelompok A');
        $response->assertDontSeeText('Desa Dua');
        $response->assertDontSeeText('Kelompok B');
        $response->assertDontSeeText('Guru Kelompok B');
    }
}
