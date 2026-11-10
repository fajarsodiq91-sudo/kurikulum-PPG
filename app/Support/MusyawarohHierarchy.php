<?php

namespace App\Support;

use App\Models\Group;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The attendance tree a musyawaroh's organiser may tick names from: their own node and
 * everything beneath it in the daerah > desa > kelompok hierarchy - never above it and never
 * sideways to a sibling branch. A daerah holder sees daerah down to every kelompok; a desa
 * holder sees their own desa down to its kelompok; a kelompok holder sees only their own
 * kelompok. Mirrors TaskHierarchy's downward reach, but keeps each node's pengurus grouped
 * by jabatan (Dapukan::group_label) instead of flattening everyone into one list.
 */
class MusyawarohHierarchy
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function treeFor(User $user): array
    {
        $scopes = $user->permissionScopes(TaskHierarchy::PERMISSION);
        $nodes = collect();

        foreach ($scopes as $scope) {
            if (in_array($scope['type'], ['global', 'region'], true)) {
                $nodes->push(self::daerahNode());
            }

            if ($scope['type'] === 'village' && $scope['id'] !== null) {
                $village = Village::find($scope['id']);

                if ($village !== null) {
                    $nodes->push(self::villageNode($village));
                }
            }

            if ($scope['type'] === 'group' && $scope['id'] !== null) {
                $group = Group::find($scope['id']);

                if ($group !== null) {
                    $nodes->push(self::groupNode($group));
                }
            }
        }

        return $nodes->unique('key')->values()->all();
    }

    /**
     * Every user id selectable somewhere in $user's tree - used to validate that submitted
     * attendee ids actually come from within their reach.
     *
     * @return list<int>
     */
    public static function allowedUserIds(User $user): array
    {
        return self::flatMembersFor($user)->pluck('user_id')->all();
    }

    /**
     * Every pengurus selectable somewhere in $user's tree, flattened and de-duplicated by
     * user id - used to offer "add someone who wasn't invited but showed up anyway" pickers.
     *
     * @return Collection<int, object>
     */
    public static function flatMembersFor(User $user): Collection
    {
        return self::flatten(self::treeFor($user))
            ->unique('user_id')
            ->map(function (object $row): object {
                $row->user_id = (int) $row->user_id;

                return $row;
            })
            ->sortBy('user_name')
            ->values();
    }

    /**
     * Every active pengurus in the system (daerah, desa, and kelompok alike), regardless of
     * branch. Attendance isn't limited to the organiser's own vertical reach the way inviting
     * and task-assigning are - a musyawaroh can be, and often is, attended by pengurus from
     * other branches who simply weren't on the invite list.
     *
     * @return Collection<int, object>
     */
    public static function allActiveMembers(): Collection
    {
        return DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('dapukans', 'dapukans.id', '=', 'user_roles.dapukan_id')
            ->where('users.status', 'active')
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->whereIn('roles.slug', ['ppg', 'perwakilan-ppg-desa', 'pelaksana-ppg-kelompok'])
            ->orderBy('users.name')
            ->get([
                'users.id as user_id',
                'users.name as user_name',
                'dapukans.name as jabatan',
            ])
            ->unique('user_id')
            ->map(function (object $row): object {
                $row->user_id = (int) $row->user_id;

                return $row;
            })
            ->values();
    }

    /**
     * @return list<int>
     */
    public static function allActiveUserIds(): array
    {
        return self::allActiveMembers()->pluck('user_id')->all();
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return Collection<int, object>
     */
    private static function flatten(array $nodes): Collection
    {
        $rows = collect();

        foreach ($nodes as $node) {
            foreach ($node['groups'] as $group) {
                $rows = $rows->merge($group);
            }

            if ($node['children'] !== []) {
                $rows = $rows->merge(self::flatten($node['children']));
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private static function daerahNode(): array
    {
        return [
            'key' => 'daerah',
            'label' => 'Daerah',
            'groups' => self::dapukanGroups(fn ($query) => $query->where('user_roles.scope_type', 'global')->where('roles.slug', 'ppg')),
            'children' => Village::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Village $village): array => self::villageNode($village))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function villageNode(Village $village): array
    {
        return [
            'key' => 'village-'.$village->id,
            'label' => 'Desa '.$village->name,
            'groups' => self::dapukanGroups(fn ($query) => $query
                ->where('user_roles.scope_type', 'village')
                ->where('user_roles.scope_id', $village->id)
                ->where('roles.slug', 'perwakilan-ppg-desa')),
            'children' => Group::where('village_id', $village->id)->where('is_active', true)->orderBy('name')->get()
                ->map(fn (Group $group): array => self::groupNode($group))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function groupNode(Group $group): array
    {
        return [
            'key' => 'group-'.$group->id,
            'label' => 'Kelompok '.$group->name,
            'groups' => self::dapukanGroups(fn ($query) => $query
                ->where('user_roles.scope_type', 'group')
                ->where('user_roles.scope_id', $group->id)
                ->where('roles.slug', 'pelaksana-ppg-kelompok')),
            'children' => [],
        ];
    }

    /**
     * Active role holders matching the given constraint, grouped by their jabatan's
     * group_label (an unassigned jabatan falls under "Lainnya").
     *
     * @param  callable(Builder): Builder  $constrain
     * @return Collection<string, Collection<int, object>>
     */
    private static function dapukanGroups(callable $constrain): Collection
    {
        $query = DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('dapukans', 'dapukans.id', '=', 'user_roles.dapukan_id')
            ->where('users.status', 'active')
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true);

        $query = $constrain($query);

        return $query
            ->orderBy('dapukans.sort_order')
            ->orderBy('users.name')
            ->get([
                'users.id as user_id',
                'users.name as user_name',
                'dapukans.name as jabatan',
                'dapukans.group_label as group_label',
            ])
            ->groupBy(fn (object $row): string => $row->group_label ?? 'Lainnya');
    }
}
