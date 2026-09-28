<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Generus;
use App\Models\Group;
use App\Models\Guardian;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeacherAndGuardianTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_teacher_and_guardian_records(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $teacherPermission = Permission::create([
            'name' => 'Manage Teachers',
            'slug' => 'manage-teachers',
            'module' => 'teachers',
            'is_active' => true,
        ]);

        $guardianPermission = Permission::create([
            'name' => 'Manage Guardians',
            'slug' => 'manage-guardians',
            'module' => 'guardians',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$teacherPermission->id, $guardianPermission->id]);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $this->actingAs($user)
            ->post('/teachers', [
                ...$this->placementPayload(),
                'name' => 'Siti Nurhaliza',
                'gender' => 'perempuan',
                'phone' => '081234567890',
                'email' => 'siti@ppg.test',
                'status' => 'active',
                'notes' => 'Guru pembina program',
            ])
            ->assertRedirect('/teachers');

        $this->assertDatabaseHas('teachers', [
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@ppg.test',
        ]);

        $this->actingAs($user)
            ->post('/guardians', [
                'full_name' => 'Budi Santoso',
                'relationship' => 'ayah',
                'phone' => '081111222333',
                'address' => 'Karawang',
                'status' => 'active',
                'notes' => 'Wali generus',
            ])
            ->assertRedirect('/guardians');

        $this->assertDatabaseHas('guardians', [
            'full_name' => 'Budi Santoso',
            'relationship' => 'ayah',
        ]);
    }

    public function test_teacher_edit_form_is_prefilled(): void
    {
        $teacher = Teacher::create(['name' => 'Guru Lama', 'email' => 'guru@ppg.test', 'status' => 'active']);

        $this->actingAs($this->createAdmin())
            ->get("/teachers/{$teacher->id}/edit")
            ->assertOk()
            ->assertSee('value="Guru Lama"', false);
    }

    public function test_teacher_can_be_updated_keeping_own_email(): void
    {
        $teacher = Teacher::create(['name' => 'Guru Lama', 'email' => 'guru@ppg.test', 'status' => 'active']);

        $this->actingAs($this->createAdmin())
            ->put("/teachers/{$teacher->id}", [
                ...$this->placementPayload(),
                'name' => 'Guru Baru',
                'email' => 'guru@ppg.test',
                'status' => 'inactive',
            ])
            ->assertRedirect('/teachers')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'name' => 'Guru Baru', 'status' => 'inactive']);
    }

    public function test_teacher_update_rejects_email_of_another_teacher(): void
    {
        Teacher::create(['name' => 'Guru Lain', 'email' => 'lain@ppg.test', 'status' => 'active']);
        $teacher = Teacher::create(['name' => 'Guru', 'email' => 'guru@ppg.test', 'status' => 'active']);

        $this->actingAs($this->createAdmin())
            ->put("/teachers/{$teacher->id}", [...$this->placementPayload(), 'name' => 'Guru', 'email' => 'lain@ppg.test', 'status' => 'active'])
            ->assertSessionHasErrors('email');

        $this->assertSame('guru@ppg.test', $teacher->fresh()->email);
    }

    public function test_teacher_without_history_can_be_deleted(): void
    {
        $teacher = Teacher::create(['name' => 'Guru Baru', 'status' => 'active']);

        $this->actingAs($this->createAdmin())
            ->delete("/teachers/{$teacher->id}")
            ->assertRedirect('/teachers');

        $this->assertModelMissing($teacher);
    }

    public function test_teacher_with_activity_history_cannot_be_deleted(): void
    {
        $teacher = Teacher::create(['name' => 'Guru Senior', 'status' => 'active']);
        $generus = Generus::create(['registration_number' => 'PPG-FU-001', 'full_name' => 'Generus', 'status' => 'active']);
        FollowUp::create([
            'generus_id' => $generus->id,
            'teacher_id' => $teacher->id,
            'title' => 'Pembinaan',
            'follow_up_date' => '2026-09-20',
        ]);

        $this->actingAs($this->createAdmin())
            ->from("/teachers/{$teacher->id}/edit")
            ->delete("/teachers/{$teacher->id}")
            ->assertRedirect("/teachers/{$teacher->id}/edit")
            ->assertSessionHasErrors(['teacher' => 'Guru ini masih tercatat di sesi KBM, evaluasi, tindak lanjut, atau penugasan sehingga tidak dapat dihapus. Ubah statusnya menjadi Nonaktif.']);

        $this->assertModelExists($teacher);
    }

    public function test_guardian_can_be_updated_and_deleted(): void
    {
        $user = $this->createAdmin();
        $guardian = Guardian::create(['full_name' => 'Wali Lama', 'relationship' => 'ayah', 'status' => 'active']);

        $this->actingAs($user)
            ->put("/guardians/{$guardian->id}", ['full_name' => 'Wali Baru', 'relationship' => 'wali', 'status' => 'active'])
            ->assertRedirect('/guardians');

        $this->assertDatabaseHas('guardians', ['id' => $guardian->id, 'full_name' => 'Wali Baru', 'relationship' => 'wali']);

        $this->actingAs($user)
            ->delete("/guardians/{$guardian->id}")
            ->assertRedirect('/guardians');

        $this->assertModelMissing($guardian);
    }

    public function test_new_teacher_gets_next_monthly_registration_number_and_stored_photo(): void
    {
        Storage::fake();
        $this->travelTo(now()->setDate(2026, 9, 24));
        Teacher::create(['registration_number' => '260999007', 'name' => 'Guru Pertama', 'status' => 'active']);

        $this->actingAs($this->createAdmin())
            ->post('/teachers', [
                ...$this->placementPayload(),
                'name' => 'Guru Berfoto',
                'status' => 'active',
                'photo' => UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==')),
            ])
            ->assertRedirect('/teachers')
            ->assertSessionHasNoErrors();

        $teacher = Teacher::where('name', 'Guru Berfoto')->firstOrFail();
        $this->assertSame('260999008', $teacher->registration_number);
        Storage::assertExists($teacher->photo);
    }

    public function test_teacher_id_card_shows_title_name_and_registration_qr_code(): void
    {
        $teacher = Teacher::create(['registration_number' => '260999003', 'name' => 'Ustadz Kartu', 'status' => 'active']);

        $this->actingAs($this->createAdmin())
            ->get("/teachers/{$teacher->id}/id-card")
            ->assertOk()
            ->assertSee('Kartu Identitas Guru')
            ->assertSee('Ustadz Kartu')
            ->assertSee('260999003')
            ->assertSee('src="data:image/svg+xml;base64,', false);
    }

    public function test_group_scoped_user_teacher_placement_is_locked_to_own_group(): void
    {
        $this->createAdmin();
        $payload = $this->placementPayload();
        $otherGroup = Group::create(['village_id' => $payload['village_id'], 'name' => 'Kelompok Lain', 'code' => 'K99', 'is_active' => true]);
        $user = $this->createScopedTeacherManager('group', $payload['group_id']);

        $this->actingAs($user)
            ->get('/teachers')
            ->assertOk()
            ->assertSee('<select id="group_id" name="group_id" required disabled', false);

        $this->actingAs($user)
            ->post('/teachers', ['name' => 'Guru Terkunci', 'status' => 'active', 'group_id' => $otherGroup->id])
            ->assertRedirect('/teachers')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teachers', [
            'name' => 'Guru Terkunci',
            'region_id' => $payload['region_id'],
            'village_id' => $payload['village_id'],
            'group_id' => $payload['group_id'],
        ]);
    }

    public function test_village_scoped_user_locks_village_but_chooses_group(): void
    {
        $this->createAdmin();
        $payload = $this->placementPayload();
        $otherVillage = Village::create(['region_id' => $payload['region_id'], 'name' => 'Desa Lain', 'code' => 'D99', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $otherVillage->id, 'name' => 'Kelompok Desa Lain', 'code' => 'K98', 'is_active' => true]);
        $user = $this->createScopedTeacherManager('village', $payload['village_id']);

        $this->actingAs($user)
            ->post('/teachers', ['name' => 'Guru Desa', 'status' => 'active', 'group_id' => $payload['group_id']])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teachers', ['name' => 'Guru Desa', 'village_id' => $payload['village_id'], 'group_id' => $payload['group_id']]);

        $this->actingAs($user)
            ->post('/teachers', ['name' => 'Guru Luar', 'status' => 'active', 'group_id' => $otherGroup->id])
            ->assertSessionHasErrors('group_id');

        $this->assertDatabaseMissing('teachers', ['name' => 'Guru Luar']);
    }

    public function test_create_form_defaults_region_to_karawang_timur(): void
    {
        $this->createAdmin();
        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);

        $this->actingAs(User::firstOrFail())
            ->get('/teachers')
            ->assertOk()
            ->assertSee('<option value="'.$region->id.'" selected>', false);
    }

    /**
     * @return array<string, int>
     */
    private function placementPayload(): array
    {
        $region = Region::firstOrCreate(['code' => 'KRT'], ['name' => 'Karawang Timur', 'is_active' => true]);
        $village = Village::firstOrCreate(['code' => 'DSA'], ['region_id' => $region->id, 'name' => 'Desa A', 'is_active' => true]);
        $group = Group::firstOrCreate(['code' => 'K01'], ['village_id' => $village->id, 'name' => 'Kelompok 1', 'is_active' => true]);

        return ['region_id' => $region->id, 'village_id' => $village->id, 'group_id' => $group->id];
    }

    private function createScopedTeacherManager(string $scopeType, int $scopeId): User
    {
        $role = Role::create(['name' => 'Admin Wilayah', 'slug' => 'admin-wilayah', 'is_active' => true]);
        $role->permissions()->attach(Permission::where('slug', 'manage-teachers')->value('id'));

        $user = User::create([
            'name' => 'Admin Wilayah',
            'email' => 'wilayah@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->assignRole($role->id, $scopeType, $scopeId);

        return $user;
    }

    private function createAdmin(): User
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        foreach (['manage-teachers', 'manage-guardians'] as $slug) {
            $role->permissions()->attach(Permission::create([
                'name' => $slug,
                'slug' => $slug,
                'module' => $slug,
                'is_active' => true,
            ])->id);
        }

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        return $user;
    }
}
