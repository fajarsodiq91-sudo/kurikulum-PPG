<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        ['name' => 'Publish Report Cards', 'slug' => 'manage-report-card-publishing', 'module' => 'report-cards'],
        ['name' => 'Confirm Report Card Publishing', 'slug' => 'confirm-report-card-publishing', 'module' => 'report-cards'],
    ];

    /**
     * Adds the rapor-publishing permissions (deploy only runs migrations, not seeders) and
     * grants manage-report-card-publishing to PPG Daerah ("ppg") and
     * confirm-report-card-publishing to Pelaksana PPG Kelompok. Super Admin gets both.
     */
    public function up(): void
    {
        $permissionIds = [];

        foreach (self::PERMISSIONS as $permission) {
            $permissionIds[$permission['slug']] = DB::table('permissions')->where('slug', $permission['slug'])->value('id')
                ?? DB::table('permissions')->insertGetId([...$permission, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        $grants = [
            'ppg' => ['manage-report-card-publishing'],
            'pelaksana-ppg-kelompok' => ['confirm-report-card-publishing'],
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
