<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
