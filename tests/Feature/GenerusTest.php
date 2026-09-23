<?php

namespace Tests\Feature;

use App\Exports\GenerusExport;
use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

        $year = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
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
                'school_grade' => '4',
                'learning_class' => 'Kelas 4',
                'educational_level' => 'SD',
                'status' => 'active',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
                'academic_year_id' => $year->id,
                'assignment_status' => 'active',
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

        $this->assertDatabaseHas('generus_assignments', [
            'status' => 'active',
        ]);
    }

    public function test_admin_can_export_generus_as_xlsx(): void
    {
        [$user, $region, $village, $group, $level, $year] = $this->createGenerusImportContext();

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
            'academic_year_id' => $year->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/generus/export')
            ->assertDownload('generus.xlsx');
    }

    public function test_internal_transfer_uses_the_newest_registered_placement(): void
    {
        [$user, $region, $village, $group, $level, $year] = $this->createGenerusImportContext();

        $this->actingAs($user)
            ->post('/generus', [
                'full_name' => 'Generus Pindah Internal',
                'status' => 'pindah_sambung',
                'transfer_destination' => 'internal',
                'region_id' => $region->id,
                'village_id' => $village->id,
                'group_id' => $group->id,
                'level_id' => $level->id,
                'academic_year_id' => $year->id,
                'assignment_status' => 'active',
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
            'status' => 'active',
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
                'assignment_status' => 'active',
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
        [$user, $region, $village, $group, $level, $year] = $this->createGenerusImportContext();

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
            'academic_year_id' => $year->id,
            'status' => 'active',
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
        [$user, $region, $village, $group, $level, $year] = $this->createGenerusImportContext();

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
            'school_grade' => '5',
            'status' => 'active',
        ]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'level_id' => $level->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
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
            'school_grade' => null,
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
            'school_grade' => '5',
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

    public function test_create_form_lists_only_groups_in_scope(): void
    {
        [, , $village, $group] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village, 'Kelompok Tetangga');

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->get('/generus/create')
            ->assertOk()
            ->assertSee('Kelompok Import')
            ->assertDontSee('Kelompok Tetangga');
    }

    public function test_village_scoped_user_can_create_generus_in_any_group_of_the_village(): void
    {
        [, $region, $village, , $level, $year] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);

        $this->actingAs($this->createScopedUser('village', $village->id))
            ->post('/generus', $this->generusPayload($region, $village, $otherGroup, $level, $year))
            ->assertRedirect('/generus')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('generus', ['full_name' => 'Generus Scope']);
    }

    public function test_group_scoped_user_cannot_create_generus_in_another_group(): void
    {
        [, $region, $village, $group, $level, $year] = $this->createGenerusImportContext();
        $otherGroup = $this->createGroupIn($village);

        $this->actingAs($this->createScopedUser('group', $group->id))
            ->from('/generus/create')
            ->post('/generus', $this->generusPayload($region, $village, $otherGroup, $level, $year))
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
        [, $region, $village, $group, $level, $year] = $this->createGenerusImportContext();
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
                    'academic_year_code' => $year->code,
                    'assignment_status' => 'active',
                ]]),
            ])
            ->assertSessionHasErrors(['row_2' => 'Baris 2: Penempatan berada di luar wilayah akses Anda.']);

        $this->assertDatabaseMissing('generus', ['registration_number' => 'PPG-SCOPE-003']);
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
            'status' => 'active',
        ]);

        return $generus;
    }

    /**
     * @return array<string, mixed>
     */
    private function generusPayload(Region $region, Village $village, Group $group, Level $level, AcademicYear $year): array
    {
        return [
            'full_name' => 'Generus Scope',
            'status' => 'active',
            'region_id' => $region->id,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'level_id' => $level->id,
            'academic_year_id' => $year->id,
            'assignment_status' => 'active',
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
     * @return array{0: User, 1: Region, 2: Village, 3: Group, 4: Level, 5: AcademicYear}
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

        $year = AcademicYear::create([
            'name' => '2025/2026',
            'code' => fake()->unique()->bothify('2025-####'),
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        return [$user, $region, $village, $group, $level, $year];
    }
}
