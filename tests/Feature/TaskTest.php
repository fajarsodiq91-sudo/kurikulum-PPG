<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskTest extends TestCase
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

    public function test_desa_can_assign_task_to_own_kelompok_and_daerah_but_not_other_desa(): void
    {
        $villageA = $this->makeVillage('Desa A');
        $villageB = $this->makeVillage('Desa B');
        $groupA1 = $this->makeGroup($villageA, 'Kelompok A1');
        $groupB1 = $this->makeGroup($villageB, 'Kelompok B1');

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null);
        $desaAUser = $this->makeUser('Desa A', $this->desaRole, 'village', $villageA->id);
        $kelompokA1User = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $groupA1->id);
        $kelompokB1User = $this->makeUser('Kelompok B1', $this->kelompokRole, 'group', $groupB1->id);
        $desaBUser = $this->makeUser('Desa B', $this->desaRole, 'village', $villageB->id);

        $this->actingAs($desaAUser)->post('/tasks', [
            'title' => 'Tugas untuk kelompok sendiri',
            'priority' => 'medium',
            'assignees' => [$kelompokA1User->id, $daerahUser->id],
        ])->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', ['title' => 'Tugas untuk kelompok sendiri', 'assigned_by_id' => $desaAUser->id]);

        $this->actingAs($desaAUser)->post('/tasks', [
            'title' => 'Tugas lintas desa',
            'priority' => 'medium',
            'assignees' => [$desaBUser->id],
        ])->assertSessionHasErrors('assignees.0');

        $this->actingAs($desaAUser)->post('/tasks', [
            'title' => 'Tugas ke kelompok desa lain',
            'priority' => 'medium',
            'assignees' => [$kelompokB1User->id],
        ])->assertSessionHasErrors('assignees.0');

        $this->assertDatabaseMissing('tasks', ['title' => 'Tugas lintas desa']);
        $this->assertDatabaseMissing('tasks', ['title' => 'Tugas ke kelompok desa lain']);
    }

    public function test_kelompok_can_only_assign_vertically(): void
    {
        $village = $this->makeVillage('Desa A');
        $groupA1 = $this->makeGroup($village, 'Kelompok A1');
        $groupA2 = $this->makeGroup($village, 'Kelompok A2');

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null);
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokA1User = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $groupA1->id);
        $kelompokA1Peer = $this->makeUser('Kelompok A1 Peer', $this->kelompokRole, 'group', $groupA1->id);
        $kelompokA2User = $this->makeUser('Kelompok A2', $this->kelompokRole, 'group', $groupA2->id);

        $this->actingAs($kelompokA1User)->post('/tasks', [
            'title' => 'Tugas vertikal',
            'priority' => 'low',
            'assignees' => [$kelompokA1Peer->id, $desaUser->id, $daerahUser->id],
        ])->assertRedirect('/tasks');

        $this->actingAs($kelompokA1User)->post('/tasks', [
            'title' => 'Tugas ke kelompok lain',
            'priority' => 'low',
            'assignees' => [$kelompokA2User->id],
        ])->assertSessionHasErrors('assignees.0');

        $this->assertDatabaseMissing('tasks', ['title' => 'Tugas ke kelompok lain']);
    }

    public function test_daerah_can_assign_to_anyone(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $this->actingAs($daerahUser)->post('/tasks', [
            'title' => 'Tugas dari daerah',
            'priority' => 'high',
            'assignees' => [$kelompokUser->id],
        ])->assertRedirect('/tasks');

        $this->assertDatabaseHas('tasks', ['title' => 'Tugas dari daerah']);
    }

    public function test_assignee_can_update_status_but_outsider_cannot_see_or_manage_task(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $otherVillage = $this->makeVillage('Desa B');

        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);
        $outsider = $this->makeUser('Desa B', $this->desaRole, 'village', $otherVillage->id);

        $task = Task::create([
            'title' => 'Tugas Rapat',
            'priority' => 'medium',
            'status' => 'todo',
            'assigned_by_id' => $desaUser->id,
        ]);
        $task->assignees()->attach($kelompokUser->id);

        $this->actingAs($kelompokUser)
            ->patch("/tasks/{$task->id}/status", ['status' => 'in_progress'])
            ->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);

        $this->actingAs($outsider)->get('/tasks')->assertDontSee('Tugas Rapat');
        $this->actingAs($outsider)->patch("/tasks/{$task->id}/status", ['status' => 'done'])->assertForbidden();
        $this->actingAs($outsider)->delete("/tasks/{$task->id}")->assertForbidden();

        $this->actingAs($desaUser)->delete("/tasks/{$task->id}")->assertRedirect('/tasks');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
