<?php

namespace Tests\Feature;

use App\Models\Generus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FollowUpAndGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_follow_up_for_generus(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $followUpPermission = Permission::create([
            'name' => 'Manage Follow Up',
            'slug' => 'manage-follow-ups',
            'module' => 'follow-up',
            'is_active' => true,
        ]);

        $role->permissions()->attach($followUpPermission->id);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $generus = Generus::create([
            'registration_number' => 'GEN-013-001',
            'full_name' => 'Generus Follow Up',
            'gender' => 'Perempuan',
            'status' => 'active',
        ]);

        $teacher = Teacher::create([
            'name' => 'Guru Pembina',
            'gender' => 'Laki-laki',
            'phone' => '081234567890',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/follow-ups', [
                'generus_id' => $generus->id,
                'teacher_id' => $teacher->id,
                'title' => 'Follow Up Pembelajaran',
                'priority' => 'high',
                'follow_up_date' => '2026-10-10',
                'status' => 'scheduled',
                'notes' => 'Perlu pendampingan pada materi evaluasi pembelajaran.',
                'next_action' => 'Membimbing latihan soal dan refleksi mingguan',
            ])
            ->assertRedirect('/follow-ups');

        $this->assertDatabaseHas('follow_ups', [
            'generus_id' => $generus->id,
            'teacher_id' => $teacher->id,
            'title' => 'Follow Up Pembelajaran',
            'priority' => 'high',
        ]);
    }
}
