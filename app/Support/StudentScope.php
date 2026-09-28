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
 */
class StudentScope
{
    /**
     * @return list<int>|null Allowed generus ids, or null when the user is not restricted.
     */
    public static function ids(?User $user, string $permission): ?array
    {
        $permissions = [$permission, str_replace('manage-', 'view-', $permission)];

        if ($user === null || $user->hasGlobalAccessAny($permissions)) {
            return null;
        }

        if ($user->teacher_id !== null) {
            return $user->teacher?->students()->pluck('generus.id')->map(fn (mixed $id): int => (int) $id)->all() ?? [];
        }

        $placementIds = $user->scopedPlacementIdsAny($permissions);

        return $placementIds === [] ? null : Generus::query()->inPlacement($placementIds)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
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

    public static function existsRule(?User $user, string $permission): Exists
    {
        $rule = Rule::exists('generus', 'id')->whereNull('deleted_at');
        $ids = self::ids($user, $permission);

        return $ids === null ? $rule : $rule->whereIn('id', $ids);
    }

    /**
     * Existing records of generus outside the teacher's students respond as not found.
     */
    public static function authorize(?User $user, string $permission, int $generusId): void
    {
        $ids = self::ids($user, $permission);

        abort_unless($ids === null || in_array($generusId, $ids, true), 404);
    }
}
