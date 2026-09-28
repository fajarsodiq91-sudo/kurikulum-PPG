<?php

namespace App\Support;

use App\Models\Generus;
use App\Models\Guardian;

/**
 * Keeps a generus' father and mother names in sync with linked guardian records. A parent
 * with the same name and relationship is one guardian shared by all of their children.
 */
class ParentGuardians
{
    private const RELATIONSHIPS = ['father_name' => 'Ayah', 'mother_name' => 'Ibu'];

    public static function sync(Generus $generus): void
    {
        if (! $generus->wasRecentlyCreated && ! $generus->wasChanged(array_keys(self::RELATIONSHIPS))) {
            return;
        }

        foreach (self::RELATIONSHIPS as $column => $relationship) {
            $name = trim((string) $generus->{$column});

            $generus->guardians()->detach($generus->guardians()->where('relationship', $relationship)->pluck('guardians.id')->all());

            if ($name === '') {
                continue;
            }

            $guardian = Guardian::query()
                ->whereRaw('lower(full_name) = ?', [mb_strtolower($name)])
                ->where('relationship', $relationship)
                ->first() ?? Guardian::create(['full_name' => $name, 'relationship' => $relationship, 'status' => 'active']);

            $generus->guardians()->syncWithoutDetaching([$guardian->id]);
        }
    }
}
