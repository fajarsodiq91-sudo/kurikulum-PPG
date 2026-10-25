<?php

namespace App\Support;

use App\Models\Generus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Restricts generus-linked modules to the generus a user may work with: a logged-in teacher
 * only sees the students they ticked, and village/group roles only see generus placed in
 * their area. Users with global access (admins, PPG) are unrestricted.
 *
 * Two id sets are exposed because "can see" and "can write" differ for a kelompok-scoped
 * role: they view every generus in their desa (manage + view permission merged, and a
 * group-scoped view permission widens to its village - see User::scopedPlacementIdsAny())
 * but may only write against generus their manage permission narrowly covers.
 */
class StudentScope
{
    /**
     * @return list<int>|null Generus ids visible for listing/export, or null when unrestricted.
     */
    public static function viewIds(?User $user, string $managePermission): ?array
    {
        return self::resolve($user, [$managePermission, self::viewTwin($managePermission)]);
    }

    /**
     * @return list<int>|null Generus ids this user may create/update/delete against, or null when unrestricted.
     */
    public static function manageIds(?User $user, string $managePermission): ?array
    {
        return self::resolve($user, [$managePermission]);
    }

    /**
     * @param  list<string>  $permissions
     * @return list<int>|null
     */
    private static function resolve(?User $user, array $permissions): ?array
    {
        if ($user === null || $user->hasGlobalAccessAny($permissions)) {
            return null;
        }

        if ($user->teacher_id !== null) {
            return $user->teacher?->students()->pluck('generus.id')->map(fn (mixed $id): int => (int) $id)->all() ?? [];
        }

        $placementIds = $user->scopedPlacementIdsAny($permissions);

        return $placementIds === [] ? null : Generus::query()->inPlacement($placementIds)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    private static function viewTwin(string $managePermission): string
    {
        return str_replace('manage-', 'view-', $managePermission);
    }

    /**
     * @param  Builder<*>  $query
     * @param  list<int>|null  $ids
     * @return Builder<*>
     */
    public static function limit(Builder $query, ?array $ids, string $column = 'generus_id'): Builder
    {
        return $ids === null ? $query : $query->whereIn($column, $ids);
    }

    public static function existsRule(?User $user, string $managePermission): Exists
    {
        $rule = Rule::exists('generus', 'id')->whereNull('deleted_at');
        $ids = self::manageIds($user, $managePermission);

        return $ids === null ? $rule : $rule->whereIn('id', $ids);
    }

    /**
     * Existing records of generus outside the teacher's students respond as not found.
     */
    public static function authorize(?User $user, string $managePermission, int $generusId): void
    {
        $ids = self::manageIds($user, $managePermission);

        abort_unless($ids === null || in_array($generusId, $ids, true), 404);
    }
}
