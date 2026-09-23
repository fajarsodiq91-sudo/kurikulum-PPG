<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        $permissions = collect([
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'module' => 'dashboard'],
            ['name' => 'View Reports', 'slug' => 'view-reports', 'module' => 'reports'],
            ['name' => 'View Master Data', 'slug' => 'view-master-data', 'module' => 'master-data'],
            ['name' => 'Manage Users', 'slug' => 'manage-users', 'module' => 'users'],
            ['name' => 'Manage Generus', 'slug' => 'manage-generus', 'module' => 'generus'],
            ['name' => 'Manage Teachers', 'slug' => 'manage-teachers', 'module' => 'teachers'],
            ['name' => 'Manage Guardians', 'slug' => 'manage-guardians', 'module' => 'guardians'],
            ['name' => 'Manage Curriculum', 'slug' => 'manage-curriculum', 'module' => 'curriculum'],
            ['name' => 'Manage Learning Materials', 'slug' => 'manage-learning-materials', 'module' => 'learning-materials'],
            ['name' => 'Manage Learning Sessions', 'slug' => 'manage-learning-sessions', 'module' => 'learning-sessions'],
            ['name' => 'Manage Learning Attendance', 'slug' => 'manage-learning-attendance', 'module' => 'learning-attendance'],
            ['name' => 'Manage Evaluations', 'slug' => 'manage-evaluations', 'module' => 'evaluations'],
            ['name' => 'Manage Training', 'slug' => 'manage-training', 'module' => 'training'],
            ['name' => 'Manage Communication', 'slug' => 'manage-communication', 'module' => 'communication'],
            ['name' => 'Manage Munaqosah', 'slug' => 'manage-munaqosah', 'module' => 'munaqosah'],
            ['name' => 'Manage Report Cards', 'slug' => 'manage-report-cards', 'module' => 'report-cards'],
            ['name' => 'Manage Follow-ups', 'slug' => 'manage-follow-ups', 'module' => 'follow-ups'],
            ['name' => 'Manage Progress Tracking', 'slug' => 'manage-progress-tracking', 'module' => 'progress-tracking'],
            ['name' => 'Manage Milestones', 'slug' => 'manage-milestones', 'module' => 'milestones'],
            ['name' => 'Manage Annual Audit', 'slug' => 'manage-annual-audit', 'module' => 'annual-audit'],
            ['name' => 'Manage Organization Units', 'slug' => 'manage-organization-units', 'module' => 'organization'],
            ['name' => 'Manage Assignments', 'slug' => 'manage-assignments', 'module' => 'assignments'],
            ['name' => 'Manage Activity Schedules', 'slug' => 'manage-activity-schedules', 'module' => 'activity'],
            ['name' => 'Manage Activity Executions', 'slug' => 'manage-activity-executions', 'module' => 'activity'],
        ])->map(fn (array $permission) => Permission::updateOrCreate(
            ['slug' => $permission['slug']],
            [...$permission, 'is_active' => true],
        ));

        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_active' => true],
        );

        $role->update(['is_active' => true]);

        $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));

        $admin = User::updateOrCreate(
            ['email' => 'admin@ppg.test'],
            [
                'name' => 'Admin PPG',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
        );

        $admin->assignRole($role->id);
    }
}
