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

class AuthAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_application_root(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_login_and_access_dashboard(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $response = $this->post('/login', [
            'email' => 'admin@ppg.test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    public function test_user_without_permission_cannot_access_dashboard(): void
    {
        $role = Role::create([
            'name' => 'Guru',
            'slug' => 'guru',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $this->post('/login', [
            'email' => 'guru@ppg.test',
            'password' => 'password123',
        ]);

        $this->get('/dashboard')->assertStatus(403);
    }

    public function test_dashboard_counts_only_active_people(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Dashboard Tester',
            'email' => 'dashboard@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id);

        Generus::create([
            'registration_number' => 'G-001',
            'full_name' => 'Generus Aktif',
            'status' => 'active',
        ]);
        Generus::create([
            'registration_number' => 'G-002',
            'full_name' => 'Generus Tidak Aktif',
            'status' => 'inactive',
        ]);
        Teacher::create(['name' => 'Guru Aktif', 'status' => 'active']);
        Teacher::create(['name' => 'Guru Tidak Aktif', 'status' => 'inactive']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('generusCount', 1)
            ->assertViewHas('teacherCount', 1);
    }

    public function test_inactive_role_or_permission_does_not_grant_dashboard_access(): void
    {
        $role = Role::create([
            'name' => 'Guru',
            'slug' => 'guru-inactive',
            'is_active' => false,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard-inactive',
            'module' => 'dashboard',
            'is_active' => false,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Guru Inactive',
            'email' => 'guru-inactive@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $this->assertFalse($user->hasPermission('view-dashboard-inactive'));

        $this->post('/login', [
            'email' => 'guru-inactive@ppg.test',
            'password' => 'password123',
        ]);

        $this->get('/dashboard')->assertStatus(403);
    }

    public function test_soft_deleted_role_or_permission_does_not_grant_dashboard_access(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin-soft-delete',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard-soft-delete',
            'module' => 'dashboard',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Admin Soft Delete',
            'email' => 'admin-soft-delete@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $this->assertTrue($user->hasPermission('view-dashboard-soft-delete'));

        $role->delete();
        $this->assertFalse($user->hasPermission('view-dashboard-soft-delete'));

        $role->restore();
        $permission->delete();
        $this->assertFalse($user->hasPermission('view-dashboard-soft-delete'));
    }

    public function test_inactive_user_does_not_receive_permission(): void
    {
        $role = Role::create([
            'name' => 'Inactive Role',
            'slug' => 'inactive-user-role',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard-inactive-user',
            'module' => 'dashboard',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive-user@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);

        $user->assignRole($role->id);

        $this->assertFalse($user->hasPermission('view-dashboard-inactive-user'));
    }

    public function test_sidebar_hides_modules_without_permission(): void
    {
        $role = Role::create([
            'name' => 'Reports User',
            'slug' => 'reports-user',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Reports',
            'slug' => 'view-reports',
            'module' => 'reports',
            'is_active' => true,
        ]);

        $role->permissions()->attach($permission->id);

        $user = User::create([
            'name' => 'Reports User',
            'email' => 'reports-user@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id);

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Laporan')
            ->assertDontSee('Data Generus')
            ->assertDontSee('Program Kurikulum');
    }

    public function test_integrated_module_pages_are_available_to_authorized_users(): void
    {
        $role = Role::create([
            'name' => 'Organization Manager',
            'slug' => 'organization-manager',
            'is_active' => true,
        ]);

        $permissions = collect([
            ['name' => 'Manage Organization Units', 'slug' => 'manage-organization-units', 'module' => 'organization'],
            ['name' => 'View Master Data', 'slug' => 'view-master-data', 'module' => 'master-data'],
            ['name' => 'Manage Generus', 'slug' => 'manage-generus', 'module' => 'generus'],
            ['name' => 'Manage Teachers', 'slug' => 'manage-teachers', 'module' => 'teachers'],
            ['name' => 'Manage Guardians', 'slug' => 'manage-guardians', 'module' => 'guardians'],
            ['name' => 'Manage Curriculum', 'slug' => 'manage-curriculum', 'module' => 'curriculum'],
            ['name' => 'Manage Learning Materials', 'slug' => 'manage-learning-materials', 'module' => 'learning-materials'],
            ['name' => 'Manage Learning Sessions', 'slug' => 'manage-learning-sessions', 'module' => 'learning-sessions'],
            ['name' => 'Manage Learning Attendance', 'slug' => 'manage-learning-attendance', 'module' => 'learning-attendance'],
            ['name' => 'Manage Progress Tracking', 'slug' => 'manage-progress-tracking', 'module' => 'progress-tracking'],
            ['name' => 'Manage Milestones', 'slug' => 'manage-milestones', 'module' => 'milestones'],
            ['name' => 'Manage Evaluations', 'slug' => 'manage-evaluations', 'module' => 'evaluations'],
            ['name' => 'Manage Munaqosah', 'slug' => 'manage-munaqosah', 'module' => 'munaqosah'],
            ['name' => 'Manage Report Cards', 'slug' => 'manage-report-cards', 'module' => 'report-cards'],
            ['name' => 'Manage Training', 'slug' => 'manage-training', 'module' => 'training'],
            ['name' => 'Manage Communication', 'slug' => 'manage-communication', 'module' => 'communication'],
            ['name' => 'View Reports', 'slug' => 'view-reports', 'module' => 'reports'],
        ])->map(fn (array $permission) => Permission::create([...$permission, 'is_active' => true]));

        $role->permissions()->attach($permissions->pluck('id'));

        $user = User::create([
            'name' => 'Organization Manager',
            'email' => 'organization-manager@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id);

        $this->actingAs($user)
            ->get('/organization-units')
            ->assertOk()
            ->assertViewIs('organization-units.index');

        $this->actingAs($user)
            ->get('/master-data/regions')
            ->assertOk()
            ->assertViewIs('master-data.regions.index');

        $this->actingAs($user)
            ->get('/generus')
            ->assertOk()
            ->assertViewIs('generus.index');

        $this->actingAs($user)
            ->get('/generus/create')
            ->assertOk()
            ->assertViewIs('generus.create');

        $this->actingAs($user)
            ->get('/teachers')
            ->assertOk()
            ->assertViewIs('teachers.index');

        $this->actingAs($user)
            ->get('/guardians')
            ->assertOk()
            ->assertViewIs('guardians.index');

        $this->actingAs($user)
            ->get('/curriculum-programs')
            ->assertOk()
            ->assertViewIs('curriculum-programs.index');

        $this->actingAs($user)
            ->get('/learning-materials')
            ->assertOk()
            ->assertViewIs('learning-materials.index');

        $this->actingAs($user)
            ->get('/learning-sessions')
            ->assertOk()
            ->assertViewIs('learning-sessions.index');

        $this->actingAs($user)
            ->get('/session-attendances')
            ->assertOk()
            ->assertViewIs('session-attendances.index');

        $this->actingAs($user)
            ->get('/progress-tracks')
            ->assertOk()
            ->assertViewIs('progress-tracks.index');

        $this->actingAs($user)
            ->get('/milestones')
            ->assertOk()
            ->assertViewIs('milestones.index');

        $this->actingAs($user)
            ->get('/evaluations')
            ->assertOk()
            ->assertViewIs('evaluations.index');

        $this->actingAs($user)
            ->get('/evaluation-scores')
            ->assertOk()
            ->assertViewIs('evaluation-scores.index');

        $this->actingAs($user)
            ->get('/munaqosahs')
            ->assertOk()
            ->assertViewIs('munaqosahs.index');

        $this->actingAs($user)
            ->get('/report-cards')
            ->assertOk()
            ->assertViewIs('report-cards.index');

        $this->actingAs($user)
            ->get('/trainings')
            ->assertOk()
            ->assertViewIs('trainings.index');

        $this->actingAs($user)
            ->get('/communications')
            ->assertOk()
            ->assertViewIs('communications.index');

        $this->actingAs($user)
            ->get('/reports')
            ->assertOk()
            ->assertViewIs('reports.index');
    }
}
