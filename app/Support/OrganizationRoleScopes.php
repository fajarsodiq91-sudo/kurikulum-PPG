<?php

namespace App\Support;

/**
 * The three organisation roles a member may hold, their fixed scope type, and the daerah > desa
 * > kelompok hierarchy order used to decide which additional role a member may request for
 * themselves (always one strictly below every role they already hold).
 */
class OrganizationRoleScopes
{
    /**
     * @var array<string, string>
     */
    public const SCOPES = [
        'ppg' => 'global',
        'perwakilan-ppg-desa' => 'village',
        'pelaksana-ppg-kelompok' => 'group',
    ];

    /**
     * Highest (daerah) to lowest (kelompok) level.
     *
     * @var list<string>
     */
    public const ORDER = ['ppg', 'perwakilan-ppg-desa', 'pelaksana-ppg-kelompok'];

    public static function levelOf(string $roleSlug): ?int
    {
        $index = array_search($roleSlug, self::ORDER, true);

        return $index === false ? null : $index;
    }

    /**
     * Role slugs strictly below the highest-ranked slug already held (lowest level number), in
     * hierarchy order. A member holding only "perwakilan-ppg-desa" may request "pelaksana-ppg-
     * kelompok" but never "ppg", even though they don't hold "ppg" either.
     *
     * @param  iterable<string>  $heldSlugs
     * @return list<string>
     */
    public static function rolesBelowAll(iterable $heldSlugs): array
    {
        $levels = array_filter(
            array_map(self::levelOf(...), [...$heldSlugs]),
            fn (?int $level): bool => $level !== null
        );

        if ($levels === []) {
            return [];
        }

        return array_slice(self::ORDER, min($levels) + 1);
    }
}
