<?php

namespace App\Models;

use App\Support\StudentScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningSession extends Model
{
    public const LEVEL_VILLAGE = 'village';

    public const LEVEL_GROUP = 'group';

    public const ATTENDANCE_PERMISSION = 'manage-learning-attendance';

    public const MANAGE_PERMISSION = 'manage-learning-sessions';

    protected $table = 'learning_sessions';

    protected $fillable = [
        'teacher_id',
        'material_id',
        'level',
        'village_id',
        'group_id',
        'session_date',
        'start_time',
        'end_time',
        'location',
        'status',
        'notes',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'material_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            self::LEVEL_VILLAGE => 'Desa'.($this->village ? ': '.$this->village->name : ''),
            self::LEVEL_GROUP => 'Kelompok'.($this->group ? ': '.$this->group->name : ''),
            default => '-',
        };
    }

    /**
     * Sessions whose attendance list the user may fill: a village-scoped role only fills
     * village-level sessions of its village, a group-scoped role only group-level sessions
     * of its group, and global roles fill any session.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function attendableBy(Builder $query, ?User $user): void
    {
        if ($user === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasGlobalAccess(self::ATTENDANCE_PERMISSION)) {
            return;
        }

        $placementIds = $user->scopedPlacementIds(self::ATTENDANCE_PERMISSION);

        $query->where(function (Builder $scoped) use ($placementIds): void {
            $scoped->whereRaw('1 = 0');

            foreach ([self::LEVEL_VILLAGE => 'village_id', self::LEVEL_GROUP => 'group_id'] as $level => $column) {
                if (isset($placementIds[$column])) {
                    $scoped->orWhere(fn (Builder $match) => $match->where('level', $level)->whereIn($column, $placementIds[$column]));
                }
            }
        });
    }

    public function isAttendableBy(?User $user): bool
    {
        return static::query()->attendableBy($user)->whereKey($this->getKey())->exists();
    }

    /**
     * Limits to sessions a scoped user may see: their own village/group placement, or every
     * session for a global holder of the view/manage permission.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        $permissions = ['view-learning-sessions', self::MANAGE_PERMISSION];

        if ($user === null || $user->hasGlobalAccessAny($permissions)) {
            return;
        }

        $placementIds = $user->scopedPlacementIdsAny($permissions);

        $query->where(function (Builder $scoped) use ($placementIds): void {
            $scoped->whereRaw('1 = 0');

            foreach ([self::LEVEL_VILLAGE => 'village_id', self::LEVEL_GROUP => 'group_id'] as $level => $column) {
                if (isset($placementIds[$column])) {
                    $scoped->orWhere(fn (Builder $match) => $match->where('level', $level)->whereIn($column, $placementIds[$column]));
                }
            }
        });
    }

    /**
     * Whether the user may complete this session's teacher/time (the national schedule's
     * placement, date, and material stay fixed): a global schedule manager, or whoever can
     * take attendance for it.
     */
    public function isCompletableBy(?User $user): bool
    {
        return $user !== null && ($user->hasGlobalAccess(self::MANAGE_PERMISSION) || $this->isAttendableBy($user));
    }

    /**
     * Active generus expected at this session: those placed in its village or group. Legacy
     * sessions without a level have no fixed audience, so every active generus is listed.
     *
     * @return Builder<Generus>
     */
    public function expectedGenerus(): Builder
    {
        $placement = match ($this->level) {
            self::LEVEL_VILLAGE => ['village_id' => [(int) $this->village_id]],
            self::LEVEL_GROUP => ['group_id' => [(int) $this->group_id]],
            default => null,
        };

        return Generus::query()
            ->where('status', 'active')
            ->when($placement, fn (Builder $query) => $query->inPlacement($placement))
            ->orderBy('full_name');
    }

    /**
     * The generus the user may take attendance for: the expected audience, narrowed to the
     * user's own students when they are a teacher.
     *
     * @return Builder<Generus>
     */
    public function rosterFor(?User $user): Builder
    {
        return StudentScope::limit($this->expectedGenerus(), StudentScope::ids($user, self::ATTENDANCE_PERMISSION), 'id');
    }
}
