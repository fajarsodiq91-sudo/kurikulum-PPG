<?php

namespace Tests\Feature;

use App\Exports\GenerusExport;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class GenerusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_generus_with_assignment_history(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Generus',
            'slug' => 'manage-generus',
            'module' => 'generus',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $region = Region::create([
            'name' => 'Karawang Timur',
            'code' => 'KRT',
            'is_active' => true,
        ]);

        $village = Village::create([
            'region_id' => $region->id,
            'name' => 'Desa A',
            'code' => 'DSA',
            'is_active' => true,
        ]);

        $group = Group::create([
            'village_id' => $village->id,
            'name' => 'Kelompok 1',
            'code' => 'K01',
            'is_active' => true,
        ]);

        $level = Level::create([
            'name' => 'Kelas 1',
            'code' => 'KELAS-1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $schoolGrade = ClassGrade::create([
            'name' => 'Kelas 4',
            'code' => 'SD-4',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/generus', [
                'full_name' => 'Ayu Lestari',
                'school_name' => 'Madrasah A',
                'nis' => 'NIS-001',
                'father_name' => 'Budi Lestari',
                'mother_name' => 'Siti Lestari',
                'father_occupation' => 'Wiraswasta',
                'mother_occupation' => 'Ibu Rumah Tangga',
                'phone_number' => '081234567890',
                'gender' => 'perempuan',
                'birth_place' => 'Karawang',
                'birth_date' => '2014-05-12',
                'birth_order' => 2,
                'sibling_count' => 3,
                'school_grade_id' => $schoolGrade->id,
                'learning_class_id' => $schoolGrade->id,
                'status' => 'active',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
                'notes' => 'Data awal generus',
            ])
            ->assertRedirect('/generus');

        $created = Generus::query()->where('full_name', 'Ayu Lestari')->firstOrFail();

        $this->assertMatchesRegularExpression('/^\d{8}$/', $created->registration_number);
        $this->assertSame($created->registration_number, $created->nis);
        $this->assertSame('0001', $created->record_number);
        $this->assertSame('Budi Lestari', $created->father_name);
        $this->assertSame('Karawang', $created->birth_place);
        $this->assertSame(2, $created->birth_order);
        $this->assertSame(3, $created->sibling_count);
        $this->assertSame($schoolGrade->id, $created->school_grade_id);
        $this->assertSame($schoolGrade->id, $created->learning_class_id);

        $this->assertDatabaseHas('generus_assignments', [
            'generus_id' => $created->id,
            'ended_at' => null,
        ]);
    }

    public function test_admin_can_export_generus_as_xlsx(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();

        $generus = Generus::create([
            'registration_number' => 'PPG-EXPORT-001',
            'full_name' => 'Export Generus',
            'nis' => 'NIS-EXPORT-001',
            'status' => 'active',
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'level_id' => $level->id,
        ]);

        $this->actingAs($user)
            ->get('/generus/export')
            ->assertDownload('generus.xlsx');
    }

    public function test_internal_transfer_uses_the_newest_registered_placement(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();

        $this->actingAs($user)
            ->post('/generus', [
                'full_name' => 'Generus Pindah Internal',
                'status' => 'pindah_sambung',
                'transfer_destination' => 'internal',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
            ])
            ->assertRedirect('/generus');

        $generus = Generus::query()->where('full_name', 'Generus Pindah Internal')->firstOrFail();

        $this->assertSame('pindah_sambung', $generus->status);
        $this->assertSame('internal', $generus->transfer_destination);
        $this->assertDatabaseHas('generus_assignments', [
            'generus_id' => $generus->id,
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'ended_at' => null,
        ]);
    }

    public function test_external_transfer_can_be_saved_without_registered_placement(): void
    {
        [$user] = $this->createGenerusImportContext();

        $this->actingAs($user)
            ->post('/generus', [
                'full_name' => 'Generus Pindah Eksternal',
                'status' => 'pindah_sambung',
                'transfer_destination' => 'external',
            ])
            ->assertRedirect('/generus');

        $generus = Generus::query()->where('full_name', 'Generus Pindah Eksternal')->firstOrFail();

        $this->assertSame('external', $generus->transfer_destination);
        $this->assertDatabaseHas('generus_assignments', [
            'generus_id' => $generus->id,
            'region_id' => null,
            'village_id' => null,
            'group_id' => null,
            'notes' => 'Pindah sambung ke luar daerah.',
        ]);
    }

    public function test_admin_can_import_generus_from_exported_xlsx(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();

        $generus = Generus::create([
            'registration_number' => 'PPG-IMPORT-001',
            'full_name' => 'Nama Lama',
            'nis' => 'NIS-IMPORT-001',
            'status' => 'active',
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'level_id' => $level->id,
        ]);

        $xlsx = Excel::raw(new GenerusExport, ExcelWriter::XLSX);

        $generus->update(['full_name' => 'Nama Diubah']);

        $this->actingAs($user)
            ->post('/generus/import', [
                'file' => UploadedFile::fake()->createWithContent('generus.xlsx', $xlsx),
            ])
            ->assertRedirect('/generus');

        $this->assertDatabaseHas('generus', [
            'registration_number' => 'PPG-IMPORT-001',
            'full_name' => 'Nama Lama',
            'nis' => 'NIS-IMPORT-001',
        ]);
    }

    public function test_import_restores_every_exported_generus_field(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();

        $schoolGrade = ClassGrade::create(['name' => 'Kelas 5', 'code' => 'SD-5', 'sort_order' => 5, 'is_active' => true]);

        $generus = Generus::create([
            'registration_number' => 'PPG-IMPORT-002',
            'record_number' => '0042',
            'full_name' => 'Generus Lengkap',
            'school_name' => 'SDN 1 Karawang',
            'nis' => 'NIS-IMPORT-002',
            'father_name' => 'Ahmad',
            'mother_name' => 'Siti',
            'phone_number' => '081234567890',
            'birth_place' => 'Karawang',
            'school_grade_id' => $schoolGrade->id,
            'status' => 'active',
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'level_id' => $level->id,
            'notes' => 'Catatan penempatan',
        ]);

        $xlsx = Excel::raw(new GenerusExport, ExcelWriter::XLSX);

        $generus->update([
            'record_number' => null,
            'school_name' => null,
            'father_name' => null,
            'mother_name' => null,
            'phone_number' => null,
            'birth_place' => null,
            'school_grade_id' => null,
        ]);
        $generus->assignments()->update(['notes' => null]);

        $this->actingAs($user)
            ->post('/generus/import', [
                'file' => UploadedFile::fake()->createWithContent('generus.xlsx', $xlsx),
            ])
            ->assertRedirect('/generus')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('generus', [
            'id' => $generus->id,
            'record_number' => '0042',
            'school_name' => 'SDN 1 Karawang',
            'father_name' => 'Ahmad',
            'mother_name' => 'Siti',
            'phone_number' => '081234567890',
            'birth_place' => 'Karawang',
            'school_grade_id' => $schoolGrade->id,
        ]);
        $this->assertDatabaseHas('generus_assignments', [
            'generus_id' => $generus->id,
            'notes' => 'Catatan penempatan',
        ]);
    }

    public function test_import_with_an_invalid_row_saves_no_rows_and_reports_the_row(): void
    {
        [$user] = $this->createGenerusImportContext();

        $xlsx = Excel::raw(new class implements FromArray, WithHeadings
        {
            public function headings(): array
            {
                return ['registration_number', 'full_name', 'status'];
            }

            public function array(): array
            {
                return [
                    ['PPG-VALID-001', 'Generus Valid', 'active'],
                    ['PPG-INVALID-001', 'Generus Tidak Valid', 'status-asal'],
                ];
            }
        }, ExcelWriter::XLSX);

        $this->actingAs($user)
            ->from('/generus')
            ->post('/generus/import', [
                'file' => UploadedFile::fake()->createWithContent('generus.xlsx', $xlsx),
            ])
            ->assertRedirect('/generus')
            ->assertSessionHasErrors(['row_3' => 'Baris 3: The selected status is invalid.']);

        $this->assertDatabaseMissing('generus', ['registration_number' => 'PPG-VALID-001']);
        $this->assertDatabaseMissing('generus', ['registration_number' => 'PPG-INVALID-001']);
    }

    public function test_group_scoped_user_only_sees_generus_in_own_group(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);
        $this->createPlacedGenerus($group, 'PPG-SCOPE-001', 'Generus Kelompok Sendiri');
        $this->createPlacedGenerus($otherGroup, 'PPG-SCOPE-002', 'Generus Kelompok Lain');

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->get('/generus')
            ->assertOk()
            ->assertSee('Generus Kelompok Sendiri')
            ->assertDontSee('Generus Kelompok Lain');
    }

    public function test_scoped_export_contains_only_generus_in_scope(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);
        $this->createPlacedGenerus($group, 'PPG-SCOPE-001', 'Generus Kelompok Sendiri');
        $this->createPlacedGenerus($otherGroup, 'PPG-SCOPE-002', 'Generus Kelompok Lain');
        Excel::fake();

        $this->actingAs($this->createScopedUser('group', $group->id))->get('/generus/export');

        Excel::assertDownloaded('generus.xlsx', fn (GenerusExport $export): bool => $export->query()->pluck('registration_number')->all() === ['PPG-SCOPE-001']);
    }

    public function test_generus_forms_have_no_region_field_and_region_is_karawang_timur(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();

        $this->actingAs($user)->get('/generus/create')->assertOk()->assertDontSee('name="region_id"', false);

        $this->actingAs($user)
            ->post('/generus', ['full_name' => 'Tanpa Daerah', 'status' => 'active', 'village_id' => $village->id, 'group_id' => $group->id, 'level_id' => $level->id])
            ->assertRedirect('/generus')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('generus_assignments', ['region_id' => $region->id, 'group_id' => $group->id]);
    }

    public function test_parent_names_become_guardians_shared_between_siblings(): void
    {
        [$user, , $village, $group, $level] = $this->createGenerusImportContext();

        foreach (['Anak Satu', 'Anak Dua'] as $childName) {
            $this->actingAs($user)
                ->post('/generus', [
                    'full_name' => $childName,
                    'father_name' => 'Budi Santoso',
                    'mother_name' => 'siti aminah',
                    'status' => 'active',
                    'village_id' => $village->id,
                    'group_id' => $group->id,
                    'level_id' => $level->id,
                ])
                ->assertSessionHasNoErrors();
        }

        $father = Guardian::where('relationship', 'Ayah')->where('full_name', 'Budi Santoso')->firstOrFail();

        $this->assertSame(1, Guardian::where('relationship', 'Ayah')->count());
        $this->assertSame(1, Guardian::where('relationship', 'Ibu')->count());
        $this->assertEqualsCanonicalizing(['Anak Satu', 'Anak Dua'], $father->generus->pluck('full_name')->all());
    }

    public function test_changing_parent_name_relinks_guardian_and_blank_name_unlinks(): void
    {
        [$user, , $village, $group, $level] = $this->createGenerusImportContext();
        $generus = Generus::create(['registration_number' => 'PPG-WALI-001', 'full_name' => 'Anak Wali', 'father_name' => 'Ayah Lama', 'status' => 'active']);

        $this->assertSame(['Ayah Lama'], $generus->guardians()->pluck('full_name')->all());

        $generus->update(['father_name' => 'Ayah Baru']);
        $this->assertSame(['Ayah Baru'], $generus->guardians()->pluck('full_name')->all());

        $generus->update(['father_name' => null]);
        $this->assertSame(0, $generus->guardians()->count());
    }

    public function test_parent_suggestions_return_existing_names_with_children(): void
    {
        [$user] = $this->createGenerusImportContext();
        Generus::create(['registration_number' => 'PPG-WALI-002', 'full_name' => 'Anak Sugesti', 'father_name' => 'Ahmad Yusuf', 'mother_name' => 'Ahmad Ibu', 'status' => 'active']);

        $this->actingAs($user)
            ->getJson('/generus/parent-suggestions?relationship=Ayah&q=yus')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Ahmad Yusuf')
            ->assertJsonPath('data.0.children', 'Anak Sugesti')
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)->getJson('/generus/parent-suggestions?relationship=Paman')->assertUnprocessable();
    }

    public function test_create_form_lists_only_groups_in_scope(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village, 'Kelompok Tetangga');

        $response = $this->actingAs($this->createScopedUser('group', $group->id))
            ->get('/generus/create')
            ->assertOk();

        $response->assertViewHas('groups', fn ($groups) => $groups->pluck('id')->contains($group->id)
            && ! $groups->pluck('id')->contains($otherGroup->id));

        // The pindah sambung origin picker searches the whole system, so it is not scope-limited.
        $response->assertViewHas('originGroups', fn ($groups) => $groups->pluck('id')->contains($otherGroup->id));
    }

    public function test_village_scoped_user_can_create_generus_in_any_group_of_the_village(): void
    {
        [, $region, $village, , $level] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);

        $this->actingAs($this->createScopedUser('village', $village->id))
            ->post('/generus', $this->generusPayload($region, $village, $otherGroup, $level))
            ->assertRedirect('/generus')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('generus', ['full_name' => 'Generus Scope']);
    }

    public function test_group_scoped_user_cannot_create_generus_in_another_group(): void
    {
        [, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->from('/generus/create')
            ->post('/generus', $this->generusPayload($region, $village, $otherGroup, $level))
            ->assertRedirect('/generus/create')
            ->assertSessionHasErrors(['group_id' => 'Kelompok yang dipilih berada di luar wilayah akses Anda.']);

        $this->assertDatabaseMissing('generus', ['full_name' => 'Generus Scope']);
    }

    public function test_scoped_import_rejects_row_that_overwrites_generus_outside_scope(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);
        $outsider = $this->createPlacedGenerus($otherGroup, 'PPG-SCOPE-002', 'Generus Kelompok Lain');

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->from('/generus')
            ->post('/generus/import', [
                'file' => $this->xlsxWithRows([
                    ['registration_number' => 'PPG-SCOPE-002', 'full_name' => 'Nama Ditimpa', 'status' => 'active'],
                ]),
            ])
            ->assertSessionHasErrors(['row_2' => 'Baris 2: Generus dengan nomor registrasi ini berada di luar wilayah akses Anda.']);

        $this->assertSame('Generus Kelompok Lain', $outsider->fresh()->full_name);
    }

    public function test_scoped_import_rejects_placement_outside_scope(): void
    {
        [, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->from('/generus')
            ->post('/generus/import', [
                'file' => $this->xlsxWithRows([[
                    'registration_number' => 'PPG-SCOPE-003',
                    'full_name' => 'Generus Baru',
                    'status' => 'active',
                    'region_code' => $region->code,
                    'village_code' => $village->code,
                    'group_code' => $otherGroup->code,
                    'level_code' => $level->code,
                ]]),
            ])
            ->assertSessionHasErrors(['row_2' => 'Baris 2: Penempatan berada di luar wilayah akses Anda.']);

        $this->assertDatabaseMissing('generus', ['registration_number' => 'PPG-SCOPE-003']);
    }

    public function test_generus_list_is_paginated_by_25(): void
    {
        [$user, , , $group] = $this->createGenerusImportContext();

        for ($number = 1; $number <= 26; $number++) {
            $generus = $this->createPlacedGenerus($group, sprintf('PPG-PAGE-%03d', $number), sprintf('Generus Halaman %03d', $number));
            $generus->forceFill(['created_at' => now()->subMinutes(30 - $number)])->save();
        }

        $this->actingAs($user)
            ->get('/generus')
            ->assertOk()
            ->assertSee('Generus Halaman 026')
            ->assertDontSee('Generus Halaman 001');

        $this->actingAs($user)
            ->get('/generus?page=2')
            ->assertOk()
            ->assertSee('Generus Halaman 001')
            ->assertDontSee('Generus Halaman 026');
    }

    public function test_store_retries_when_registration_number_collides(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $attempts = 0;

        Generus::creating(function () use (&$attempts): void {
            $attempts++;

            if ($attempts === 1) {
                throw new UniqueConstraintViolationException('sqlite', 'insert into "generus"', [], new Exception('UNIQUE constraint failed: generus.registration_number'));
            }
        });

        $this->actingAs($user)
            ->post('/generus', $this->generusPayload($region, $village, $group, $level))
            ->assertRedirect('/generus');

        $this->assertSame(2, $attempts);
        $this->assertDatabaseHas('generus', ['full_name' => 'Generus Scope']);
    }

    public function test_detail_page_shows_biodata_and_placement_history(): void
    {
        [$user, , , $group] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-SHOW-001', 'Generus Detail');
        $generus->update(['father_name' => 'Ayah Detail']);

        $this->actingAs($user)
            ->get("/generus/{$generus->id}")
            ->assertOk()
            ->assertSee('Generus Detail')
            ->assertSee('Ayah Detail')
            ->assertSee('Kelompok Import');
    }

    public function test_edit_form_is_prefilled_with_current_data(): void
    {
        [$user, , , $group] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-EDIT-005', 'Generus Prefill');

        $this->actingAs($user)
            ->get("/generus/{$generus->id}/edit")
            ->assertOk()
            ->assertSee('value="Generus Prefill"', false)
            ->assertSee('value="PPG-EDIT-005"', false)
            ->assertSee('<option value="'.$group->id.'" selected>', false);
    }

    public function test_scoped_user_gets_404_for_generus_outside_scope(): void
    {
        [, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);
        $outsider = $this->createPlacedGenerus($otherGroup, 'PPG-SCOPE-002', 'Generus Kelompok Lain');
        $user = $this->createScopedUser('group', $group->id);

        $this->actingAs($user)->get("/generus/{$outsider->id}")->assertNotFound();
        $this->actingAs($user)->get("/generus/{$outsider->id}/edit")->assertNotFound();
        $this->actingAs($user)
            ->put("/generus/{$outsider->id}", $this->generusPayload($region, $village, $group, $level))
            ->assertNotFound();
        $this->actingAs($user)->delete("/generus/{$outsider->id}")->assertNotFound();

        $this->assertNotSoftDeleted($outsider);
        $this->assertSame('Generus Kelompok Lain', $outsider->fresh()->full_name);
    }

    public function test_update_without_placement_change_keeps_current_placement(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-EDIT-001', 'Nama Lama');
        $generus->assignments()->update(['level_id' => $level->id]);

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", [
                ...$this->generusPayload($region, $village, $group, $level),
                'full_name' => 'Nama Baru',
                'registration_number' => 'DIUBAH',
                'nis' => 'DIUBAH',
            ])
            ->assertRedirect("/generus/{$generus->id}")
            ->assertSessionHasNoErrors();

        $generus->refresh();
        $this->assertSame('Nama Baru', $generus->full_name);
        $this->assertSame('PPG-EDIT-001', $generus->registration_number);
        $this->assertNotSame('DIUBAH', $generus->nis);
        $this->assertSame(1, $generus->assignments()->count());
    }

    public function test_update_with_new_group_ends_old_placement_and_records_new_one(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $newGroup = $this->createGroupIn($village);
        $generus = $this->createPlacedGenerus($group, 'PPG-EDIT-002', 'Generus Mutasi');
        $oldAssignment = $generus->assignments()->firstOrFail();

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", $this->generusPayload($region, $village, $newGroup, $level))
            ->assertRedirect("/generus/{$generus->id}");

        $this->assertDatabaseHas('generus_assignments', [
            'id' => $oldAssignment->id,
            'ended_at' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('generus_assignments', [
            'generus_id' => $generus->id,
            'group_id' => $newGroup->id,
            'ended_at' => null,
        ]);
    }

    public function test_group_scoped_user_cannot_move_generus_to_another_group(): void
    {
        [, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);
        $generus = $this->createPlacedGenerus($group, 'PPG-EDIT-003', 'Generus Kelompok Sendiri');

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->put("/generus/{$generus->id}", $this->generusPayload($region, $village, $otherGroup, $level))
            ->assertSessionHasErrors(['group_id' => 'Kelompok yang dipilih berada di luar wilayah akses Anda.']);

        $this->assertSame(1, $generus->assignments()->count());
    }

    public function test_status_other_than_transfer_clears_transfer_destination(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-EDIT-004', 'Generus Kembali Aktif');
        $generus->update(['status' => 'pindah_sambung', 'transfer_destination' => 'external']);

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", [
                ...$this->generusPayload($region, $village, $group, $level),
                'transfer_destination' => 'external',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($generus->fresh()->transfer_destination);
    }

    public function test_marking_pindah_sambung_internal_without_destination_leaves_generus_pending(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-PS-001', 'Generus Menunggu Pindah');
        $oldAssignment = $generus->assignments()->firstOrFail();

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", [
                'full_name' => 'Generus Menunggu Pindah',
                'status' => 'pindah_sambung',
                'transfer_destination' => 'internal',
            ])
            ->assertRedirect("/generus/{$generus->id}")
            ->assertSessionHasNoErrors();

        $generus->refresh();
        $this->assertSame('pindah_sambung', $generus->status);
        $this->assertSame('internal', $generus->transfer_destination);
        $this->assertDatabaseHas('generus_assignments', [
            'id' => $oldAssignment->id,
            'ended_at' => now()->toDateString(),
        ]);
        $this->assertSame(1, $generus->assignments()->count());
    }

    public function test_scoped_user_can_mark_pindah_sambung_without_destination(): void
    {
        [, , , $group] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-PS-002', 'Generus Kelompok Sendiri');

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->put("/generus/{$generus->id}", [
                'full_name' => 'Generus Kelompok Sendiri',
                'status' => 'pindah_sambung',
                'transfer_destination' => 'internal',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('pindah_sambung', $generus->fresh()->status);
    }

    public function test_pending_transfers_lists_only_generus_who_left_the_given_group(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);

        $pending = $this->createPlacedGenerus($group, 'PPG-PS-003', 'Generus Menunggu');
        $pending->update(['status' => 'pindah_sambung', 'transfer_destination' => 'internal']);
        $pending->assignments()->firstOrFail()->update(['ended_at' => now()->toDateString()]);

        $stillActive = $this->createPlacedGenerus($group, 'PPG-PS-004', 'Generus Masih Aktif');

        $pendingElsewhere = $this->createPlacedGenerus($otherGroup, 'PPG-PS-005', 'Generus Kelompok Lain');
        $pendingElsewhere->update(['status' => 'pindah_sambung', 'transfer_destination' => 'internal']);
        $pendingElsewhere->assignments()->firstOrFail()->update(['ended_at' => now()->toDateString()]);

        $response = $this->actingAs($this->createScopedUser('group', $group->id))
            ->getJson("/generus/pending-transfers?group_id={$group->id}")
            ->assertOk();

        $response->assertJsonFragment(['full_name' => 'Generus Menunggu']);
        $response->assertJsonMissing(['full_name' => 'Generus Masih Aktif']);
        $response->assertJsonMissing(['full_name' => 'Generus Kelompok Lain']);
    }

    public function test_receive_transfer_keeps_identity_but_moves_generus_to_new_group(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $destinationGroup = $this->createGroupIn($village, 'Kelompok Tujuan');

        $pending = $this->createPlacedGenerus($group, 'PPG-PS-006', 'Generus Sebelum Pindah');
        $pending->update(['status' => 'pindah_sambung', 'transfer_destination' => 'internal', 'nis' => 'PPG-PS-006']);
        $oldAssignment = $pending->assignments()->firstOrFail();
        $oldAssignment->update(['ended_at' => now()->toDateString()]);

        $this->actingAs($user)
            ->post('/generus/receive-transfer', [
                'generus_id' => $pending->id,
                'full_name' => 'Generus Setelah Pindah',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $destinationGroup->id,
                'level_id' => $level->id,
            ])
            ->assertRedirect("/generus/{$pending->id}")
            ->assertSessionHasNoErrors();

        $pending->refresh();
        $this->assertSame('active', $pending->status);
        $this->assertNull($pending->transfer_destination);
        $this->assertSame('Generus Setelah Pindah', $pending->full_name);
        $this->assertSame('PPG-PS-006', $pending->registration_number);
        $this->assertSame('PPG-PS-006', $pending->nis);

        $this->assertDatabaseHas('generus_assignments', [
            'id' => $oldAssignment->id,
            'ended_at' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('generus_assignments', [
            'generus_id' => $pending->id,
            'group_id' => $destinationGroup->id,
            'ended_at' => null,
        ]);
    }

    public function test_receive_transfer_rejects_generus_not_pending(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $activeGenerus = $this->createPlacedGenerus($group, 'PPG-PS-007', 'Generus Aktif');

        $this->actingAs($user)
            ->post('/generus/receive-transfer', [
                'generus_id' => $activeGenerus->id,
                'full_name' => 'Generus Aktif',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
            ])
            ->assertSessionHasErrors('generus_id');

        $this->assertSame('active', $activeGenerus->fresh()->status);
    }

    public function test_scoped_user_cannot_receive_transfer_outside_scope(): void
    {
        [, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $outsideGroup = $this->createGroupIn($village, 'Kelompok Luar Cakupan');

        $pending = $this->createPlacedGenerus($group, 'PPG-PS-008', 'Generus Menunggu Cakupan');
        $pending->update(['status' => 'pindah_sambung', 'transfer_destination' => 'internal']);
        $pending->assignments()->firstOrFail()->update(['ended_at' => now()->toDateString()]);

        $this->actingAs($this->createScopedUser('group', $outsideGroup->id))
            ->post('/generus/receive-transfer', [
                'generus_id' => $pending->id,
                'full_name' => 'Generus Menunggu Cakupan',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
            ])
            ->assertSessionHasErrors(['group_id' => 'Kelompok yang dipilih berada di luar wilayah akses Anda.']);

        $this->assertSame('pindah_sambung', $pending->fresh()->status);
    }

    public function test_delete_soft_deletes_generus_and_hides_it_from_the_list(): void
    {
        [$user, , , $group] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-DEL-001', 'Generus Dihapus');

        $this->actingAs($user)
            ->delete("/generus/{$generus->id}")
            ->assertRedirect('/generus');

        $this->assertSoftDeleted($generus);
        $this->actingAs($user)->get('/generus')->assertDontSee('PPG-DEL-001');
    }

    public function test_new_registration_number_skips_numbers_of_deleted_generus(): void
    {
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $deleted = $this->createPlacedGenerus($group, now()->format('ym').'0001', 'Generus Dihapus');
        $deleted->delete();

        $this->actingAs($user)
            ->post('/generus', $this->generusPayload($region, $village, $group, $level))
            ->assertRedirect('/generus');

        $this->assertDatabaseHas('generus', [
            'full_name' => 'Generus Scope',
            'registration_number' => now()->format('ym').'0002',
        ]);
    }

    public function test_import_rejects_registration_number_of_deleted_generus(): void
    {
        [$user, , , $group] = $this->createGenerusImportContext();
        $this->createPlacedGenerus($group, 'PPG-DEL-002', 'Generus Dihapus')->delete();

        $this->actingAs($user)
            ->from('/generus')
            ->post('/generus/import', [
                'file' => $this->xlsxWithRows([
                    ['registration_number' => 'PPG-DEL-002', 'full_name' => 'Generus Dihidupkan', 'status' => 'active'],
                ]),
            ])
            ->assertSessionHasErrors(['row_2' => 'Baris 2: Generus dengan nomor registrasi ini sudah dihapus.']);
    }

    public function test_uploaded_photo_is_stored_privately_and_shown_on_the_id_card(): void
    {
        Storage::fake();
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, '26090001', 'Generus Berfoto');

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", [
                ...$this->generusPayload($region, $village, $group, $level),
                'full_name' => 'Generus Berfoto',
                'photo' => $this->fakePhoto(),
            ])
            ->assertSessionHasNoErrors();

        $photoPath = $generus->fresh()->photo;
        Storage::assertExists($photoPath);
        $this->assertStringStartsWith(Generus::PHOTO_DIRECTORY.'/', $photoPath);

        $this->actingAs($user)
            ->get("/generus/{$generus->id}/id-card")
            ->assertOk()
            ->assertSee('Kartu Identitas Generus')
            ->assertSee('Generus Berfoto')
            ->assertSee('26090001')
            ->assertSee('src="data:image/svg+xml;base64,', false)
            ->assertSee('src="data:image/png;base64,'.base64_encode(Storage::get($photoPath)).'"', false)
            ->assertSee('images/logo-ppg-karawang-timur.png');
    }

    public function test_replacing_photo_deletes_the_old_file_and_keeping_it_leaves_it_untouched(): void
    {
        Storage::fake();
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-FOTO-002', 'Generus Ganti Foto');
        $oldPhotoPath = Storage::putFileAs(Generus::PHOTO_DIRECTORY, $this->fakePhoto(), 'lama.png');
        $generus->update(['photo' => $oldPhotoPath]);
        $payload = $this->generusPayload($region, $village, $group, $level);

        $this->actingAs($user)->put("/generus/{$generus->id}", $payload)->assertSessionHasNoErrors();

        $this->assertSame($oldPhotoPath, $generus->fresh()->photo);

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", [...$payload, 'photo' => $this->fakePhoto()])
            ->assertSessionHasNoErrors();

        Storage::assertMissing($oldPhotoPath);
        Storage::assertExists($generus->fresh()->photo);
    }

    public function test_photo_must_be_an_image(): void
    {
        Storage::fake();
        [$user, $region, $village, $group, $level] = $this->createGenerusImportContext();
        $generus = $this->createPlacedGenerus($group, 'PPG-FOTO-003', 'Generus Salah Foto');

        $this->actingAs($user)
            ->put("/generus/{$generus->id}", [
                ...$this->generusPayload($region, $village, $group, $level),
                'photo' => UploadedFile::fake()->createWithContent('foto.pdf', '%PDF-1.4'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($generus->fresh()->photo);
    }

    public function test_scoped_user_gets_404_for_id_card_of_generus_outside_scope(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $outsider = $this->createPlacedGenerus($this->createGroupIn($village), 'PPG-SCOPE-003', 'Generus Luar');

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->get("/generus/{$outsider->id}/id-card")
            ->assertNotFound();
    }

    private function fakePhoto(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'foto.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
        );
    }

    private function createScopedUser(string $scopeType, int $scopeId): User
    {
        $role = Role::create([
            'name' => 'Admin Wilayah',
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ]);

        $role->permissions()->attach(Permission::query()->where('slug', 'manage-generus')->value('id'));

        $user = User::create([
            'name' => 'Admin Wilayah',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, $scopeType, $scopeId);

        return $user;
    }

    private function createGroupIn(Village $village, string $name = 'Kelompok Lain'): Group
    {
        return Group::create([
            'village_id' => $village->id,
            'name' => $name,
            'code' => fake()->unique()->bothify('K02-###'),
            'is_active' => true,
        ]);
    }

    private function createPlacedGenerus(Group $group, string $registrationNumber, string $fullName): Generus
    {
        $generus = Generus::create([
            'registration_number' => $registrationNumber,
            'full_name' => $fullName,
            'status' => 'active',
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'region_id' => $group->village->region_id,
            'village_id' => $group->village_id,
            'group_id' => $group->id,
        ]);

        return $generus;
    }

    /**
     * @return array<string, mixed>
     */
    private function generusPayload(Region $region, Village $village, Group $group, Level $level): array
    {
        return [
            'full_name' => 'Generus Scope',
            'status' => 'active',
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'level_id' => $level->id,
        ];
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function xlsxWithRows(array $rows): UploadedFile
    {
        $xlsx = Excel::raw(new class($rows) implements FromArray, WithHeadings
        {
            /**
             * @param  list<array<string, string>>  $rows
             */
            public function __construct(private array $rows) {}

            public function headings(): array
            {
                return array_keys($this->rows[0]);
            }

            public function array(): array
            {
                return array_map('array_values', $this->rows);
            }
        }, ExcelWriter::XLSX);

        return UploadedFile::fake()->createWithContent('generus.xlsx', $xlsx);
    }

    /**
     * @return array{0: User, 1: Region, 2: Village, 3: Group, 4: Level}
     */
    private function createGenerusImportContext(): array
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'Manage Generus',
            'slug' => 'manage-generus',
            'module' => 'generus',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id);

        $region = Region::create([
            'name' => 'Karawang Timur',
            'code' => fake()->unique()->bothify('KRT-###'),
            'is_active' => true,
        ]);

        $village = Village::create([
            'region_id' => $region->id,
            'name' => 'Desa Import',
            'code' => fake()->unique()->bothify('DSA-###'),
            'is_active' => true,
        ]);

        $group = Group::create([
            'village_id' => $village->id,
            'name' => 'Kelompok Import',
            'code' => fake()->unique()->bothify('K01-###'),
            'is_active' => true,
        ]);

        $level = Level::create([
            'name' => 'Kelas Import',
            'code' => fake()->unique()->bothify('KELAS-###'),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return [$user, $region, $village, $group, $level];
    }
}
