<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Generus extends Model
{
    use SoftDeletes;

    public const MANAGE_PERMISSION = 'manage-generus';

    public const PHOTO_DIRECTORY = 'generus-photos';

    protected $table = 'generus';

    protected $fillable = [
        'registration_number',
        'record_number',
        'full_name',
        'school_name',
        'nis',
        'father_name',
        'mother_name',
        'father_occupation',
        'mother_occupation',
        'phone_number',
        'gender',
        'birth_place',
        'birth_date',
        'birth_order',
        'sibling_count',
        'school_grade',
        'learning_class',
        'educational_level',
        'photo',
        'status',
        'transfer_destination',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'birth_order' => 'integer',
            'sibling_count' => 'integer',
        ];
    }

    /**
     * Limit to generus whose active placement falls inside the user's role scopes.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->hasGlobalAccess(self::MANAGE_PERMISSION)) {
            return;
        }

        $placementIds = $user->scopedPlacementIds(self::MANAGE_PERMISSION);

        if ($placementIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('assignments', function (Builder $assignments) use ($placementIds): void {
            $assignments->where('status', 'active')
                ->where(function (Builder $placement) use ($placementIds): void {
                    foreach ($placementIds as $column => $ids) {
                        $placement->orWhereIn($column, $ids);
                    }
                });
        });
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(GenerusAssignment::class);
    }

    /**
     * Photos live on the private disk, so pages embed them inline instead of linking to a public URL.
     */
    public function photoDataUri(): ?string
    {
        if (blank($this->photo) || ! Storage::exists($this->photo)) {
            return null;
        }

        return 'data:'.Storage::mimeType($this->photo).';base64,'.base64_encode(Storage::get($this->photo));
    }
}
