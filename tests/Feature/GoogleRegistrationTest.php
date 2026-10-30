<?php

namespace Tests\Feature;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\RegistrationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The three self-registerable roles aren't seeded by migrations, only by RolePermissionSeeder
     * (not run for tests), so each test creates the one it needs with the matching view permission.
     */
    private function selfRegisterableRole(string $slug, string $name): Role
    {
        $role = Role::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'view-generus'], ['name' => 'View Generus', 'module' => 'generus', 'is_active' => true])->id);

        return $role;
    }

    /**
     * Dapukans also aren't seeded by migrations, only by DapukanSeeder (not run for tests).
     */
    private function selfRegisterableDapukan(string $roleSlug, string $name = 'Dapukan Uji'): Dapukan
    {
        return Dapukan::create(['role_slug' => $roleSlug, 'group_label' => null, 'name' => $name, 'sort_order' => 0, 'can_approve' => false, 'is_active' => true]);
    }

    private function mockGoogleUser(string $email, string $name = 'Warga Baru'): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getNickname')->andReturn($name);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_an_unrecognised_google_account_is_sent_to_register_instead_of_just_failing(): void
    {
        $this->mockGoogleUser('baru@gmail.com', 'Warga Baru');

        $this->get('/login/google/callback')->assertRedirect('/register');
        $this->assertGuest();

        $this->get('/register')->assertOk()->assertSee('baru@gmail.com')->assertSee('Warga Baru', escape: false);
    }

    public function test_registering_creates_a_pending_account_with_an_inactive_role_and_does_not_log_in(): void
    {
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Satu', 'is_active' => true]);
        $role = $this->selfRegisterableRole('perwakilan-ppg-desa', 'Perwakilan PPG Desa');
        $dapukan = $this->selfRegisterableDapukan('perwakilan-ppg-desa', 'Pembina Desa');

        $this->mockGoogleUser('desa@gmail.com', 'Perwakilan Desa');
        $this->get('/login/google/callback');

        $this->post('/register', [
            'name' => 'Perwakilan Desa',
            'role_id' => $role->id,
            'dapukan_id' => $dapukan->id,
            'village_id' => $village->id,
        ])->assertRedirect('/login');

        $user = User::where('email', 'desa@gmail.com')->firstOrFail();
        $this->assertSame('pending', $user->status);
        $this->assertNull($user->password);
        $this->assertGuest();

        $pivot = $user->roles()->first()->pivot;
        $this->assertSame('village', $pivot->scope_type);
        $this->assertSame($village->id, $pivot->scope_id);
        $this->assertSame($dapukan->id, $pivot->dapukan_id);
        $this->assertFalse((bool) $pivot->is_active);
        $this->assertFalse($user->hasPermission('view-generus'));
    }

    public function test_registering_emails_the_approver_for_that_scope_but_not_an_unrelated_desa_approver(): void
    {
        Notification::fake();

        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Satu', 'is_active' => true]);
        $otherVillage = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Lain', 'is_active' => true]);
        $role = $this->selfRegisterableRole('perwakilan-ppg-desa', 'Perwakilan PPG Desa');
        $dapukan = $this->selfRegisterableDapukan('perwakilan-ppg-desa', 'Pembina Desa');

        $approver = User::create(['name' => 'Approver Desa Satu', 'email' => 'approver-satu@ppg.test', 'status' => 'active']);
        $approver->assignRole($role->id, 'village', $village->id, $dapukan->id);

        $unrelatedApprover = User::create(['name' => 'Approver Desa Lain', 'email' => 'approver-lain@ppg.test', 'status' => 'active']);
        $unrelatedApprover->assignRole($role->id, 'village', $otherVillage->id, $dapukan->id);

        $this->mockGoogleUser('desa@gmail.com', 'Perwakilan Desa');
        $this->get('/login/google/callback');

        $this->post('/register', [
            'name' => 'Perwakilan Desa',
            'role_id' => $role->id,
            'dapukan_id' => $dapukan->id,
            'village_id' => $village->id,
        ])->assertRedirect('/login');

        Notification::assertSentTo($approver, RegistrationSubmitted::class);
        Notification::assertNotSentTo($unrelatedApprover, RegistrationSubmitted::class);
    }

    public function test_registering_still_succeeds_even_if_notifying_the_approver_fails(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP down'));

        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Satu', 'is_active' => true]);
        $role = $this->selfRegisterableRole('perwakilan-ppg-desa', 'Perwakilan PPG Desa');
        $dapukan = $this->selfRegisterableDapukan('perwakilan-ppg-desa', 'Pembina Desa');

        $this->mockGoogleUser('desa@gmail.com', 'Perwakilan Desa');
        $this->get('/login/google/callback');

        $this->post('/register', [
            'name' => 'Perwakilan Desa',
            'role_id' => $role->id,
            'dapukan_id' => $dapukan->id,
            'village_id' => $village->id,
        ])->assertRedirect('/login');

        $this->assertDatabaseHas('users', ['email' => 'desa@gmail.com', 'status' => 'pending']);
    }

    public function test_registering_as_perwakilan_desa_or_pelaksana_kelompok_requires_the_matching_scope(): void
    {
        $role = $this->selfRegisterableRole('perwakilan-ppg-desa', 'Perwakilan PPG Desa');
        $dapukan = $this->selfRegisterableDapukan('perwakilan-ppg-desa');

        $this->mockGoogleUser('tanpa-desa@gmail.com');
        $this->get('/login/google/callback');

        $this->post('/register', ['name' => 'Tanpa Desa', 'role_id' => $role->id, 'dapukan_id' => $dapukan->id])
            ->assertSessionHasErrors('village_id');

        $this->assertDatabaseMissing('users', ['email' => 'tanpa-desa@gmail.com']);
    }

    public function test_registering_with_a_dapukan_from_a_different_role_fails_validation(): void
    {
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Tiga', 'is_active' => true]);
        $role = $this->selfRegisterableRole('perwakilan-ppg-desa', 'Perwakilan PPG Desa');
        $wrongDapukan = $this->selfRegisterableDapukan('pelaksana-ppg-kelompok');

        $this->mockGoogleUser('dapukan-salah@gmail.com');
        $this->get('/login/google/callback');

        $this->post('/register', [
            'name' => 'Dapukan Salah',
            'role_id' => $role->id,
            'dapukan_id' => $wrongDapukan->id,
            'village_id' => $village->id,
        ])->assertSessionHasErrors('dapukan_id');

        $this->assertDatabaseMissing('users', ['email' => 'dapukan-salah@gmail.com']);
    }

    public function test_registration_form_is_unreachable_without_first_going_through_google(): void
    {
        $this->get('/register')->assertRedirect('/login');
        $this->post('/register', ['name' => 'Siapapun'])->assertRedirect('/login');
    }

    public function test_a_pending_account_is_told_to_wait_and_an_approved_account_can_then_sign_in(): void
    {
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa Dua', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok Dua', 'is_active' => true]);
        $role = $this->selfRegisterableRole('pelaksana-ppg-kelompok', 'Pelaksana PPG Kelompok');
        $dapukan = $this->selfRegisterableDapukan('pelaksana-ppg-kelompok', 'Unsur Pembina Generus');

        $this->mockGoogleUser('kelompok@gmail.com', 'Pelaksana Kelompok');
        $this->get('/login/google/callback');
        $this->post('/register', ['name' => 'Pelaksana Kelompok', 'role_id' => $role->id, 'dapukan_id' => $dapukan->id, 'village_id' => $village->id, 'group_id' => $group->id]);

        $this->mockGoogleUser('kelompok@gmail.com', 'Pelaksana Kelompok');
        $this->get('/login/google/callback')->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();

        $user = User::where('email', 'kelompok@gmail.com')->firstOrFail();
        $admin = $this->adminWith('manage-users');

        $this->actingAs($admin)->put("/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
            'roles' => [$role->id],
            'role_scopes' => [$role->id => ['type' => 'group', 'id' => $group->id]],
            'role_dapukan' => [$role->id => $dapukan->id],
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertTrue($user->hasPermission('view-generus'));

        auth()->logout();
        $this->mockGoogleUser('kelompok@gmail.com', 'Pelaksana Kelompok');
        $this->get('/login/google/callback')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    private function adminWith(string $permission): User
    {
        $role = Role::create(['name' => 'Peran '.$permission, 'slug' => 'peran-'.$permission, 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => $permission], ['name' => $permission, 'module' => $permission, 'is_active' => true])->id);

        $user = User::create(['name' => 'Admin', 'email' => $permission.'@ppg.test', 'password' => bcrypt('password123'), 'status' => 'active']);
        $user->assignRole($role->id);

        return $user;
    }
}
