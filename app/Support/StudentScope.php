<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * A logged-in teacher who lacks global access to a module only works with the generus they
 * ticked as their students. Everyone else (admins, non-teacher roles) is unrestricted.
 */
class StudentScope
{
    /**
     * @return list<int>|null Allowed generus ids, or null when the user is not restricted.
     */
    public static function ids(?User $user, string $permission): ?array
    {
        if ($user === null || $user->teacher_id === null || $user->hasGlobalAccess($permission)) {
            return null;
        }

        return $user->teacher?->students()->pluck('generus.id')->map(fn (mixed $id): int => (int) $id)->all() ?? [];
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
