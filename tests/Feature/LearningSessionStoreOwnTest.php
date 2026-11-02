<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LearningSessionStoreOwnTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_kelompok_user_can_create_a_learning_session_for_their_own_group(): void
    {
        $group = $this->createGroup();
        $user = $this->createKelompokUser($group);

        $this->actingAs($user)
            ->post('/learning-sessions/own', [
                'group_id' => $group->id,
                'session_date' => '2026-03-02',
                'start_time' => '16:00',
                'end_time' => '17:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_sessions', [
            'group_id' => $group->id,
            'village_id' => $group->village_id,
            'level' => 'group',
            'session_date' => '2026-03-02',
            'status' => 'scheduled',
        ]);
    }

    public function test_a_kelompok_user_cannot_create_a_session_for_another_groups_id(): void
    {
        $group = $this->createGroup();
        $otherGroup = Group::create(['village_id' => $group->village_id, 'name' => 'Kelompok Lain', 'code' => 'KL', 'slug' => 'kelompok-lain', 'is_active' => true]);
        $user = $this->createKelompokUser($group);

        $this->actingAs($user)
            ->post('/learning-sessions/own', [
                'group_id' => $otherGroup->id,
                'session_date' => '2026-03-02',
            ])
            ->assertSessionHasErrors('group_id');

        $this->assertDatabaseCount('learning_sessions', 0);
    }

    public function test_a_user_without_the_permission_cannot_create_their_own_session(): void
    {
        $group = $this->createGroup();
        $user = User::create(['name' => 'Tanpa Izin', 'email' => 'tanpaizin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);

        $this->actingAs($user)
            ->post('/learning-sessions/own', [
                'group_id' => $group->id,
                'session_date' => '2026-03-02',
            ])
            ->assertForbidden();
    }

    private function createGroup(): Group
    {
        $region = Region::create(['name' => 'Daerah Test', 'code' => 'RT', 'slug' => 'daerah-test', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Test', 'code' => 'DT', 'slug' => 'desa-test', 'is_active' => true]);

        return Group::create(['village_id' => $village->id, 'name' => 'Kelompok HNH', 'code' => 'HNH', 'slug' => 'hnh', 'is_active' => true]);
    }

    private function createKelompokUser(Group $group): User
    {
        $role = Role::create(['name' => 'Pelaksana Kelompok', 'slug' => 'pelaksana-kelompok-'.$group->id, 'is_active' => true]);
        $permission = Permission::firstOrCreate(
            ['slug' => 'manage-own-learning-sessions'],
            ['name' => 'Manage Own Learning Sessions', 'module' => 'learning-sessions', 'is_active' => true],
        );
        $role->permissions()->attach($permission->id);

        $user = User::create(['name' => 'Ahmad', 'email' => 'ahmad@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'group', $group->id);

        return $user;
    }
}
