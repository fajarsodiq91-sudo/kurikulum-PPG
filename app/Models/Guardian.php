<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = [
        'full_name',
        'relationship',
        'phone',
        'address',
        'status',
        'notes',
    ];

    /**
     * Limit to guardians of generus the user may see; guests and global roles see all.
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        $permissions = ['view-guardians', 'manage-guardians'];

        if ($user === null || $user->hasGlobalAccessAny($permissions)) {
            return;
        }

        $placementIds = $user->scopedPlacementIdsAny($permissions);

        $query->whereHas('generus', fn (Builder $generus) => $placementIds === []
            ? $generus->whereRaw('1 = 0')
            : $generus->inPlacement($placementIds));
    }

    public function generus(): BelongsToMany
    {
        return $this->belongsToMany(Generus::class, 'generus_guardian')->withTimestamps();
    }
}
