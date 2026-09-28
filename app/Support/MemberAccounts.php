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
    private const PERMISSIONS = ['view-dashboard', 'view-generus', 'view-teachers', 'view-guardians'];

    public static function forGenerus(Generus $generus): void
    {
        self::ensure($generus->registration_number, $generus->full_name, 'generus_id', $generus->id, 'generus', 'Generus', 'active');
    }

    public static function forTeacher(Teacher $teacher): void
    {
        self::ensure($teacher->registration_number, $teacher->name, 'teacher_id', $teacher->id, 'guru', 'Guru', $teacher->status === 'active' ? 'active' : 'inactive');
    }

    public static function setStatus(string $linkColumn, int $id, string $status): void
    {
        User::where($linkColumn, $id)->update(['status' => $status]);
    }

    public static function rename(string $linkColumn, int $id, string $name): void
    {
        User::where($linkColumn, $id)->update(['name' => $name]);
    }

    private static function ensure(?string $number, string $name, string $linkColumn, int $id, string $roleSlug, string $roleName, string $status): void
    {
        if (blank($number) || User::where('username', $number)->exists()) {
            return;
        }

        $user = User::create([
            'name' => $name,
            'username' => $number,
            'password' => Hash::make($number),
            'status' => $status,
        ]);
        $user->forceFill([$linkColumn => $id])->save();

        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => $roleName, 'is_active' => true]);
        $role->permissions()->syncWithoutDetaching(Permission::whereIn('slug', self::PERMISSIONS)->pluck('id'));
        $user->assignRole($role->id);
    }
}
