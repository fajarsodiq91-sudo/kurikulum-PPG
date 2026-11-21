<?php

namespace App\Support;

use App\Models\Group;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Every team (a jabatan/Dapukan within a specific organisational node, e.g. "Bidang
     * Kurikulum" at the Daerah level, or at a particular Desa) this user may assign a task
     * to - their own node and everything beneath it, mirroring {@see assignableRecipients()}.
     * Each entry's "key" is what a submitted form identifies the team by.
     *
     * @return Collection<int, array{key: string, label: string, dapukan_id: int, scope_type: string, scope_id: ?int}>
     */
    public static function assignableTeams(User $user): Collection
    {
        $nodes = self::reachableScopeNodes($user);

        if ($nodes === []) {
            return collect();
        }

        $query = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('dapukans', 'dapukans.id', '=', 'user_roles.dapukan_id')
            ->where('users.status', 'active')
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->where('dapukans.is_active', true);

        $query->where(function (\Illuminate\Database\Query\Builder $outer) use ($nodes): void {
            foreach ($nodes as $node) {
                $outer->orWhere(function (\Illuminate\Database\Query\Builder $inner) use ($node): void {
                    $inner->where('roles.slug', $node['role_slug'])
                        ->where('user_roles.scope_type', $node['scope_type']);

                    if ($node['scope_id'] === null) {
                        $inner->whereNull('user_roles.scope_id');
                    } else {
                        $inner->where('user_roles.scope_id', $node['scope_id']);
                    }
                });
            }
        });

        return $query
            ->distinct()
            ->orderBy('dapukans.sort_order')
            ->get([
                'dapukans.id as dapukan_id',
                'dapukans.name as dapukan_name',
                'user_roles.scope_type',
                'user_roles.scope_id',
            ])
            ->map(fn (object $row): array => [
                'key' => $row->dapukan_id.'|'.$row->scope_type.'|'.($row->scope_id ?? ''),
                'label' => $row->dapukan_name.' — '.self::nodeLabel($row->scope_type, $row->scope_id),
                'dapukan_id' => (int) $row->dapukan_id,
                'scope_type' => $row->scope_type,
                'scope_id' => $row->scope_id !== null ? (int) $row->scope_id : null,
            ])
            ->unique('key')
            ->sortBy('label')
            ->values();
    }

    /**
     * Every active user id who currently holds the given team (dapukan within a specific
     * scope). Used to expand a task's chosen team(s) into concrete assignees.
     *
     * @param  list<string>  $teamKeys
     * @return list<int>
     */
    public static function teamMemberIds(array $teamKeys): array
    {
        if ($teamKeys === []) {
            return [];
        }

        $ids = collect();

        foreach ($teamKeys as $teamKey) {
            [$dapukanId, $scopeType, $scopeId] = array_pad(explode('|', $teamKey, 3), 3, null);

            $query = DB::table('user_roles')
                ->join('users', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('users.status', 'active')
                ->where('roles.is_active', true)
                ->where('user_roles.is_active', true)
                ->where('user_roles.dapukan_id', (int) $dapukanId)
                ->where('user_roles.scope_type', $scopeType);

            if ($scopeId === null || $scopeId === '') {
                $query->whereNull('user_roles.scope_id');
            } else {
                $query->where('user_roles.scope_id', (int) $scopeId);
            }

            $ids = $ids->merge($query->pluck('users.id'));
        }

        return self::normalizeIds($ids);
    }

    /**
     * The organisational node (Daerah, a given Desa, or a given Kelompok) a team's
     * scope_type/scope_id identifies, as a human label.
     */
    private static function nodeLabel(string $scopeType, ?int $scopeId): string
    {
        return match ($scopeType) {
            'global', 'region' => 'Daerah',
            'village' => 'Desa '.(Village::find($scopeId)?->name ?? '?'),
            'group' => 'Kelompok '.(Group::find($scopeId)?->name ?? '?'),
            default => '?',
        };
    }

    /**
     * Organisational nodes (role_slug + scope) reachable from this user: their own node and
     * everything beneath it. Mirrors the traversal {@see downwardReachIds()} and
     * {@see MusyawarohHierarchy::treeFor()} perform, but flattened into a list
     * of node specs instead of a nested tree or a flat user-id list.
     *
     * @return list<array{role_slug: string, scope_type: string, scope_id: ?int}>
     */
    private static function reachableScopeNodes(User $user): array
    {
        $nodes = [];

        foreach ($user->permissionScopes(self::PERMISSION) as $scope) {
            if (in_array($scope['type'], ['global', 'region'], true)) {
                $nodes[] = ['role_slug' => 'ppg', 'scope_type' => 'global', 'scope_id' => null];

                foreach (Village::where('is_active', true)->get() as $village) {
                    $nodes[] = ['role_slug' => 'perwakilan-ppg-desa', 'scope_type' => 'village', 'scope_id' => $village->id];

                    foreach (Group::where('village_id', $village->id)->where('is_active', true)->get() as $group) {
                        $nodes[] = ['role_slug' => 'pelaksana-ppg-kelompok', 'scope_type' => 'group', 'scope_id' => $group->id];
                    }
                }
            }

            if ($scope['type'] === 'village' && $scope['id'] !== null) {
                $nodes[] = ['role_slug' => 'perwakilan-ppg-desa', 'scope_type' => 'village', 'scope_id' => (int) $scope['id']];

                foreach (Group::where('village_id', $scope['id'])->where('is_active', true)->get() as $group) {
                    $nodes[] = ['role_slug' => 'pelaksana-ppg-kelompok', 'scope_type' => 'group', 'scope_id' => $group->id];
                }
            }

            if ($scope['type'] === 'group' && $scope['id'] !== null) {
                $nodes[] = ['role_slug' => 'pelaksana-ppg-kelompok', 'scope_type' => 'group', 'scope_id' => (int) $scope['id']];
            }
        }

        return $nodes;
    }

    /**
     * Bucket (nama musyawaroh) values this user's management-role peers have typed before -
     * shared only within the same role (e.g. every Perwakilan PPG Desa), never across levels,
     * so a kelompok or daerah holder never sees a desa's suggestions and vice versa.
     *
     * @return list<string>
     */
    public static function bucketSuggestionsFor(User $user): array
    {
        $roleIds = $user->roles()
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return [];
        }

        return Task::query()
            ->whereNotNull('bucket')
            ->where('bucket', '!=', '')
            ->whereHas('assignedBy.roles', fn (Builder $query) => $query
                ->where('roles.is_active', true)
                ->where('user_roles.is_active', true)
                ->whereIn('roles.id', $roleIds))
            ->distinct()
            ->orderBy('bucket')
            ->pluck('bucket')
            ->all();
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
