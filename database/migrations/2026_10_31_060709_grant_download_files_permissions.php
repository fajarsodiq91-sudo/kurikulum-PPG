<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        ['name' => 'Manage Download Files', 'slug' => 'manage-download-files', 'module' => 'download-files'],
        ['name' => 'View Download Files', 'slug' => 'view-download-files', 'module' => 'download-files'],
    ];

    /**
     * Adds the download-files permissions (deploy only runs migrations, not seeders) and
     * gives manage+view to Super Admin and view-only to Tamu (guest) when those roles exist.
     */
    public function up(): void
    {
        $permissionIds = [];

        foreach (self::PERMISSIONS as $permission) {
            $permissionIds[$permission['slug']] = DB::table('permissions')->where('slug', $permission['slug'])->value('id')
                ?? DB::table('permissions')->insertGetId([...$permission, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        $superAdminId = DB::table('roles')->where('slug', 'super-admin')->value('id');

        if ($superAdminId !== null) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $superAdminId, 'permission_id' => $permissionId]);
            }
        }

        $guestId = DB::table('roles')->where('slug', 'guest')->value('id');

        if ($guestId !== null) {
            DB::table('role_permissions')->insertOrIgnore(['role_id' => $guestId, 'permission_id' => $permissionIds['view-download-files']]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', array_column(self::PERMISSIONS, 'slug'))->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
