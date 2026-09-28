<?php

namespace App\Support;

use App\Models\Generus;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Login accounts for registered generus and teachers: the registration number is the
 * username and also the initial password, which the member can change afterwards.
 */
class MemberAccounts
{
    private const BASE_PERMISSIONS = ['view-dashboard', 'view-generus', 'view-teachers', 'view-guardians'];

    /**
     * Teachers also work with their own students, so these are limited to those students
     * by StudentScope rather than granted for everyone.
     */
    private const TEACHER_PERMISSIONS = [
        'manage-my-students',
        'manage-learning-attendance',
        'manage-evaluations',
        'manage-follow-ups',
        'manage-progress-tracking',
        'manage-report-cards',
        'manage-milestones',
        'manage-munaqosah',
        'manage-parent-communications',
    ];

    public static function forGenerus(Generus $generus): void
    {
        self::ensure($generus->registration_number, $generus->full_name, 'generus_id', $generus->id, 'generus', 'Generus', 'active', self::BASE_PERMISSIONS);
    }

    public static function forTeacher(Teacher $teacher): void
    {
        self::ensure($teacher->registration_number, $teacher->name, 'teacher_id', $teacher->id, 'guru', 'Guru', $teacher->status === 'active' ? 'active' : 'inactive', [...self::BASE_PERMISSIONS, ...self::TEACHER_PERMISSIONS], 'teacher');
    }

    public static function setStatus(string $linkColumn, int $id, string $status): void
    {
        User::where($linkColumn, $id)->update(['status' => $status]);
    }

    public static function rename(string $linkColumn, int $id, string $name): void
    {
        User::where($linkColumn, $id)->update(['name' => $name]);
    }

    /**
     * Creates the account when missing and always (re)applies the role, so accounts made
     * before a permission existed pick it up the next time they are synced.
     *
     * @param  list<string>  $permissionSlugs
     */
    private static function ensure(?string $number, string $name, string $linkColumn, int $id, string $roleSlug, string $roleName, string $status, array $permissionSlugs, string $scopeType = 'global'): void
    {
        if (blank($number)) {
            return;
        }

        $user = User::where('username', $number)->first();

        if ($user === null) {
            $user = User::create([
                'name' => $name,
                'username' => $number,
                'password' => Hash::make($number),
                'status' => $status,
            ]);
            $user->forceFill([$linkColumn => $id])->save();
        }

        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => $roleName, 'is_active' => true]);
        $role->permissions()->syncWithoutDetaching(
            collect($permissionSlugs)
                ->map(fn (string $slug): int => Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'module' => $slug, 'is_active' => true])->id)
                ->all(),
        );
        $user->assignRole($role->id, $scopeType, $scopeType === 'global' ? null : $id);
    }
}
