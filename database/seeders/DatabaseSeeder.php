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
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $permissions = collect([
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'module' => 'dashboard'],
            ['name' => 'View Reports', 'slug' => 'view-reports', 'module' => 'reports'],
        ])->map(fn (array $permission) => Permission::firstOrCreate(
            ['slug' => $permission['slug']],
            [...$permission, 'is_active' => true],
        ));

        $role = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_active' => true],
        );

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
