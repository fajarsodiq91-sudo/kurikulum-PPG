<?php

namespace App\Support;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Who a pengurus may assign a task to, and whose tasks they may oversee, following the
 * organisation hierarchy (daerah > desa > kelompok): strictly vertical - their own node,
 * everything beneath it (downward), and everything above it (upward). Never sideways to a
 * sibling desa or kelompok outside their own branch.
 */
class TaskHierarchy
{
    public const PERMISSION = 'manage-tasks';

    /**
     * Ids of users within this user's own node or beneath it. Null means unrestricted: a
     * daerah (global/region scoped) holder's downward reach is the whole organisation.
     *
     * @return list<int>|null
     */
    public static function downwardReachIds(User $user): ?array
    {
        $scopes = $user->permissionScopes(self::PERMISSION);

        if ($scopes->contains(fn (array $scope): bool => in_array($scope['type'], ['global', 'region'], true))) {
            return null;
        }

        $ids = collect();

        foreach ($scopes as $scope) {
            if ($scope['type'] === 'village' && $scope['id'] !== null) {
                $ids = $ids->merge(self::usersWithScope('village', (int) $scope['id'])->pluck('id'));

                $groupIds = Group::where('village_id', $scope['id'])->pluck('id')->all();
                $ids = $ids->merge(self::usersWithScopeIn('group', $groupIds)->pluck('id'));
            }

            if ($scope['type'] === 'group' && $scope['id'] !== null) {
                $ids = $ids->merge(self::usersWithScope('group', (int) $scope['id'])->pluck('id'));
            }
        }

        return self::normalizeIds($ids);
    }

    /**
     * Ids of users above this user's own node (their desa and/or the daerah).
     *
     * @return list<int>
     */
    public static function upwardReachIds(User $user): array
    {
        $ids = collect();

        foreach ($user->permissionScopes(self::PERMISSION) as $scope) {
            if ($scope['type'] === 'village') {
                $ids = $ids->merge(self::topLevelUsers()->pluck('id'));
            }

            if ($scope['type'] === 'group' && $scope['id'] !== null) {
                $villageId = Group::find($scope['id'])?->village_id;

                if ($villageId !== null) {
                    $ids = $ids->merge(self::usersWithScope('village', $villageId)->pluck('id'));
                }

                $ids = $ids->merge(self::topLevelUsers()->pluck('id'));
            }
        }

        return self::normalizeIds($ids);
    }

    /**
     * Everyone this user may give a task to: their own branch, their ancestors, and
     * themselves - never a sibling desa/kelompok outside their own branch.
     *
     * @return Collection<int, User>
     */
    public static function assignableRecipients(User $user): Collection
    {
        $downward = self::downwardReachIds($user);

        if ($downward === null) {
            return self::holdersQuery()->with(['roles', 'teacher'])->orderBy('name')->get();
        }

        $ids = collect([...$downward, ...self::upwardReachIds($user), $user->id])->unique()->values()->all();

        return User::query()->whereIn('id', $ids)->where('status', 'active')->with(['roles', 'teacher'])->orderBy('name')->get();
    }

    private static function usersWithScope(string $scopeType, int $scopeId): Builder
    {
        return self::activeUsersQuery()->whereHas(
            'roles',
            fn (Builder $query) => self::roleGrantingPermission($query)->where('user_roles.scope_type', $scopeType)->where('user_roles.scope_id', $scopeId),
        );
    }

    /**
     * @param  list<int>  $scopeIds
     */
    private static function usersWithScopeIn(string $scopeType, array $scopeIds): Builder
    {
        if ($scopeIds === []) {
            return User::query()->whereRaw('1 = 0');
        }

        return self::activeUsersQuery()->whereHas(
            'roles',
            fn (Builder $query) => self::roleGrantingPermission($query)->where('user_roles.scope_type', $scopeType)->whereIn('user_roles.scope_id', $scopeIds),
        );
    }

    private static function topLevelUsers(): Builder
    {
        return self::activeUsersQuery()->whereHas(
            'roles',
            fn (Builder $query) => self::roleGrantingPermission($query)->whereIn('user_roles.scope_type', ['global', 'region']),
        );
    }

    private static function holdersQuery(): Builder
    {
        return self::activeUsersQuery()->whereHas('roles', fn (Builder $query) => self::roleGrantingPermission($query));
    }

    private static function activeUsersQuery(): Builder
    {
        return User::query()->where('status', 'active');
    }

    /**
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    private static function roleGrantingPermission(Builder $query): Builder
    {
        return $query
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->whereHas('permissions', fn (Builder $permissions) => $permissions->where('permissions.slug', self::PERMISSION)->where('permissions.is_active', true));
    }

    /**
     * @return list<int>
     */
    private static function normalizeIds(Collection $ids): array
    {
        return $ids->unique()->map(fn (mixed $id): int => (int) $id)->values()->all();
    }
}
