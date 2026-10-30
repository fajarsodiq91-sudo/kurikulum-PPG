<?php

namespace Tests\Feature;

use App\Models\ClassGrade;
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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardGenerusResumeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_active' => true]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ]);

        $generusPermission = Permission::create([
            'name' => 'Manage Generus',
            'slug' => 'manage-generus',
            'module' => 'generus',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$permission->id, $generusPermission->id]);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        return $user;
    }

    private function placeGenerus(Generus $generus, Group $group, ClassGrade $classGrade): void
    {
        $generus->update(['learning_class_id' => $classGrade->id]);

        GenerusAssignment::create([
            'generus_id' => $generus->id,
            'village_id' => $group->village_id,
            'group_id' => $group->id,
            'level_id' => $classGrade->level_id,
            'assigned_at' => now()->toDateString(),
        ]);
    }

    public function test_dashboard_resume_table_shows_counts_per_village_group_and_class_grade(): void
    {
        $user = $this->actingAsAdmin();

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'D1', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok A', 'code' => 'KA', 'is_active' => true]);

        $level = Level::create(['name' => 'PAUD', 'code' => 'PD', 'sort_order' => 1, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => 'Pra PAUD', 'code' => '00', 'sort_order' => 1, 'is_active' => true]);

        $boy = Generus::create(['registration_number' => 'GEN-001', 'full_name' => 'Generus Putra', 'gender' => 'laki-laki', 'status' => 'active']);
        $girl = Generus::create(['registration_number' => 'GEN-002', 'full_name' => 'Generus Putri', 'gender' => 'perempuan', 'status' => 'active']);

        $this->placeGenerus($boy, $group, $classGrade);
        $this->placeGenerus($girl, $group, $classGrade);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $response->assertSee('Resume Jumlah Generus')
            ->assertSee('Desa Satu')
            ->assertSee('Kelompok A')
            ->assertSee('Pra PAUD')
            ->assertSee('Subtotal Desa Desa Satu')
            ->assertSee('Grand Total');

        $response->assertViewHas('resumeGrandTotal', 2);
        $response->assertViewHas('resumeGrandCounts', [$classGrade->id => 2]);
    }

    public function test_dashboard_resume_table_filters_by_gender_and_jenjang(): void
    {
        $user = $this->actingAsAdmin();

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'D1', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok A', 'code' => 'KA', 'is_active' => true]);

        $levelPaud = Level::create(['name' => 'PAUD', 'code' => 'PD', 'sort_order' => 1, 'is_active' => true]);
        $levelRemaja = Level::create(['name' => 'Remaja', 'code' => 'RM', 'sort_order' => 2, 'is_active' => true]);
        $classGradePaud = ClassGrade::create(['level_id' => $levelPaud->id, 'name' => 'Pra PAUD', 'code' => '00', 'sort_order' => 1, 'is_active' => true]);
        $classGradeRemaja = ClassGrade::create(['level_id' => $levelRemaja->id, 'name' => '10', 'code' => '10', 'sort_order' => 1, 'is_active' => true]);

        $boy = Generus::create(['registration_number' => 'GEN-001', 'full_name' => 'Generus Putra', 'gender' => 'laki-laki', 'status' => 'active']);
        $girl = Generus::create(['registration_number' => 'GEN-002', 'full_name' => 'Generus Putri', 'gender' => 'perempuan', 'status' => 'active']);

        $this->placeGenerus($boy, $group, $classGradePaud);
        $this->placeGenerus($girl, $group, $classGradeRemaja);

        $this->actingAs($user)
            ->get('/dashboard?gender=laki-laki&level_id[]='.$levelPaud->id)
            ->assertOk()
            ->assertViewHas('resumeGrandTotal', 1)
            ->assertViewHas('resumeClassGrades', fn ($classGrades) => $classGrades->pluck('id')->all() === [$classGradePaud->id]);

        $this->actingAs($user)
            ->get('/dashboard?level_id[]='.$levelPaud->id.'&level_id[]='.$levelRemaja->id)
            ->assertOk()
            ->assertViewHas('resumeGrandTotal', 2)
            ->assertViewHas('resumeClassGrades', fn ($classGrades) => $classGrades->pluck('id')->sort()->values()->all() === [$classGradePaud->id, $classGradeRemaja->id]);
    }
}
