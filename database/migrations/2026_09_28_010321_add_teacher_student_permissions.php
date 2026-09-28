<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        ['name' => 'Manage My Students', 'slug' => 'manage-my-students', 'module' => 'teachers'],
        ['name' => 'Manage Parent Communications', 'slug' => 'manage-parent-communications', 'module' => 'parent-communications'],
    ];

    /**
     * Adds the new permissions and gives them to Super Admin when that role already exists.
     */
    public function up(): void
    {
        $superAdminId = DB::table('roles')->where('slug', 'super-admin')->value('id');

        foreach (self::PERMISSIONS as $permission) {
            $id = DB::table('permissions')->where('slug', $permission['slug'])->value('id')
                ?? DB::table('permissions')->insertGetId([...$permission, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

            if ($superAdminId !== null) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $superAdminId, 'permission_id' => $id]);
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
