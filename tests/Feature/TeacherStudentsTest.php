<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FollowUp;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\ParentCommunication;
use App\Models\Permission;
use App\Models\Region;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherStudentsTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Group $otherGroup;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $village = Village::create(['region_id' => Region::karawangTimur()->id, 'name' => 'Desa A', 'is_active' => true]);
        $this->group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 1', 'is_active' => true]);
        $this->otherGroup = Group::create(['village_id' => $village->id, 'name' => 'Kelompok 2', 'is_active' => true]);
        $this->teacher = Teacher::create([
            'registration_number' => '260999001',
            'name' => 'Ustadz Pembina',
            'status' => 'active',
            'village_id' => $village->id,
            'group_id' => $this->group->id,
        ]);
    }

    private function placedGenerus(string $name, Group $group): Generus
    {
        $generus = Generus::create(['registration_number' => fake()->unique()->numerify('2609####'), 'full_name' => $name, 'status' => 'active']);
        GenerusAssignment::create(['generus_id' => $generus->id, 'region_id' => Region::karawangTimur()->id, 'village_id' => $group->village_id, 'group_id' => $group->id]);

        return $generus;
    }

    private function teacherUser(): User
    {
        return User::where('teacher_id', $this->teacher->id)->firstOrFail();
    }

    private function adminUser(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin-x', 'is_active' => true]);
        foreach (['manage-teachers', 'manage-report-cards'] as $slug) {
            $role->permissions()->attach(Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true])->id);
        }

        $user = User::create(['name' => 'Admin', 'email' => 'admin-x@ppg.test', 'password' => 'password123', 'status' => 'active']);
        $user->assignRole($role->id);

        return $user;
    }

    public function test_teacher_ticks_students_from_own_group_only(): void
    {
        $own = $this->placedGenerus('Murid Satu', $this->group);
        $second = $this->placedGenerus('Murid Dua', $this->group);
        $outsider = $this->placedGenerus('Murid Kelompok Lain', $this->otherGroup);

        $this->actingAs($this->teacherUser())
            ->get('/my-students')
            ->assertOk()
            ->assertSee('Murid Satu')
            ->assertDontSee('Murid Kelompok Lain');

        $this->actingAs($this->teacherUser())
            ->put('/my-students', ['student_ids' => [$own->id, $outsider->id]])
            ->assertRedirect('/my-students');

        $this->assertSame([$own->id], $this->teacher->students()->pluck('generus.id')->all());

        $this->actingAs($this->teacherUser())->put('/my-students', ['student_ids' => [$second->id]]);
        $this->assertSame([$second->id], $this->teacher->students()->pluck('generus.id')->all());
    }

    public function test_admin_can_manage_a_teachers_students(): void
    {
        $student = $this->placedGenerus('Murid Admin', $this->group);

        $this->actingAs($this->adminUser())
            ->put("/teachers/{$this->teacher->id}/students", ['student_ids' => [$student->id]])
            ->assertRedirect("/teachers/{$this->teacher->id}/students");

        $this->assertSame(1, $this->teacher->students()->count());
    }

    public function test_teacher_only_sees_and_fills_records_of_own_students(): void
    {
        $mine = $this->placedGenerus('Murid Saya', $this->group);
        $other = $this->placedGenerus('Murid Orang Lain', $this->group);
        $this->teacher->students()->attach($mine->id);
        $year = AcademicYear::create(['name' => '2025/2026', 'code' => 'TA', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'S1', 'code' => 'S1', 'sort_order' => 1, 'is_active' => true]);
        $otherCard = ReportCard::create(['generus_id' => $other->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id]);
        ReportCard::create(['generus_id' => $mine->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id]);

        $teacher = $this->teacherUser();

        $this->actingAs($teacher)->get('/report-cards')->assertOk()->assertSee('Murid Saya')->assertDontSee('Murid Orang Lain');
        $this->actingAs($teacher)->get("/report-cards/{$otherCard->id}/edit")->assertNotFound();

        $followUp = ['title' => 'Bina bacaan', 'priority' => 'low', 'follow_up_date' => '2026-10-01', 'status' => 'scheduled'];
        $this->actingAs($teacher)->post('/follow-ups', [...$followUp, 'generus_id' => $other->id, 'teacher_id' => $this->teacher->id])->assertSessionHasErrors('generus_id');
        $this->actingAs($teacher)->post('/follow-ups', [...$followUp, 'generus_id' => $mine->id, 'teacher_id' => 999])->assertSessionHasNoErrors();

        $this->assertSame($this->teacher->id, FollowUp::firstOrFail()->teacher_id);
    }

    public function test_admin_still_sees_every_students_records(): void
    {
        $student = $this->placedGenerus('Murid Bebas', $this->group);
        $year = AcademicYear::create(['name' => '2025/2026', 'code' => 'TA', 'start_year' => 2025, 'end_year' => 2026, 'is_active' => true]);
        $semester = Semester::create(['academic_year_id' => $year->id, 'name' => 'S1', 'code' => 'S1', 'sort_order' => 1, 'is_active' => true]);
        ReportCard::create(['generus_id' => $student->id, 'academic_year_id' => $year->id, 'semester_id' => $semester->id]);

        $this->actingAs($this->adminUser())->get('/report-cards')->assertOk()->assertSee('Murid Bebas');
    }

    public function test_teacher_records_parent_communication_for_own_student_and_linked_guardian(): void
    {
        $mine = Generus::create(['registration_number' => '26090101', 'full_name' => 'Anak Saya', 'father_name' => 'Pak Budi', 'status' => 'active']);
        $other = Generus::create(['registration_number' => '26090102', 'full_name' => 'Anak Lain', 'father_name' => 'Pak Andi', 'status' => 'active']);
        $this->teacher->students()->attach($mine->id);
        $payload = ['channel' => 'WhatsApp', 'communicated_at' => '2026-09-28', 'message' => 'Menyampaikan perkembangan.'];
        $teacher = $this->teacherUser();

        $this->actingAs($teacher)
            ->post('/parent-communications', [...$payload, 'generus_id' => $mine->id, 'guardian_id' => $mine->guardians()->firstOrFail()->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('parent_communications', ['generus_id' => $mine->id, 'teacher_id' => $this->teacher->id]);

        $this->actingAs($teacher)->post('/parent-communications', [...$payload, 'generus_id' => $other->id])->assertSessionHasErrors('generus_id');
        $this->actingAs($teacher)
            ->post('/parent-communications', [...$payload, 'generus_id' => $mine->id, 'guardian_id' => $other->guardians()->firstOrFail()->id])
            ->assertSessionHasErrors('guardian_id');

        $this->assertSame(1, ParentCommunication::count());
        $this->actingAs($teacher)->get('/parent-communications')->assertOk()->assertSee('Anak Saya')->assertDontSee('Anak Lain');
    }

    public function test_generus_account_cannot_reach_teacher_only_pages(): void
    {
        $student = $this->placedGenerus('Murid Biasa', $this->group);
        $user = User::where('generus_id', $student->id)->firstOrFail();

        $this->actingAs($user)->get('/my-students')->assertForbidden();
        $this->actingAs($user)->get('/parent-communications')->assertForbidden();
    }
}
