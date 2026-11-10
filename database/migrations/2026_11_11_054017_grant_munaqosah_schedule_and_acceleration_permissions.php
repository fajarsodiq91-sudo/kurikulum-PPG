<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        ['name' => 'Manage Munaqosah Schedule', 'slug' => 'manage-munaqosah-schedule', 'module' => 'munaqosah'],
        ['name' => 'Propose Munaqosah Acceleration', 'slug' => 'propose-munaqosah-acceleration', 'module' => 'munaqosah'],
        ['name' => 'Approve Munaqosah Acceleration', 'slug' => 'approve-munaqosah-acceleration', 'module' => 'munaqosah'],
    ];

    /**
     * Adds the munaqosah-schedule/acceleration permissions (deploy only runs migrations, not
     * seeders) and grants them: manage-munaqosah-schedule + approve-munaqosah-acceleration to
     * PPG Daerah ("ppg"), approve-munaqosah-acceleration also to Perwakilan PPG Desa, and
     * propose-munaqosah-acceleration to Pelaksana PPG Kelompok. Super Admin gets all three.
     */
    public function up(): void
    {
        $permissionIds = [];

        foreach (self::PERMISSIONS as $permission) {
            $permissionIds[$permission['slug']] = DB::table('permissions')->where('slug', $permission['slug'])->value('id')
                ?? DB::table('permissions')->insertGetId([...$permission, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        $grants = [
            'ppg' => ['manage-munaqosah-schedule', 'approve-munaqosah-acceleration'],
            'perwakilan-ppg-desa' => ['approve-munaqosah-acceleration'],
            'pelaksana-ppg-kelompok' => ['propose-munaqosah-acceleration'],
        ];

        foreach ($grants as $roleSlug => $slugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');

            if ($roleId === null) {
                continue;
            }

            foreach ($slugs as $slug) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionIds[$slug]]);
            }
        }

        $superAdminId = DB::table('roles')->where('slug', 'super-admin')->value('id');

        if ($superAdminId !== null) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $superAdminId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', array_column(self::PERMISSIONS, 'slug'))->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
