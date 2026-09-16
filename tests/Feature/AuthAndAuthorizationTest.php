<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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
}
