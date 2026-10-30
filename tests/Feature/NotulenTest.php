<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Notulen;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotulenTest extends TestCase
{
    use RefreshDatabase;

    private Role $daerahRole;

    private Role $desaRole;

    private Role $kelompokRole;

    protected function setUp(): void
    {
        parent::setUp();

        $permission = Permission::create([
            'name' => 'Manage Tasks',
            'slug' => 'manage-tasks',
            'module' => 'tasks',
            'is_active' => true,
        ]);

        $this->daerahRole = Role::create(['name' => 'PPG', 'slug' => 'ppg', 'is_active' => true]);
        $this->desaRole = Role::create(['name' => 'Perwakilan PPG Desa', 'slug' => 'perwakilan-ppg-desa', 'is_active' => true]);
        $this->kelompokRole = Role::create(['name' => 'Pelaksana PPG Kelompok', 'slug' => 'pelaksana-ppg-kelompok', 'is_active' => true]);

        foreach ([$this->daerahRole, $this->desaRole, $this->kelompokRole] as $role) {
            $role->permissions()->attach($permission->id);
        }
    }

    private function makeUser(string $name, Role $role, string $scopeType, ?int $scopeId): User
    {
        $user = User::create([
            'name' => $name,
            'email' => str($name)->slug().'@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, $scopeType, $scopeId);

        return $user;
    }

    private function makeVillage(string $name): Village
    {
        return Village::create(['region_id' => Region::karawangTimur()->id, 'name' => $name, 'is_active' => true]);
    }

    private function makeGroup(Village $village, string $name): Group
    {
        return Group::create(['village_id' => $village->id, 'name' => $name, 'is_active' => true]);
    }

    public function test_a_notulen_can_be_created_without_turning_into_a_task(): void
    {
        $village = $this->makeVillage('Desa A');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);

        $this->actingAs($desaUser)->post('/notulen', [
            'title' => 'Hasil Musyawaroh Oktober',
            'content' => 'Disepakati jadwal kegiatan bulan depan.',
            'bucket' => 'Musyawaroh Pengurus',
            'meeting_date' => '2026-10-30',
        ])->assertRedirect('/tasks');

        $this->assertDatabaseHas('notulens', [
            'title' => 'Hasil Musyawaroh Oktober',
            'created_by_id' => $desaUser->id,
        ]);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_notulen_is_visible_within_the_creators_vertical_hierarchy_but_not_to_a_sibling_branch(): void
    {
        $villageA = $this->makeVillage('Desa A');
        $villageB = $this->makeVillage('Desa B');
        $groupA1 = $this->makeGroup($villageA, 'Kelompok A1');
        $groupB1 = $this->makeGroup($villageB, 'Kelompok B1');

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null);
        $desaAUser = $this->makeUser('Desa A', $this->desaRole, 'village', $villageA->id);
        $kelompokA1User = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $groupA1->id);
        $kelompokB1User = $this->makeUser('Kelompok B1', $this->kelompokRole, 'group', $groupB1->id);

        Notulen::create([
            'title' => 'Notulen Rapat Desa A',
            'content' => 'Catatan rapat.',
            'created_by_id' => $kelompokA1User->id,
        ]);

        $this->actingAs($kelompokA1User)->get('/tasks')->assertSee('Notulen Rapat Desa A');
        $this->actingAs($desaAUser)->get('/tasks')->assertSee('Notulen Rapat Desa A');
        $this->actingAs($daerahUser)->get('/tasks')->assertSee('Notulen Rapat Desa A');

        $this->actingAs($kelompokB1User)->get('/tasks')->assertDontSee('Notulen Rapat Desa A');
    }

    public function test_only_the_creator_can_delete_a_notulen(): void
    {
        $village = $this->makeVillage('Desa A');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $group = $this->makeGroup($village, 'Kelompok A1');
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $notulen = Notulen::create([
            'title' => 'Notulen Rapat',
            'content' => 'Catatan rapat.',
            'created_by_id' => $desaUser->id,
        ]);

        $this->actingAs($kelompokUser)->delete("/notulen/{$notulen->id}")->assertForbidden();
        $this->assertDatabaseHas('notulens', ['id' => $notulen->id]);

        $this->actingAs($desaUser)->delete("/notulen/{$notulen->id}")->assertRedirect('/tasks');
        $this->assertDatabaseMissing('notulens', ['id' => $notulen->id]);
    }
}
