<?php

namespace Tests\Feature;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Musyawaroh;
use App\Models\MusyawarohAttendee;
use App\Models\Notulen;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Village;
use App\Notifications\NotulenFinalized;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MusyawarohTest extends TestCase
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

    private function makeUser(string $name, Role $role, string $scopeType, ?int $scopeId, ?int $dapukanId = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => str($name)->slug().'@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, $scopeType, $scopeId, $dapukanId);

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

    public function test_daerah_user_sees_the_whole_attendance_tree_down_to_kelompok(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $dapukan = Dapukan::create(['role_slug' => 'ppg', 'group_label' => 'Pengurus Harian', 'name' => 'Ketua', 'sort_order' => 1, 'is_active' => true]);

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null, $dapukan->id);
        $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $response = $this->actingAs($daerahUser)->get('/musyawaroh/create');

        $response->assertOk()
            ->assertSee('Daerah')
            ->assertSee('Desa A')
            ->assertSee('Kelompok A1');
    }

    public function test_desa_user_does_not_see_the_daerah_node_in_the_attendance_tree(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');

        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $response = $this->actingAs($desaUser)->get('/musyawaroh/create');

        $response->assertOk()->assertDontSee('>Daerah<', false);
    }

    public function test_attendance_tree_has_a_search_box_and_starts_collapsed_for_daerah_and_desa_but_open_for_kelompok(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null);
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $this->actingAs($daerahUser)->get('/musyawaroh/create')
            ->assertOk()
            ->assertSee('data-attendance-search', false)
            ->assertSee('data-default-collapsed="1"', false);

        $this->actingAs($desaUser)->get('/musyawaroh/create')
            ->assertOk()
            ->assertSee('data-default-collapsed="1"', false);

        $this->actingAs($kelompokUser)->get('/musyawaroh/create')
            ->assertOk()
            ->assertSee('data-default-collapsed="0"', false)
            ->assertDontSee('data-default-collapsed="1"', false);
    }

    public function test_a_musyawaroh_can_be_created_with_registered_attendees_and_manual_names(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $response = $this->actingAs($desaUser)->post('/musyawaroh', [
            'title' => 'Musyawaroh Pengurus',
            'meeting_date' => '2026-11-10',
            'location' => 'Sekretariat',
            'attendance' => [$kelompokUser->id => 'hadir'],
            'manual_names' => ['Tamu Undangan'],
        ]);

        $musyawaroh = Musyawaroh::first();
        $response->assertRedirect(route('musyawaroh.show', $musyawaroh));

        $this->assertDatabaseHas('musyawarohs', [
            'title' => 'Musyawaroh Pengurus',
            'created_by_id' => $desaUser->id,
        ]);
        $this->assertDatabaseHas('musyawaroh_attendees', [
            'musyawaroh_id' => $musyawaroh->id,
            'user_id' => $kelompokUser->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('musyawaroh_attendees', [
            'musyawaroh_id' => $musyawaroh->id,
            'manual_name' => 'Tamu Undangan',
            'status' => 'hadir',
        ]);
    }

    public function test_a_musyawaroh_can_record_izin_and_alpha_for_attendees(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $izinUser = $this->makeUser('Izin Orang', $this->kelompokRole, 'group', $group->id);
        $alphaUser = $this->makeUser('Alpha Orang', $this->kelompokRole, 'group', $group->id);

        $this->actingAs($desaUser)->post('/musyawaroh', [
            'meeting_date' => '2026-11-10',
            'attendance' => [
                $izinUser->id => 'izin',
                $alphaUser->id => 'alpha',
            ],
        ])->assertRedirect();

        $musyawaroh = Musyawaroh::first();
        $this->assertDatabaseHas('musyawaroh_attendees', ['musyawaroh_id' => $musyawaroh->id, 'user_id' => $izinUser->id, 'status' => 'izin']);
        $this->assertDatabaseHas('musyawaroh_attendees', ['musyawaroh_id' => $musyawaroh->id, 'user_id' => $alphaUser->id, 'status' => 'alpha']);
    }

    public function test_an_attendee_outside_the_creators_reach_is_silently_ignored(): void
    {
        $villageA = $this->makeVillage('Desa A');
        $villageB = $this->makeVillage('Desa B');
        $groupB1 = $this->makeGroup($villageB, 'Kelompok B1');

        $desaAUser = $this->makeUser('Desa A', $this->desaRole, 'village', $villageA->id);
        $kelompokB1User = $this->makeUser('Kelompok B1', $this->kelompokRole, 'group', $groupB1->id);

        $this->actingAs($desaAUser)->post('/musyawaroh', [
            'meeting_date' => '2026-11-10',
            'attendance' => [$kelompokB1User->id => 'hadir'],
        ])->assertRedirect();

        $this->assertDatabaseCount('musyawaroh_attendees', 0);
    }

    public function test_musyawaroh_is_visible_within_the_creators_vertical_hierarchy_but_not_to_a_sibling_branch(): void
    {
        $villageA = $this->makeVillage('Desa A');
        $villageB = $this->makeVillage('Desa B');
        $groupA1 = $this->makeGroup($villageA, 'Kelompok A1');
        $groupB1 = $this->makeGroup($villageB, 'Kelompok B1');

        $daerahUser = $this->makeUser('Daerah', $this->daerahRole, 'global', null);
        $desaAUser = $this->makeUser('Desa A', $this->desaRole, 'village', $villageA->id);
        $kelompokA1User = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $groupA1->id);
        $kelompokB1User = $this->makeUser('Kelompok B1', $this->kelompokRole, 'group', $groupB1->id);

        $musyawaroh = Musyawaroh::create([
            'title' => 'Musyawaroh Kelompok A1',
            'meeting_date' => now()->toDateString(),
            'created_by_id' => $kelompokA1User->id,
        ]);

        $this->actingAs($kelompokA1User)->get('/musyawaroh')->assertSee('Musyawaroh Kelompok A1');
        $this->actingAs($desaAUser)->get('/musyawaroh')->assertSee('Musyawaroh Kelompok A1');
        $this->actingAs($daerahUser)->get('/musyawaroh')->assertSee('Musyawaroh Kelompok A1');
        $this->actingAs($desaAUser)->get("/musyawaroh/{$musyawaroh->id}")->assertOk();

        $this->actingAs($kelompokB1User)->get('/musyawaroh')->assertDontSee('Musyawaroh Kelompok A1');
        $this->actingAs($kelompokB1User)->get("/musyawaroh/{$musyawaroh->id}")->assertForbidden();
    }

    public function test_anyone_who_can_see_the_musyawaroh_can_toggle_its_notulen_but_outsiders_cannot(): void
    {
        $villageA = $this->makeVillage('Desa A');
        $villageB = $this->makeVillage('Desa B');
        $groupA1 = $this->makeGroup($villageA, 'Kelompok A1');
        $groupB1 = $this->makeGroup($villageB, 'Kelompok B1');

        $desaAUser = $this->makeUser('Desa A', $this->desaRole, 'village', $villageA->id);
        $kelompokA1User = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $groupA1->id);
        $kelompokB1User = $this->makeUser('Kelompok B1', $this->kelompokRole, 'group', $groupB1->id);

        $musyawaroh = Musyawaroh::create([
            'title' => 'Musyawaroh Kelompok A1',
            'meeting_date' => now()->toDateString(),
            'created_by_id' => $kelompokA1User->id,
        ]);

        $notulen = Notulen::create([
            'title' => 'Siapkan ruangan',
            'content' => 'Siapkan ruangan',
            'musyawaroh_id' => $musyawaroh->id,
            'created_by_id' => $kelompokA1User->id,
        ]);

        $this->actingAs($kelompokB1User)->patch("/notulen/{$notulen->id}/toggle")->assertForbidden();

        $this->actingAs($desaAUser)->patch("/notulen/{$notulen->id}/toggle")->assertRedirect();
        $notulen->refresh();
        $this->assertTrue($notulen->is_done);
        $this->assertEquals($desaAUser->id, $notulen->checked_by_id);
    }

    public function test_only_an_assignee_can_update_progress_of_a_task_born_from_a_notulen(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $musyawaroh = Musyawaroh::create([
            'meeting_date' => now()->toDateString(),
            'created_by_id' => $desaUser->id,
        ]);

        $notulen = Notulen::create([
            'title' => 'Siapkan konsumsi',
            'content' => 'Siapkan konsumsi',
            'musyawaroh_id' => $musyawaroh->id,
            'created_by_id' => $desaUser->id,
        ]);

        $task = Task::create([
            'title' => 'Siapkan konsumsi',
            'priority' => 'medium',
            'status' => 'todo',
            'due_date' => now()->addDays(14)->toDateString(),
            'assigned_by_id' => $desaUser->id,
            'notulen_id' => $notulen->id,
        ]);
        $task->assignees()->sync([$kelompokUser->id]);

        $this->actingAs($desaUser)->patch("/tasks/{$task->id}/status", ['status' => 'in_progress'])->assertForbidden();

        $this->actingAs($kelompokUser)->patch("/tasks/{$task->id}/status", ['status' => 'in_progress'])->assertRedirect();
        $this->assertEquals('in_progress', $task->refresh()->status);

        $this->actingAs($desaUser)->get("/musyawaroh/{$musyawaroh->id}")->assertOk()->assertSee('Siapkan konsumsi');
    }

    public function test_a_notulen_item_can_be_turned_into_a_task_via_the_musyawaroh_page(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $musyawaroh = Musyawaroh::create([
            'meeting_date' => now()->toDateString(),
            'created_by_id' => $desaUser->id,
        ]);

        $notulen = Notulen::create([
            'title' => 'Siapkan konsumsi',
            'content' => 'Siapkan konsumsi',
            'musyawaroh_id' => $musyawaroh->id,
            'created_by_id' => $desaUser->id,
        ]);

        $response = $this->actingAs($desaUser)->post('/tasks', [
            'title' => 'Siapkan konsumsi',
            'priority' => 'medium',
            'notulen_id' => $notulen->id,
            'assignees' => [$kelompokUser->id],
        ]);

        $response->assertRedirect(route('musyawaroh.show', $musyawaroh));
        $this->assertDatabaseHas('tasks', ['notulen_id' => $notulen->id, 'title' => 'Siapkan konsumsi']);

        // A second task cannot be created for the same notulen item.
        $this->actingAs($desaUser)->post('/tasks', [
            'title' => 'Siapkan konsumsi lagi',
            'priority' => 'medium',
            'notulen_id' => $notulen->id,
            'assignees' => [$kelompokUser->id],
        ])->assertStatus(422);
    }

    public function test_creator_of_an_ordinary_task_can_still_update_its_progress(): void
    {
        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $kelompokUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);

        $task = Task::create([
            'title' => 'Tugas biasa',
            'priority' => 'medium',
            'status' => 'todo',
            'due_date' => now()->addDays(14)->toDateString(),
            'assigned_by_id' => $desaUser->id,
        ]);
        $task->assignees()->sync([$kelompokUser->id]);

        $this->actingAs($desaUser)->patch("/tasks/{$task->id}/status", ['status' => 'in_progress'])->assertRedirect();
        $this->assertEquals('in_progress', $task->refresh()->status);
    }

    public function test_a_notulen_can_be_saved_as_a_draft_and_stays_editable(): void
    {
        $village = $this->makeVillage('Desa A');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);

        $musyawaroh = Musyawaroh::create(['meeting_date' => now()->toDateString(), 'created_by_id' => $desaUser->id]);

        $this->actingAs($desaUser)->post('/notulen', [
            'title' => 'Draf poin rapat',
            'musyawaroh_id' => $musyawaroh->id,
            'action' => 'draft',
        ])->assertRedirect(route('musyawaroh.show', $musyawaroh));

        $notulen = Notulen::first();
        $this->assertEquals(Notulen::STATUS_DRAFT, $notulen->status);
        $this->assertTrue($notulen->isContentEditableBy($desaUser));

        $this->actingAs($desaUser)->put("/notulen/{$notulen->id}", [
            'title' => 'Draf poin rapat (revisi)',
        ])->assertRedirect();

        $this->assertEquals('Draf poin rapat (revisi)', $notulen->refresh()->title);
    }

    public function test_sending_a_notulen_locks_it_and_notifies_the_whole_attendance_reach_including_absentees(): void
    {
        Notification::fake();

        $village = $this->makeVillage('Desa A');
        $group = $this->makeGroup($village, 'Kelompok A1');
        $desaUser = $this->makeUser('Desa A', $this->desaRole, 'village', $village->id);
        $presentUser = $this->makeUser('Kelompok A1', $this->kelompokRole, 'group', $group->id);
        $absentUser = $this->makeUser('Kelompok A1 Lain', $this->kelompokRole, 'group', $group->id);

        $musyawaroh = Musyawaroh::create(['meeting_date' => now()->toDateString(), 'created_by_id' => $desaUser->id]);
        MusyawarohAttendee::create(['musyawaroh_id' => $musyawaroh->id, 'user_id' => $presentUser->id]);

        $notulen = Notulen::create([
            'title' => 'Draf poin rapat',
            'content' => 'Draf poin rapat',
            'musyawaroh_id' => $musyawaroh->id,
            'created_by_id' => $desaUser->id,
        ]);

        $this->actingAs($desaUser)->patch("/notulen/{$notulen->id}/send")->assertRedirect();

        $notulen->refresh();
        $this->assertEquals(Notulen::STATUS_FINAL, $notulen->status);
        $this->assertNotNull($notulen->sent_at);
        $this->assertFalse($notulen->isContentEditableBy($desaUser));

        // Notified both the present attendee and the absent pengurus within reach.
        Notification::assertSentTo($presentUser, NotulenFinalized::class);
        Notification::assertSentTo($absentUser, NotulenFinalized::class);
        // The sender themselves is not notified.
        Notification::assertNotSentTo($desaUser, NotulenFinalized::class);

        // Locked: content can no longer be edited, nor sent again.
        $this->actingAs($desaUser)->put("/notulen/{$notulen->id}", ['title' => 'Coba ubah'])->assertForbidden();
        $this->actingAs($desaUser)->patch("/notulen/{$notulen->id}/send")->assertForbidden();

        // "Jadikan Tugas" must still work on a finalised notulen.
        $this->actingAs($desaUser)->post('/tasks', [
            'title' => $notulen->title,
            'priority' => 'medium',
            'notulen_id' => $notulen->id,
            'assignees' => [$presentUser->id],
        ])->assertRedirect(route('musyawaroh.show', $musyawaroh));
    }
}
