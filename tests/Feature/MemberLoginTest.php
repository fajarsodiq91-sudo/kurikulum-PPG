<?php

namespace Tests\Feature;

use App\Models\Generus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A 128-number descriptor whose distance to descriptor(0) is exactly $x.
     *
     * @return list<float>
     */
    private function descriptor(float $x): array
    {
        return [$x, ...array_fill(0, 127, 0.0)];
    }

    private function generus(string $name, array $attributes = []): Generus
    {
        return Generus::create($attributes + ['registration_number' => 'PPG-'.strtoupper($name), 'full_name' => $name, 'status' => 'active']);
    }

    private function accountOf(Generus $generus): User
    {
        return User::where('generus_id', $generus->id)->firstOrFail();
    }

    public function test_generus_signs_in_by_rfid_without_a_password(): void
    {
        $generus = $this->generus('Ali', ['rfid_uid' => 'A1B2C3D4']);

        $this->postJson('/login/rfid', ['uid' => ' a1b2 c3d4 '])
            ->assertOk()->assertJsonStructure(['redirect']);

        $this->assertAuthenticatedAs($this->accountOf($generus));
    }

    public function test_teacher_signs_in_by_rfid(): void
    {
        $teacher = Teacher::create(['registration_number' => Teacher::nextRegistrationNumber(), 'name' => 'Farah', 'status' => 'active', 'rfid_uid' => 'TEACH001']);

        $this->postJson('/login/rfid', ['uid' => 'TEACH001'])->assertOk();

        $this->assertAuthenticatedAs(User::where('teacher_id', $teacher->id)->firstOrFail());
    }

    public function test_unknown_inactive_or_missing_cards_are_rejected_alike(): void
    {
        $this->generus('Budi', ['rfid_uid' => 'INACTIVE1', 'status' => 'married']);

        $this->postJson('/login/rfid', ['uid' => 'UNKNOWN'])->assertUnprocessable()->assertJson(['message' => 'Kartu tidak dikenali.']);
        $this->postJson('/login/rfid', ['uid' => 'INACTIVE1'])->assertUnprocessable()->assertJson(['message' => 'Kartu tidak dikenali.']);
        $this->postJson('/login/rfid', [])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_card_of_a_user_manager_or_deactivated_account_can_not_sign_in_without_password(): void
    {
        $admin = $this->generus('Cici', ['rfid_uid' => 'ADMINCARD']);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin-x', 'is_active' => true]);
        $role->permissions()->attach(Permission::create(['name' => 'Manage Users', 'slug' => 'manage-users', 'module' => 'users', 'is_active' => true])->id);
        $this->accountOf($admin)->assignRole($role->id);

        $blocked = $this->generus('Dodi', ['rfid_uid' => 'BLOCKED01']);
        $this->accountOf($blocked)->update(['status' => 'inactive']);

        $this->postJson('/login/rfid', ['uid' => 'ADMINCARD'])->assertUnprocessable();
        $this->postJson('/login/rfid', ['uid' => 'BLOCKED01'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_face_login_matches_the_closest_enrolled_face_only_within_the_threshold(): void
    {
        $ali = $this->generus('Ali', ['face_descriptor' => $this->descriptor(0.0)]);
        $this->generus('Budi', ['face_descriptor' => $this->descriptor(2.0)]);
        $this->generus('Cici');

        $this->postJson('/login/face', ['descriptor' => json_encode($this->descriptor(0.2))])->assertOk();
        $this->assertAuthenticatedAs($this->accountOf($ali));

        auth()->logout();

        $this->postJson('/login/face', ['descriptor' => json_encode($this->descriptor(1.0))])
            ->assertUnprocessable()->assertJson(['message' => 'Wajah tidak dikenali.']);
        $this->assertGuest();
    }

    public function test_ambiguous_faces_are_refused(): void
    {
        $this->generus('Ali', ['face_descriptor' => $this->descriptor(0.0)]);
        $this->generus('Adik', ['face_descriptor' => $this->descriptor(0.02)]);

        $this->postJson('/login/face', ['descriptor' => json_encode($this->descriptor(0.01))])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_face_login_rejects_malformed_descriptors(): void
    {
        $this->postJson('/login/face', ['descriptor' => json_encode([0.1, 0.2])])->assertUnprocessable()->assertJsonValidationErrors('descriptor');
        $this->postJson('/login/face', ['descriptor' => json_encode(array_fill(0, 128, 'x'))])->assertUnprocessable();
        $this->postJson('/login/face', [])->assertUnprocessable();
    }

    public function test_login_page_offers_the_scan_options(): void
    {
        $this->get('/login')->assertOk()->assertSee('Scan Wajah')->assertSee('Scan RFID')->assertSee('Scan QR');
    }

    public function test_face_data_is_stored_only_with_a_new_photo_and_never_from_the_wrong_size(): void
    {
        Storage::fake();
        $admin = $this->adminWith('manage-generus');
        $generus = $this->generus('Eka');
        $payload = ['full_name' => 'Eka', 'status' => 'pindah_sambung', 'transfer_destination' => 'external'];

        $this->actingAs($admin)->put("/generus/{$generus->id}", $payload + ['face_descriptor' => json_encode($this->descriptor(0.3))])->assertSessionHasNoErrors();
        $this->assertNull($generus->fresh()->face_descriptor);

        $this->actingAs($admin)->put("/generus/{$generus->id}", $payload + ['face_descriptor' => '[1,2]'])->assertSessionHasErrors('face_descriptor');

        $this->actingAs($admin)->put("/generus/{$generus->id}", $payload + [
            'photo' => UploadedFile::fake()->create('eka.jpg', 10, 'image/jpeg'),
            'face_descriptor' => json_encode($this->descriptor(0.3)),
        ])->assertSessionHasNoErrors();
        $this->assertSame(0.3, $generus->fresh()->face_descriptor[0]);
    }

    public function test_only_user_managers_can_use_the_face_enrollment_page(): void
    {
        Storage::fake();
        $generus = $this->generus('Fani', ['photo' => UploadedFile::fake()->create('f.jpg', 10, 'image/jpeg')->store('generus-photos')]);
        $admin = $this->adminWith('manage-users');
        $member = $this->accountOf($this->generus('Gita'));

        $this->actingAs($member)->get('/face-enrollment')->assertForbidden();
        $this->actingAs($member)->postJson("/face-enrollment/generus/{$generus->id}", ['descriptor' => json_encode($this->descriptor(0.1))])->assertForbidden();

        $this->actingAs($admin)->get('/face-enrollment')->assertOk()->assertSee('Fani');
        $this->actingAs($admin)->get("/face-enrollment/generus/{$generus->id}/photo")->assertOk();
        $this->actingAs($admin)->postJson("/face-enrollment/generus/{$generus->id}", ['descriptor' => json_encode($this->descriptor(0.1))])->assertOk();
        $this->assertSame(0.1, $generus->fresh()->face_descriptor[0]);
        $this->actingAs($admin)->postJson("/face-enrollment/generus/{$generus->id}", ['descriptor' => '[1]'])->assertUnprocessable();
    }

    private function adminWith(string $permission): User
    {
        $role = Role::create(['name' => 'Peran '.$permission, 'slug' => 'peran-'.$permission, 'is_active' => true]);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => $permission], ['name' => $permission, 'module' => $permission, 'is_active' => true])->id);

        $user = User::create(['name' => 'Admin', 'email' => $permission.'@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id);

        return $user;
    }
}
