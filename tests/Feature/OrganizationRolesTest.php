<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationRolesTest extends TestCase
{
    use RefreshDatabase;

    private Village $villageA;

    private Village $villageB;

    private Group $groupA1;

    private Group $groupA2;

    private Group $groupB1;

    private Generus $inA1;

    private Generus $inA2;

    private Generus $inB1;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $region = Region::karawangTimur();
        $this->villageA = Village::create(['region_id' => $region->id, 'name' => 'Desa A', 'is_active' => true]);
        $this->villageB = Village::create(['region_id' => $region->id, 'name' => 'Desa B', 'is_active' => true]);
        $this->groupA1 = Group::create(['village_id' => $this->villageA->id, 'name' => 'Kelompok A1', 'is_active' => true]);
        $this->groupA2 = Group::create(['village_id' => $this->villageA->id, 'name' => 'Kelompok A2', 'is_active' => true]);
        $this->groupB1 = Group::create(['village_id' => $this->villageB->id, 'name' => 'Kelompok B1', 'is_active' => true]);
        $this->level = Level::create(['name' => 'Kelas 1', 'code' => 'K1', 'sort_order' => 1, 'is_active' => true]);

        $this->inA1 = $this->placed('Murid A1', $this->groupA1, 'Ayah A1');
        $this->inA2 = $this->placed('Murid A2', $this->groupA2, 'Ayah A2');
        $this->inB1 = $this->placed('Murid B1', $this->groupB1, 'Ayah B1');

        foreach ([[$this->groupA1, 'Guru A1', '260999011'], [$this->groupA2, 'Guru A2', '260999012'], [$this->groupB1, 'Guru B1', '260999013']] as [$group, $name, $number]) {
            Teacher::create(['registration_number' => $number, 'name' => $name, 'status' => 'active', 'region_id' => $region->id, 'village_id' => $group->village_id, 'group_id' => $group->id]);
        }
    }

    private function placed(string $name, Group $group, string $father): Generus
    {
        $generus = Generus::create(['registration_number' => fake()->unique()->numerify('2609####'), 'full_name' => $name, 'father_name' => $father, 'status' => 'active']);
        GenerusAssignment::create(['generus_id' => $generus->id, 'region_id' => Region::karawangTimur()->id, 'village_id' => $group->village_id, 'group_id' => $group->id, 'level_id' => $this->level->id]);

        return $generus;
    }

    private function userWithRole(string $roleSlug, string $scopeType = 'global', ?int $scopeId = null): User
    {
        $user = User::create(['name' => $roleSlug, 'email' => $roleSlug.'@ppg.test', 'password' => 'password123', 'status' => 'active']);
        $user->assignRole(Role::where('slug', $roleSlug)->firstOrFail()->id, $scopeType, $scopeId);

        return $user;
    }

    private function reportCard(Generus $generus): ReportCard
    {
        $year = AcademicYear::firstOrCreate(['code' => 'TA'], ['name' => '2025/2026', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
        $semester = Semester::firstOrCreate(['code' => 'S1'], ['academic_year_id' => $year->id, 'name' => 'S1', 'sort_order' => 1, 'is_active' => true]);

        return ReportCard::create(['generus_id' => $generus->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id]);
    }

    public function test_the_three_roles_exist_with_expected_permissions(): void
    {
        $ppg = Role::where('slug', 'ppg')->firstOrFail()->permissions()->pluck('slug');
        $village = Role::where('slug', 'perwakilan-ppg-desa')->firstOrFail()->permissions()->pluck('slug');
        $group = Role::where('slug', 'pelaksana-ppg-kelompok')->firstOrFail()->permissions()->pluck('slug');

        $this->assertTrue($ppg->contains('view-master-data'));
        $this->assertFalse($ppg->contains('manage-generus'));
        $this->assertFalse($ppg->contains('manage-users'));
        $this->assertFalse($village->contains('view-master-data'));
        $this->assertTrue($village->every(fn (string $slug): bool => str_starts_with($slug, 'view-')));
        $this->assertTrue($group->contains('manage-generus'));
        $this->assertFalse($group->contains('manage-master-data'));
    }

    public function test_ppg_sees_everything_but_cannot_change_anything(): void
    {
        $ppg = $this->userWithRole('ppg');
        $this->reportCard($this->inB1);

        $this->actingAs($ppg)->get('/generus')->assertOk()->assertSee('Murid A1')->assertSee('Murid B1');
        $this->actingAs($ppg)->get('/teachers')->assertOk()->assertSee('Guru A1')->assertSee('Guru B1');
        $this->actingAs($ppg)->get('/report-cards')->assertOk()->assertSee('Murid B1')->assertDontSee('Simpan');
        $this->actingAs($ppg)->get('/master-data/villages')->assertOk()->assertSee('Desa A')->assertDontSee('Tambah Desa');

        $this->actingAs($ppg)->get('/generus/create')->assertForbidden();
        $this->actingAs($ppg)->post('/generus', ['full_name' => 'X'])->assertForbidden();
        $this->actingAs($ppg)->post('/report-cards', [])->assertForbidden();
        $this->actingAs($ppg)->post('/master-data/villages', ['name' => 'Desa C', 'is_active' => 1])->assertForbidden();
        $this->actingAs($ppg)->get('/users')->assertForbidden();
        $this->actingAs($ppg)->get('/roles')->assertForbidden();
    }

    public function test_village_representative_sees_only_own_village_read_only(): void
    {
        $rep = $this->userWithRole('perwakilan-ppg-desa', 'village', $this->villageA->id);
        $this->reportCard($this->inA1);
        $ownerless = $this->reportCard($this->inB1);

        $this->actingAs($rep)->get('/generus')->assertOk()->assertSee('Murid A1')->assertSee('Murid A2')->assertDontSee('Murid B1');
        $this->actingAs($rep)->get("/generus/{$this->inB1->id}")->assertNotFound();
        $this->actingAs($rep)->get('/teachers')->assertOk()->assertSee('Guru A1')->assertDontSee('Guru B1');
        $this->actingAs($rep)->get('/guardians')->assertOk()->assertSee('Ayah A1')->assertDontSee('Ayah B1');
        $this->actingAs($rep)->get('/report-cards')->assertOk()->assertSee('Murid A1')->assertDontSee('Murid B1');
        $this->actingAs($rep)->get('/dashboard')->assertOk();

        $this->actingAs($rep)->post('/generus', ['full_name' => 'Baru'])->assertForbidden();
        $this->actingAs($rep)->get("/report-cards/{$ownerless->id}/edit")->assertForbidden();
        $this->actingAs($rep)->get('/master-data/villages')->assertForbidden();
        $this->actingAs($rep)->get('/users')->assertForbidden();
    }

    public function test_group_executor_manages_only_own_group_data(): void
    {
        $exec = $this->userWithRole('pelaksana-ppg-kelompok', 'group', $this->groupA1->id);
        $otherCard = $this->reportCard($this->inA2);
        $this->reportCard($this->inA1);

        $this->actingAs($exec)->get('/generus')->assertOk()->assertSee('Murid A1')->assertDontSee('Murid A2')->assertDontSee('Murid B1');
        $this->actingAs($exec)->get('/teachers')->assertOk()->assertSee('Guru A1')->assertDontSee('Guru A2');
        $this->actingAs($exec)->get('/report-cards')->assertOk()->assertSee('Murid A1')->assertDontSee('Murid A2');
        $this->actingAs($exec)->get("/report-cards/{$otherCard->id}/edit")->assertNotFound();

        $payload = ['full_name' => 'Murid Baru', 'status' => 'active', 'village_id' => $this->villageA->id, 'level_id' => $this->level->id];
        $this->actingAs($exec)->post('/generus', [...$payload, 'group_id' => $this->groupA1->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('generus', ['full_name' => 'Murid Baru']);

        $this->actingAs($exec)->post('/generus', [...$payload, 'full_name' => 'Murid Salah', 'group_id' => $this->groupA2->id])->assertSessionHasErrors('group_id');
        $this->assertDatabaseMissing('generus', ['full_name' => 'Murid Salah']);

        $followUp = ['title' => 'Bina', 'priority' => 'low', 'follow_up_date' => '2026-10-01', 'status' => 'scheduled', 'teacher_id' => Teacher::where('group_id', $this->groupA1->id)->value('id')];
        $this->actingAs($exec)->post('/follow-ups', [...$followUp, 'generus_id' => $this->inA1->id])->assertSessionHasNoErrors();
        $this->actingAs($exec)->post('/follow-ups', [...$followUp, 'generus_id' => $this->inA2->id])->assertSessionHasErrors('generus_id');

        $this->actingAs($exec)->post('/master-data/villages', ['name' => 'Desa C', 'is_active' => 1])->assertForbidden();
        $this->actingAs($exec)->get('/master-data/villages')->assertForbidden();
    }
}
