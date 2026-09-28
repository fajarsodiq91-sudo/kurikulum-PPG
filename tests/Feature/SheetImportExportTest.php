<?php

namespace Tests\Feature;

use App\Exports\SheetExport;
use App\Imports\SheetImport;
use App\Models\AcademicYear;
use App\Models\Group;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use App\Support\Sheets\AcademicYearSheet;
use App\Support\Sheets\ClassGradeSheet;
use App\Support\Sheets\GroupSheet;
use App\Support\Sheets\GuardianSheet;
use App\Support\Sheets\LevelSheet;
use App\Support\Sheets\SemesterSheet;
use App\Support\Sheets\Sheet;
use App\Support\Sheets\TeacherSheet;
use App\Support\Sheets\VillageSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SheetImportExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Runs rows through the same import class the upload uses, without needing an XLSX file.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    private function importRows(Sheet $sheet, array $rows): array
    {
        $import = new SheetImport($sheet);
        $import->collection(collect($rows)->map(fn (array $row) => collect($row)));

        return $import->errors();
    }

    public function test_village_import_creates_then_updates_by_name_under_karawang_timur(): void
    {
        $this->assertSame([], $this->importRows(new VillageSheet, [['name' => 'Desa Baru', 'code' => 'DB', 'status' => 'aktif']]));
        $this->assertSame([], $this->importRows(new VillageSheet, [['name' => 'desa baru', 'code' => 'DB2', 'status' => 'nonaktif']]));

        $village = Village::firstOrFail();
        $this->assertSame(1, Village::count());
        $this->assertSame('DB2', $village->code);
        $this->assertFalse((bool) $village->is_active);
        $this->assertSame(Region::karawangTimur()->id, $village->region_id);
    }

    public function test_group_import_needs_an_existing_village_and_reports_the_row(): void
    {
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa A', 'is_active' => true]);

        $errors = $this->importRows(new GroupSheet, [
            ['village_name' => 'Desa A', 'name' => 'Kelompok 1'],
            ['village_name' => 'Desa Hantu', 'name' => 'Kelompok 2'],
        ]);

        $this->assertSame(['row_3'], array_keys($errors));
        $this->assertDatabaseHas('groups', ['village_id' => $village->id, 'name' => 'Kelompok 1']);
    }

    public function test_level_class_grade_and_academic_year_upsert_by_code(): void
    {
        $this->importRows(new LevelSheet, [['name' => 'PAUD', 'code' => 'PD', 'sort_order' => '1']]);
        $this->importRows(new LevelSheet, [['name' => 'PAUD Baru', 'code' => 'PD', 'sort_order' => '2']]);
        $this->importRows(new ClassGradeSheet, [['name' => 'Kelas 4', 'code' => 'SD-4']]);
        $this->importRows(new AcademicYearSheet, [['name' => '2025/2026', 'code' => 'TA1', 'start_year' => '2025', 'end_year' => '2026']]);

        $this->assertSame(1, Level::count());
        $this->assertDatabaseHas('levels', ['code' => 'PD', 'name' => 'PAUD Baru', 'sort_order' => 2]);
        $this->assertDatabaseHas('class_grades', ['code' => 'SD-4']);
        $this->assertDatabaseHas('academic_years', ['code' => 'TA1', 'start_year' => 2025]);

        $errors = $this->importRows(new AcademicYearSheet, [['name' => 'Salah', 'code' => 'TA2', 'start_year' => '2026', 'end_year' => '2025']]);
        $this->assertSame(['row_2'], array_keys($errors));
    }

    public function test_semester_import_resolves_academic_year_by_code(): void
    {
        AcademicYear::create(['name' => '2025/2026', 'code' => 'TA1', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);

        $errors = $this->importRows(new SemesterSheet, [
            ['academic_year_code' => 'TA1', 'name' => 'Semester 1', 'sort_order' => '1'],
            ['academic_year_code' => 'XXX', 'name' => 'Semester 2'],
        ]);

        $this->assertSame(['row_3'], array_keys($errors));
        $this->assertSame(1, Semester::count());
    }

    public function test_teacher_import_creates_with_generated_number_and_updates_existing(): void
    {
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa A', 'is_active' => true]);
        Group::create(['village_id' => $village->id, 'name' => 'Kelompok 1', 'is_active' => true]);

        $row = ['name' => 'Ustadz Baru', 'email' => 'baru@ppg.test', 'village_name' => 'Desa A', 'group_name' => 'Kelompok 1', 'status' => 'active'];
        $this->assertSame([], $this->importRows(new TeacherSheet, [$row]));

        $teacher = Teacher::firstOrFail();
        $this->assertMatchesRegularExpression('/^\d{4}99\d{3}$/', $teacher->registration_number);
        $this->assertSame($village->id, $teacher->village_id);

        $this->importRows(new TeacherSheet, [[...$row, 'registration_number' => $teacher->registration_number, 'name' => 'Ustadz Diubah', 'status' => 'nonaktif']]);

        $this->assertSame(1, Teacher::count());
        $this->assertSame('Ustadz Diubah', $teacher->fresh()->name);
        $this->assertSame('inactive', $teacher->fresh()->status);
    }

    public function test_teacher_import_rejects_unknown_group_and_placement_outside_user_scope(): void
    {
        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa A', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 1', 'is_active' => true]);
        $otherGroup = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 2', 'is_active' => true]);

        $scoped = $this->createUser('group', $group->id, 'manage-teachers');
        $errors = $this->importRows(new TeacherSheet($scoped), [
            ['name' => 'Guru A', 'village_name' => 'Desa A', 'group_name' => 'Kelompok 2'],
            ['name' => 'Guru B', 'village_name' => 'Desa A', 'group_name' => 'Tidak Ada'],
            ['name' => 'Guru C', 'village_name' => 'Desa A', 'group_name' => 'Kelompok 1'],
        ]);

        $this->assertSame(['row_2', 'row_3'], array_keys($errors));
        $this->assertSame(['Guru C'], Teacher::pluck('name')->all());
        $this->assertNotNull($otherGroup);
    }

    public function test_guardian_import_upserts_by_name_and_relationship(): void
    {
        $this->importRows(new GuardianSheet, [['full_name' => 'Budi', 'relationship' => 'Ayah', 'phone' => '081']]);
        $this->importRows(new GuardianSheet, [['full_name' => 'BUDI', 'relationship' => 'ayah', 'phone' => '082', 'status' => 'nonaktif']]);

        $this->assertSame(1, Guardian::count());
        $this->assertSame('082', Guardian::firstOrFail()->phone);
        $this->assertSame('inactive', Guardian::firstOrFail()->status);
    }

    public function test_export_endpoints_download_for_authorized_users_only(): void
    {
        Excel::fake();
        $manager = $this->createUser('global', null, 'manage-teachers', 'manage-guardians', 'view-master-data', 'manage-master-data');

        foreach (['/teachers/export' => 'guru.xlsx', '/guardians/export' => 'orang-tua-wali.xlsx', '/master-data/villages/export' => 'desa.xlsx', '/master-data/semesters/export' => 'semester.xlsx'] as $url => $file) {
            $this->actingAs($manager)->get($url);
            Excel::assertDownloaded($file, fn (SheetExport $export): bool => $export->headings() !== []);
        }

        $this->actingAs($manager)->get('/master-data/unknown/export')->assertNotFound();
    }

    public function test_guests_cannot_export_teachers(): void
    {
        $this->get('/teachers/export')->assertRedirect('/login');
    }

    public function test_pages_show_the_export_import_bar(): void
    {
        $manager = $this->createUser('global', null, 'manage-teachers', 'manage-guardians', 'view-master-data', 'manage-master-data');

        foreach (['/teachers', '/guardians', '/master-data/villages', '/master-data/class-grades'] as $url) {
            $this->actingAs($manager)->get($url)->assertOk()->assertSee('Export XLSX')->assertSee('Import XLSX');
        }
    }

    private function createUser(string $scopeType, ?int $scopeId, string ...$permissionSlugs): User
    {
        $role = Role::create(['name' => 'Peran '.fake()->unique()->word(), 'slug' => fake()->unique()->slug(), 'is_active' => true]);

        foreach ($permissionSlugs as $slug) {
            $role->permissions()->attach(Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true])->id);
        }

        $user = User::create(['name' => 'Pengguna', 'email' => fake()->unique()->safeEmail(), 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, $scopeType, $scopeId);

        return $user;
    }
}
