<?php

namespace Tests\Feature;

use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_root_always_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_guest_is_redirected_to_login_when_no_guest_role_grants_dashboard_access(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_can_view_dashboard_read_only_when_guest_role_grants_permission(): void
    {
        $guestRole = Role::create([
            'name' => 'Tamu',
            'slug' => 'guest',
            'is_active' => true,
        ]);

        $guestRole->permissions()->attach(Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ])->id);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Tamu')
            ->assertDontSee('Data Generus');
    }

    public function test_guest_sees_the_generus_resume_table_when_guest_role_grants_view_generus(): void
    {
        $guestRole = Role::create([
            'name' => 'Tamu',
            'slug' => 'guest',
            'is_active' => true,
        ]);

        $guestRole->permissions()->attach([
            Permission::create(['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'module' => 'dashboard', 'is_active' => true])->id,
            Permission::create(['name' => 'View Generus', 'slug' => 'view-generus', 'module' => 'generus', 'is_active' => true])->id,
        ]);

        $region = Region::create(['name' => 'Karawang Timur', 'code' => 'KT', 'is_active' => true]);
        $village = Village::create(['region_id' => $region->id, 'name' => 'Desa Satu', 'code' => 'D1', 'is_active' => true]);
        $group = Group::create(['village_id' => $village->id, 'name' => 'Kelompok A', 'code' => 'KA', 'is_active' => true]);
        $level = Level::create(['name' => 'PAUD', 'code' => 'PD', 'sort_order' => 1, 'is_active' => true]);
        $classGrade = ClassGrade::create(['level_id' => $level->id, 'name' => 'Pra PAUD', 'code' => '00', 'sort_order' => 1, 'is_active' => true]);

        $child = Generus::create(['registration_number' => 'GEN-001', 'full_name' => 'Generus Satu', 'gender' => 'laki-laki', 'status' => 'active', 'learning_class_id' => $classGrade->id]);
        GenerusAssignment::create(['generus_id' => $child->id, 'village_id' => $village->id, 'group_id' => $group->id, 'level_id' => $level->id, 'assigned_at' => now()->toDateString()]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Resume Jumlah Generus')
            ->assertSee('Desa Satu')
            ->assertViewHas('resumeGrandTotal', 1);
    }

    public function test_guest_is_redirected_to_login_when_accessing_pages_requiring_edit_permissions(): void
    {
        $guestRole = Role::create([
            'name' => 'Tamu',
            'slug' => 'guest',
            'is_active' => true,
        ]);

        $guestRole->permissions()->attach(Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ])->id);

        $this->get('/generus')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
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
            'login' => 'admin@ppg.test',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::create([
            'name' => 'Admin PPG',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'login' => 'admin@ppg.test',
                'password' => 'salah',
            ])->assertSessionHasErrors('login');
        }

        $this->post('/login', [
            'login' => 'admin@ppg.test',
            'password' => 'password123',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_dashboard_does_not_query_roles_per_sidebar_item(): void
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_active' => true,
        ]);

        $role->permissions()->attach(Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'view-dashboard',
            'module' => 'dashboard',
            'is_active' => true,
        ])->id);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id, 'global', null);

        $roleQueryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$roleQueryCount): void {
            if (str_contains($query->sql, 'user_roles')) {
                $roleQueryCount++;
            }
        });

        $this->actingAs(User::findOrFail($user->id))->get('/dashboard')->assertOk();

        // One query for the permission checks and one for the role name in the topbar.
        $this->assertSame(2, $roleQueryCount);
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
            'login' => 'guru@ppg.test',
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
            'login' => 'guru-inactive@ppg.test',
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

        $this->assertTrue($user->fresh()->hasPermission('view-dashboard-soft-delete'));

        $role->delete();
        $this->assertFalse($user->fresh()->hasPermission('view-dashboard-soft-delete'));

        $role->restore();
        $permission->delete();
        $this->assertFalse($user->fresh()->hasPermission('view-dashboard-soft-delete'));
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
            ->assertDontSee('Kategori Materi');
    }

    public function test_sidebar_shows_download_file_menu_for_a_role_that_only_has_that_permission(): void
    {
        $role = Role::create([
            'name' => 'Tamu',
            'slug' => 'guest',
            'is_active' => true,
        ]);

        $role->permissions()->attach([
            Permission::create(['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'module' => 'dashboard', 'is_active' => true])->id,
            // The download-files permissions are seeded by a migration (not a factory/seeder),
            // so they already exist in the test database by the time this runs.
            Permission::firstOrCreate(['slug' => 'view-download-files'], ['name' => 'View Download Files', 'module' => 'download-files', 'is_active' => true])->id,
        ]);

        $user = User::create([
            'name' => 'Tamu User',
            'email' => 'tamu-download@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $user->assignRole($role->id);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Download File')
            ->assertDontSee('Kategori Materi');

        $this->actingAs($user)
            ->get('/download-files')
            ->assertOk();
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
            ->get('/master-data/villages')
            ->assertOk()
            ->assertViewIs('master-data.villages');

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
            ->get('/material-categories')
            ->assertOk()
            ->assertViewIs('material-categories.index');

        $this->actingAs($user)
            ->get('/material-chapters')
            ->assertOk()
            ->assertViewIs('material-chapters.index');

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

    public function test_guest_with_view_generus_permission_can_read_but_not_write(): void
    {
        $guestRole = Role::create(['name' => 'Tamu', 'slug' => 'guest', 'is_active' => true]);

        $guestRole->permissions()->attach(Permission::create([
            'name' => 'View Generus',
            'slug' => 'view-generus',
            'module' => 'generus',
            'is_active' => true,
        ])->id);

        $generus = Generus::create([
            'registration_number' => 'G-001',
            'full_name' => 'Generus Publik',
            'status' => 'active',
        ]);

        $this->get('/generus')->assertOk()->assertSee('Generus Publik');
        $this->get("/generus/{$generus->id}")->assertOk()->assertSee('Generus Publik');

        $this->get('/generus/create')->assertRedirect('/login');
        $this->get("/generus/{$generus->id}/edit")->assertRedirect('/login');
        $this->post('/generus', ['full_name' => 'Hacked'])->assertRedirect('/login');
        $this->put("/generus/{$generus->id}", ['full_name' => 'Hacked'])->assertRedirect('/login');
        $this->delete("/generus/{$generus->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('generus', ['id' => $generus->id, 'full_name' => 'Generus Publik']);
    }

    public function test_guest_with_view_teachers_permission_can_read_but_not_write(): void
    {
        $guestRole = Role::create(['name' => 'Tamu', 'slug' => 'guest', 'is_active' => true]);

        $guestRole->permissions()->attach(Permission::create([
            'name' => 'View Teachers',
            'slug' => 'view-teachers',
            'module' => 'teachers',
            'is_active' => true,
        ])->id);

        $teacher = Teacher::create(['name' => 'Guru Publik', 'status' => 'active']);

        $this->get('/teachers')->assertOk()->assertSee('Guru Publik')->assertDontSee('Tambah Guru');

        $this->get("/teachers/{$teacher->id}/edit")->assertRedirect('/login');
        $this->post('/teachers', ['name' => 'Hacked', 'status' => 'active'])->assertRedirect('/login');
        $this->put("/teachers/{$teacher->id}", ['name' => 'Hacked', 'status' => 'active'])->assertRedirect('/login');
        $this->delete("/teachers/{$teacher->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'name' => 'Guru Publik']);
    }

    public function test_guest_with_view_guardians_permission_can_read_but_not_write(): void
    {
        $guestRole = Role::create(['name' => 'Tamu', 'slug' => 'guest', 'is_active' => true]);

        $guestRole->permissions()->attach(Permission::create([
            'name' => 'View Guardians',
            'slug' => 'view-guardians',
            'module' => 'guardians',
            'is_active' => true,
        ])->id);

        $guardian = Guardian::create([
            'full_name' => 'Wali Publik',
            'relationship' => 'Ayah',
            'status' => 'active',
        ]);

        $this->get('/guardians')->assertOk()->assertSee('Wali Publik')->assertDontSee('Tambah Wali');

        $this->get("/guardians/{$guardian->id}/edit")->assertRedirect('/login');
        $this->post('/guardians', ['full_name' => 'Hacked', 'relationship' => 'Ayah', 'status' => 'active'])->assertRedirect('/login');
        $this->put("/guardians/{$guardian->id}", ['full_name' => 'Hacked', 'relationship' => 'Ayah', 'status' => 'active'])->assertRedirect('/login');
        $this->delete("/guardians/{$guardian->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('guardians', ['id' => $guardian->id, 'full_name' => 'Wali Publik']);
    }

    public function test_guest_without_view_permission_is_redirected_to_login_for_generus_teachers_and_guardians(): void
    {
        Role::create(['name' => 'Tamu', 'slug' => 'guest', 'is_active' => true]);

        $this->get('/generus')->assertRedirect('/login');
        $this->get('/teachers')->assertRedirect('/login');
        $this->get('/guardians')->assertRedirect('/login');
    }
}
