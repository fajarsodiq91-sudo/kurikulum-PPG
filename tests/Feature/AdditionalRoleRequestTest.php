<?php

namespace Tests\Feature;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\AdditionalRoleSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdditionalRoleRequestTest extends TestCase
{
    use RefreshDatabase;

    private Village $village;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Satu', 'is_active' => true]);
    }

    private function activeUser(string $roleSlug, string $scopeType, ?int $scopeId, string $dapukanName): User
    {
        $user = User::create(['name' => "active-{$dapukanName}", 'email' => strtolower(str_replace(' ', '-', $dapukanName)).'-'.uniqid().'@ppg.test', 'status' => 'active']);
        $dapukanId = Dapukan::where('role_slug', $roleSlug)->where('name', $dapukanName)->value('id');
        $user->assignRole(Role::where('slug', $roleSlug)->firstOrFail()->id, $scopeType, $scopeId, $dapukanId);

        return $user;
    }

    public function test_a_daerah_member_may_request_perwakilan_desa_as_an_additional_role(): void
    {
        Notification::fake();

        $user = $this->activeUser('ppg', 'global', null, 'Bidang Kurikulum');
        $dapukan = Dapukan::where('role_slug', 'perwakilan-ppg-desa')->firstOrFail();
        $role = Role::where('slug', 'perwakilan-ppg-desa')->firstOrFail();

        $this->actingAs($user)->get('/profile/additional-role')->assertOk()->assertSee($role->name);

        $this->actingAs($user)->post('/profile/additional-role', [
            'role_id' => $role->id,
            'dapukan_id' => $dapukan->id,
            'village_id' => $this->village->id,
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $pivot = $user->roles()->where('roles.id', $role->id)->first()->pivot;
        $this->assertSame('village', $pivot->scope_type);
        $this->assertSame($this->village->id, $pivot->scope_id);
        $this->assertFalse((bool) $pivot->is_active);

        // the original role is untouched
        $this->assertSame('active', $user->status);
        $this->assertTrue((bool) $user->roles()->where('roles.slug', 'ppg')->first()->pivot->is_active);
    }

    public function test_a_member_cannot_request_a_role_higher_than_what_they_already_hold(): void
    {
        $user = $this->activeUser('perwakilan-ppg-desa', 'village', $this->village->id, 'Pembina Desa');
        $ppgRole = Role::where('slug', 'ppg')->firstOrFail();
        $dapukan = Dapukan::where('role_slug', 'ppg')->firstOrFail();

        $this->actingAs($user)->get('/profile/additional-role')->assertOk()->assertDontSee($ppgRole->name, escape: false);

        $this->actingAs($user)->post('/profile/additional-role', [
            'role_id' => $ppgRole->id,
            'dapukan_id' => $dapukan->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id, 'role_id' => $ppgRole->id]);
    }

    public function test_a_member_at_the_lowest_level_has_nothing_available_to_request(): void
    {
        $village = $this->village;
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Satu', 'is_active' => true]);
        $user = $this->activeUser('pelaksana-ppg-kelompok', 'group', $group->id, 'Unsur Pembina Generus');

        $this->actingAs($user)->get('/profile/additional-role')
            ->assertOk()
            ->assertSee('Tidak ada peran tambahan yang bisa diajukan dari peran Anda saat ini.');
    }

    public function test_a_member_cannot_request_a_role_they_already_hold_or_have_pending(): void
    {
        $user = $this->activeUser('ppg', 'global', null, 'Bidang Kurikulum');
        $role = Role::where('slug', 'perwakilan-ppg-desa')->firstOrFail();
        $dapukan = Dapukan::where('role_slug', 'perwakilan-ppg-desa')->firstOrFail();

        $this->actingAs($user)->post('/profile/additional-role', [
            'role_id' => $role->id,
            'dapukan_id' => $dapukan->id,
            'village_id' => $this->village->id,
        ])->assertRedirect(route('profile.edit'));

        // actingAs() keeps reusing this same in-memory $user object as the guard's resolved
        // user across requests in this test; refresh it so the second request sees the roles
        // relation freshly loaded, the way a brand-new HTTP request would in production.
        $user->refresh();
        $this->actingAs($user);

        // requesting the same role again while it's still pending must be rejected
        $this->actingAs($user)->post('/profile/additional-role', [
            'role_id' => $role->id,
            'dapukan_id' => $dapukan->id,
            'village_id' => $this->village->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertSame(1, $user->roles()->where('role_id', $role->id)->count());
    }

    public function test_requesting_an_additional_role_notifies_the_matching_approver_but_not_an_unrelated_one(): void
    {
        // Approval is peer-level, same as a brand-new registration (see RegistrationApproval):
        // only someone who already holds the SAME role at the SAME scope (or has manage-users)
        // may approve it. A requester's own higher-level role plays no part in who gets notified.
        Notification::fake();

        $group = Group::create(['village_id' => $this->village->id, 'name' => 'Kelompok Satu', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $this->village->id, 'name' => 'Kelompok Lain', 'is_active' => true]);
        $groupRole = Role::where('slug', 'pelaksana-ppg-kelompok')->firstOrFail();
        $groupDapukan = Dapukan::where('role_slug', 'pelaksana-ppg-kelompok')->firstOrFail();

        $peerApprover = $this->activeUser('pelaksana-ppg-kelompok', 'group', $group->id, 'Unsur Pembina Generus');
        $unrelatedApprover = $this->activeUser('pelaksana-ppg-kelompok', 'group', $otherGroup->id, 'Unsur Pembina Generus');
        $requester = $this->activeUser('ppg', 'global', null, 'Bidang Kurikulum');

        $this->actingAs($requester)->post('/profile/additional-role', [
            'role_id' => $groupRole->id,
            'dapukan_id' => $groupDapukan->id,
            'village_id' => $this->village->id,
            'group_id' => $group->id,
        ])->assertRedirect(route('profile.edit'));

        Notification::assertSentTo($peerApprover, AdditionalRoleSubmitted::class);
        Notification::assertNotSentTo($unrelatedApprover, AdditionalRoleSubmitted::class);
    }
}
