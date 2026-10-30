<?php

namespace Tests\Feature;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\RegistrationDecided;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Village $villageA;

    private Village $villageB;

    private Group $groupA1;

    private Group $groupA2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $region = Region::karawangTimur();
        $this->villageA = Village::create(['region_id' => $region->id, 'name' => 'Desa A', 'is_active' => true]);
        $this->villageB = Village::create(['region_id' => $region->id, 'name' => 'Desa B', 'is_active' => true]);
        $this->groupA1 = Group::create(['village_id' => $this->villageA->id, 'name' => 'Kelompok A1', 'is_active' => true]);
        $this->groupA2 = Group::create(['village_id' => $this->villageA->id, 'name' => 'Kelompok A2', 'is_active' => true]);
    }

    private function activeUser(string $roleSlug, string $scopeType, ?int $scopeId, string $dapukanName): User
    {
        $user = User::create(['name' => "active-{$dapukanName}", 'email' => strtolower(str_replace(' ', '-', $dapukanName)).'-'.uniqid().'@ppg.test', 'status' => 'active']);
        $dapukanId = Dapukan::where('role_slug', $roleSlug)->where('name', $dapukanName)->value('id');
        $user->assignRole(Role::where('slug', $roleSlug)->firstOrFail()->id, $scopeType, $scopeId, $dapukanId);

        return $user;
    }

    private function pendingUser(string $roleSlug, string $scopeType, ?int $scopeId, string $dapukanName): User
    {
        $user = User::create(['name' => "pending-{$dapukanName}", 'email' => strtolower(str_replace(' ', '-', $dapukanName)).'-'.uniqid().'@ppg.test', 'status' => 'pending']);
        $dapukanId = Dapukan::where('role_slug', $roleSlug)->where('name', $dapukanName)->value('id');
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role->id, ['scope_type' => $scopeType, 'scope_id' => $scopeId, 'dapukan_id' => $dapukanId, 'is_active' => false]);

        return $user;
    }

    public function test_ph_ppg_member_sees_and_approves_pending_daerah_registration(): void
    {
        $approver = $this->activeUser('ppg', 'global', null, 'Ketua PPG');
        $pending = $this->pendingUser('ppg', 'global', null, 'Bendahara');

        $this->actingAs($approver)->get('/registrations/pending')->assertOk()->assertSee($pending->name);

        $response = $this->actingAs($approver)->post("/registrations/{$pending->id}/approve");

        $response->assertRedirect(route('registrations.pending'));
        $pending->refresh();
        $this->assertSame('active', $pending->status);
        $this->assertTrue((bool) $pending->roles()->first()->pivot->is_active);
    }

    public function test_seksi_bidang_member_cannot_see_or_approve_pending_daerah_registration(): void
    {
        $nonPhApprover = $this->activeUser('ppg', 'global', null, 'Bidang Kurikulum');
        $pending = $this->pendingUser('ppg', 'global', null, 'Bidang Tahfidz');

        $this->actingAs($nonPhApprover)->get('/registrations/pending')->assertOk()->assertDontSee($pending->name);

        $this->actingAs($nonPhApprover)->post("/registrations/{$pending->id}/approve")->assertForbidden();

        $pending->refresh();
        $this->assertSame('pending', $pending->status);
    }

    public function test_perwakilan_desa_approves_peer_in_same_desa_but_not_other_desa(): void
    {
        $approver = $this->activeUser('perwakilan-ppg-desa', 'village', $this->villageA->id, 'Pembina Desa');
        $sameVillage = $this->pendingUser('perwakilan-ppg-desa', 'village', $this->villageA->id, 'Bidang BK');
        $otherVillage = $this->pendingUser('perwakilan-ppg-desa', 'village', $this->villageB->id, 'Sekretaris');

        $this->actingAs($approver)->get('/registrations/pending')->assertOk()->assertSee($sameVillage->name)->assertDontSee($otherVillage->name);

        $this->actingAs($approver)->post("/registrations/{$sameVillage->id}/approve")->assertRedirect(route('registrations.pending'));
        $this->assertSame('active', $sameVillage->refresh()->status);

        $this->actingAs($approver)->post("/registrations/{$otherVillage->id}/approve")->assertForbidden();
        $this->assertSame('pending', $otherVillage->refresh()->status);
    }

    public function test_pelaksana_kelompok_approves_peer_in_same_kelompok_but_not_other_kelompok(): void
    {
        $approver = $this->activeUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Unsur Pembina Generus');
        $sameGroup = $this->pendingUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Bidang BK');
        $otherGroup = $this->pendingUser('pelaksana-ppg-kelompok', 'group', $this->groupA2->id, 'Sekretaris');

        $this->actingAs($approver)->get('/registrations/pending')->assertOk()->assertSee($sameGroup->name)->assertDontSee($otherGroup->name);

        $this->actingAs($approver)->post("/registrations/{$sameGroup->id}/approve")->assertRedirect(route('registrations.pending'));
        $this->assertSame('active', $sameGroup->refresh()->status);

        $this->actingAs($approver)->post("/registrations/{$otherGroup->id}/approve")->assertForbidden();
        $this->assertSame('pending', $otherGroup->refresh()->status);
    }

    public function test_superadmin_approves_and_rejects_any_pending_registration(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin-'.uniqid().'@ppg.test', 'status' => 'active']);
        $admin->assignRole(Role::where('slug', 'super-admin')->firstOrFail()->id);

        $toApprove = $this->pendingUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Bidang BK');
        $toReject = $this->pendingUser('perwakilan-ppg-desa', 'village', $this->villageB->id, 'Bidang BK');

        $this->actingAs($admin)->post("/registrations/{$toApprove->id}/approve")->assertRedirect(route('registrations.pending'));
        $this->assertSame('active', $toApprove->refresh()->status);

        $this->actingAs($admin)->post("/registrations/{$toReject->id}/reject")->assertRedirect(route('registrations.pending'));
        $this->assertModelMissing($toReject);
    }

    public function test_rejecting_a_registration_deletes_the_pending_account(): void
    {
        $approver = $this->activeUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Unsur Pembina Generus');
        $pending = $this->pendingUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Bidang BK');

        $this->actingAs($approver)->post("/registrations/{$pending->id}/reject")->assertRedirect(route('registrations.pending'));

        $this->assertModelMissing($pending);
    }

    public function test_approving_a_registration_emails_the_applicant(): void
    {
        Notification::fake();

        $approver = $this->activeUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Unsur Pembina Generus');
        $pending = $this->pendingUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Bidang BK');

        $this->actingAs($approver)->post("/registrations/{$pending->id}/approve")->assertRedirect(route('registrations.pending'));

        Notification::assertSentTo(
            $pending,
            fn (RegistrationDecided $notification): bool => $notification->toMail($pending)->subject === 'Pendaftaran Anda Disetujui'
        );
    }

    public function test_rejecting_a_registration_emails_the_applicant_before_deleting_it(): void
    {
        Notification::fake();

        $approver = $this->activeUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Unsur Pembina Generus');
        $pending = $this->pendingUser('pelaksana-ppg-kelompok', 'group', $this->groupA1->id, 'Bidang BK');

        $this->actingAs($approver)->post("/registrations/{$pending->id}/reject")->assertRedirect(route('registrations.pending'));

        Notification::assertSentTo(
            $pending,
            fn (RegistrationDecided $notification): bool => $notification->toMail($pending)->subject === 'Pendaftaran Anda Ditolak'
        );
    }
}
