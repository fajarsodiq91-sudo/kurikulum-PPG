<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Read-only "view-*" twins of the module permissions, and the three organisation roles
 * built on them. Safe to run repeatedly.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Modules that gain a view-* permission next to their existing manage-* one.
     *
     * @var list<string>
     */
    private const MODULES = [
        'curriculum', 'learning-materials', 'learning-sessions', 'learning-attendance', 'evaluations',
        'training', 'communication', 'munaqosah', 'report-cards', 'follow-ups', 'progress-tracking',
        'milestones', 'annual-audit', 'organization-units', 'assignments', 'activity-schedules',
        'activity-executions',
    ];

    /**
     * Manage permissions the group executor (Pelaksana PPG Kelompok) holds within their group.
     *
     * @var list<string>
     */
    private const GROUP_MANAGE = [
        'manage-generus', 'manage-teachers', 'manage-guardians', 'manage-learning-attendance',
        'manage-evaluations', 'manage-follow-ups', 'manage-progress-tracking', 'manage-report-cards',
        'manage-milestones', 'manage-munaqosah', 'manage-parent-communications',
    ];

    public function run(): void
    {
        $this->ensurePermission('manage-master-data', 'Manage Master Data', 'master-data');
        $this->ensurePermission('manage-parent-communications', 'Manage Parent Communications', 'parent-communications');
        $this->ensurePermission('view-parent-communications', 'View Parent Communications', 'parent-communications');

        foreach (self::MODULES as $module) {
            $this->ensurePermission("view-{$module}", 'View '.str($module)->replace('-', ' ')->title(), $module);
        }

        $this->preserveMasterDataWriteAccess();

        $viewPermissions = Permission::where('slug', 'like', 'view-%')->pluck('slug');
        $forVillage = $viewPermissions->reject(fn (string $slug): bool => $slug === 'view-master-data')->values();

        $this->syncRole('ppg', 'PPG', 'Melihat semua data (baca-saja)', $viewPermissions->all());
        $this->syncRole('perwakilan-ppg-desa', 'Perwakilan PPG Desa', 'Melihat data di desanya (baca-saja)', $forVillage->all());
        $this->syncRole('pelaksana-ppg-kelompok', 'Pelaksana PPG Kelompok', 'Mengelola seluruh data di tingkat kelompok', [...$forVillage->all(), ...self::GROUP_MANAGE]);

        Role::where('slug', 'super-admin')->first()?->permissions()->syncWithoutDetaching(Permission::pluck('id'));
    }

    private function ensurePermission(string $slug, string $name, string $module): void
    {
        Permission::firstOrCreate(['slug' => $slug], ['name' => $name, 'module' => $module, 'is_active' => true]);
    }

    /**
     * view-master-data used to allow writes too; holders keep that through manage-master-data.
     */
    private function preserveMasterDataWriteAccess(): void
    {
        $viewId = Permission::where('slug', 'view-master-data')->value('id');
        $manageId = Permission::where('slug', 'manage-master-data')->value('id');

        if ($viewId === null) {
            return;
        }

        Role::whereHas('permissions', fn ($query) => $query->whereKey($viewId))
            ->whereNotIn('slug', ['ppg', 'perwakilan-ppg-desa', 'pelaksana-ppg-kelompok'])
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$manageId]));
    }

    /**
     * @param  list<string>  $permissionSlugs
     */
    private function syncRole(string $slug, string $name, string $description, array $permissionSlugs): void
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description, 'is_active' => true]);
        $role->permissions()->syncWithoutDetaching(Permission::whereIn('slug', $permissionSlugs)->pluck('id'));
    }
}
