<?php

namespace App\Models;

use App\Support\TaskHierarchy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Musyawaroh extends Model
{
    protected $fillable = [
        'title',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MusyawarohAttendee::class);
    }

    public function notulens(): HasMany
    {
        return $this->hasMany(Notulen::class);
    }

    /**
     * Visible to whoever could have attended it: the author's own vertical reach (their
     * branch and their ancestors), mirroring the hierarchy tasks use. Super Admin sees
     * everything.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $downward = TaskHierarchy::downwardReachIds($user);

        if ($downward === null) {
            return;
        }

        $ids = collect([...$downward, ...TaskHierarchy::upwardReachIds($user), $user->id])->unique()->values()->all();

        $query->whereIn('created_by_id', $ids);
    }

    public function isVisibleTo(User $user): bool
    {
        return static::query()->visibleTo($user)->whereKey($this->id)->exists();
    }
}
