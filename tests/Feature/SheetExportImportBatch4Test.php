<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Communication;
use App\Models\Generus;
use App\Models\ParentCommunication;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Support\Sheets\AnnouncementSheet;
use App\Support\Sheets\CommunicationSheet;
use App\Support\Sheets\ParentCommunicationSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SheetExportImportBatch4Test extends TestCase
{
    use RefreshDatabase;

    public function test_every_batch_four_module_can_be_exported_as_xlsx(): void
    {
        $user = $this->globalAdmin();

        $academicYear = $this->makeAcademicYear();
        $training = Training::create(['title' => 'Workshop Kurikulum', 'type' => 'workshop', 'academic_year_id' => $academicYear->id, 'status' => 'scheduled']);
        Communication::create(['training_id' => $training->id, 'channel' => 'whatsapp', 'subject' => 'Undangan', 'message' => 'Mohon hadir', 'status' => 'sent']);

        $generus = Generus::create(['registration_number' => 'PPG-FIX-001', 'full_name' => 'Generus Fixture', 'father_name' => 'Bapak Fulan', 'status' => 'active']);
        ParentCommunication::create([
            'generus_id' => $generus->id,
            'guardian_id' => $generus->guardians()->firstOrFail()->id,
            'channel' => 'WhatsApp',
            'communicated_at' => '2026-01-15',
            'subject' => 'Perkembangan anak',
            'message' => 'Anak menunjukkan kemajuan baik',
        ]);

        Announcement::create(['title' => 'Pengumuman Libur', 'body' => 'Libur semester', 'template' => 'default', 'status' => 'published', 'user_id' => $user->id]);

        $downloads = [
            '/communications/export' => 'komunikasi.xlsx',
            '/parent-communications/export' => 'komunikasi-orang-tua.xlsx',
            '/announcements/export' => 'berita.xlsx',
        ];

        foreach ($downloads as $url => $filename) {
            $this->actingAs($user)->get($url)->assertDownload($filename);
        }
    }

    public function test_communication_sheet_import_resolves_training_by_title_and_is_idempotent(): void
    {
        $academicYear = $this->makeAcademicYear();
        $training = Training::create(['title' => 'Workshop Kurikulum', 'type' => 'workshop', 'academic_year_id' => $academicYear->id, 'status' => 'scheduled']);
        $sheet = new CommunicationSheet;

        $sheet->importRow([
            'training_title' => 'Workshop Kurikulum',
            'channel' => 'whatsapp',
            'subject' => 'Undangan',
            'message' => 'Mohon hadir',
            'status' => 'sent',
        ]);

        $sheet->importRow([
            'training_title' => 'Workshop Kurikulum',
            'channel' => 'whatsapp',
            'subject' => 'Undangan',
            'message' => 'Mohon hadir tepat waktu',
            'status' => 'sent',
        ]);

        $this->assertDatabaseCount('communications', 1);
        $this->assertDatabaseHas('communications', ['training_id' => $training->id, 'message' => 'Mohon hadir tepat waktu']);
    }

    public function test_communication_sheet_import_rejects_unknown_training_title(): void
    {
        $sheet = new CommunicationSheet;

        $error = $sheet->importRow([
            'training_title' => 'Tidak Ada',
            'channel' => 'whatsapp',
            'subject' => 'Undangan',
            'message' => 'Mohon hadir',
            'status' => 'sent',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseCount('communications', 0);
    }

    public function test_parent_communication_sheet_import_resolves_generus_and_its_own_guardian(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-PC-001', 'full_name' => 'Generus PC', 'father_name' => 'Bapak Fulan', 'status' => 'active']);
        $guardian = $generus->guardians()->firstOrFail();
        $sheet = new ParentCommunicationSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'PPG-PC-001',
            'guardian_full_name' => $guardian->full_name,
            'channel' => 'WhatsApp',
            'communicated_at' => '2026-01-15',
            'subject' => 'Perkembangan anak',
            'message' => 'Anak menunjukkan kemajuan baik',
        ]);

        $this->assertNull($error);
        $this->assertDatabaseHas('parent_communications', ['generus_id' => $generus->id, 'guardian_id' => $guardian->id]);
    }

    public function test_parent_communication_sheet_import_rejects_a_guardian_that_is_not_linked_to_the_generus(): void
    {
        $generus = Generus::create(['registration_number' => 'PPG-PC-002', 'full_name' => 'Generus PC 2', 'father_name' => 'Bapak Someone', 'status' => 'active']);
        Generus::create(['registration_number' => 'PPG-PC-003', 'full_name' => 'Generus PC 3', 'father_name' => 'Bapak Lain', 'status' => 'active']);
        $sheet = new ParentCommunicationSheet;

        $error = $sheet->importRow([
            'generus_registration_number' => 'PPG-PC-002',
            'guardian_full_name' => 'Bapak Lain',
            'channel' => 'WhatsApp',
            'communicated_at' => '2026-01-15',
            'subject' => null,
            'message' => 'Pesan',
        ]);

        $this->assertNotNull($error);
        $this->assertDatabaseCount('parent_communications', 0);
    }

    public function test_announcement_sheet_import_creates_and_updates_by_title_without_touching_the_image(): void
    {
        $user = $this->globalAdmin();
        $sheet = new AnnouncementSheet($user);

        $sheet->importRow([
            'title' => 'Pengumuman Libur',
            'body' => 'Libur semester ganjil',
            'template' => 'default',
            'status' => 'published',
            'published_at' => '2026-01-01 08:00:00',
        ]);

        $this->assertDatabaseHas('announcements', ['title' => 'Pengumuman Libur', 'status' => 'published', 'user_id' => $user->id]);

        $sheet->importRow([
            'title' => 'Pengumuman Libur',
            'body' => 'Libur semester ganjil diperpanjang',
            'template' => 'highlight',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->assertDatabaseCount('announcements', 1);
        $this->assertDatabaseHas('announcements', ['title' => 'Pengumuman Libur', 'body' => 'Libur semester ganjil diperpanjang', 'template' => 'highlight', 'status' => 'draft']);
    }

    private function globalAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);

        foreach (['manage-communication', 'manage-parent-communications', 'manage-announcements'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true]);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::create(['name' => 'Admin PPG', 'email' => 'admin@ppg.test', 'password' => Hash::make('password123'), 'status' => 'active']);
        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function makeAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026-'.uniqid(),
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);
    }
}
