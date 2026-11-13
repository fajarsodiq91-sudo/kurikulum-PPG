<?php

namespace App\Models;

use App\Support\StudentScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'village_id',
        'name',
        'code',
        'slug',
        'is_active',
        'description',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function defaultSchedules(): HasMany
    {
        return $this->hasMany(GroupDefaultSchedule::class);
    }

    /**
     * Limits to groups a scoped user may manage for the given permission: their own group,
     * or any group in their village when they hold a village-level scope. Global holders see
     * every group.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function manageableBy(Builder $query, ?User $user, string $permission): void
    {
        if ($user === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasGlobalAccess($permission)) {
            return;
        }

        $placementIds = $user->scopedPlacementIds($permission);

        $query->where(function (Builder $scoped) use ($placementIds): void {
            $scoped->whereRaw('1 = 0');

            if (isset($placementIds['group_id'])) {
                $scoped->orWhereIn('id', $placementIds['group_id']);
            }

            if (isset($placementIds['village_id'])) {
                $scoped->orWhereIn('village_id', $placementIds['village_id']);
            }
        });
    }

    /**
     * Active generus currently placed in this group, narrowed to the user's own students when
     * they are a teacher.
     *
     * @return Builder<Generus>
     */
    public function rosterFor(?User $user, string $permission): Builder
    {
        return self::rosterForGroups([(int) $this->id], $user, $permission);
    }

    /**
     * Same as rosterFor(), but spanning several groups at once — used when the caller (e.g. a
     * desa/daerah user) hasn't narrowed the page down to a single kelompok.
     *
     * @param  list<int>  $groupIds
     * @return Builder<Generus>
     */
    public static function rosterForGroups(array $groupIds, ?User $user, string $permission): Builder
    {
        return StudentScope::limit(
            Generus::where('status', 'active')->inPlacement(['group_id' => $groupIds])->orderBy('full_name'),
            StudentScope::manageIds($user, $permission),
            'id',
        );
    }
}
