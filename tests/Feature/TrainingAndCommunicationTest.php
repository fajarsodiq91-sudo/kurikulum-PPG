<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrainingAndCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_training_and_communication_message(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $trainingPermission = Permission::create([
            'name' => 'Manage Training',
            'slug' => 'manage-training',
            'module' => 'training',
            'is_active' => true,
        ]);

        $communicationPermission = Permission::create([
            'name' => 'Manage Communication',
            'slug' => 'manage-communication',
            'module' => 'communication',
            'is_active' => true,
        ]);

        $role->permissions()->attach([$trainingPermission->id, $communicationPermission->id]);

        $user = User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'code' => '2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/trainings', [
                'title' => 'Pelatihan Digital PPG',
                'type' => 'workshop',
                'academic_year_id' => $academicYear->id,
                'scheduled_at' => '2026-10-01',
                'location' => 'Karawang',
                'status' => 'scheduled',
                'notes' => 'Pelatihan penggunaan platform digital',
            ])
            ->assertRedirect('/trainings');

        $this->assertDatabaseHas('trainings', [
            'title' => 'Pelatihan Digital PPG',
            'type' => 'workshop',
        ]);

        $training = Training::query()->first();

        $this->actingAs($user)
            ->post('/communications', [
                'training_id' => $training->id,
                'channel' => 'whatsapp',
                'subject' => 'Pengingat Pelatihan',
                'message' => 'Pelatihan dimulai pukul 08:00',
                'status' => 'sent',
            ])
            ->assertRedirect('/communications');

        $this->assertDatabaseHas('communications', [
            'training_id' => $training->id,
            'subject' => 'Pengingat Pelatihan',
            'channel' => 'whatsapp',
        ]);
    }
}
