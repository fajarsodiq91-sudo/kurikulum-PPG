<?php

namespace App\Support;

use App\Models\ClassGrade;
use App\Models\Generus;
use Illuminate\Support\Collection;

/**
 * The kelas-KBM sequence a generus moves through on promotion: every active class grade,
 * ordered first by its level's sort_order (PAUD, Caberawit, Pra Remaja, Remaja, Pra Nikah)
 * then by its own sort_order within that level. Kenaikan kelas only ever moves a generus to
 * the next entry in this single flattened sequence — never set directly.
 */
class GradePromotionService
{
    /**
     * The class grade right after $current in the curriculum sequence, or null when $current
     * is the last one (nothing further to promote into).
     */
    public static function nextClassGradeFor(ClassGrade $current): ?ClassGrade
    {
        $sequence = self::sequence();
        $index = $sequence->search(fn (ClassGrade $grade): bool => $grade->id === $current->id);

        if ($index === false) {
            return null;
        }

        return $sequence->get($index + 1);
    }

    /**
     * Moves $generus into $to (or clears its kelas KBM when $to is null, e.g. the sequence
     * ended). This is the only place learning_class_id should change after a generus's
     * initial placement — always as a result of a munaqosah decision, never a direct edit.
     */
    public static function promote(Generus $generus, ?ClassGrade $to): void
    {
        $generus->update(['learning_class_id' => $to?->id]);
    }

    /**
     * @return Collection<int, ClassGrade>
     */
    private static function sequence(): Collection
    {
        return ClassGrade::where('is_active', true)
            ->whereHas('level', fn ($query) => $query->where('is_active', true))
            ->with('level')
            ->get()
            ->sortBy(fn (ClassGrade $grade): string => sprintf('%05d-%05d-%05d', $grade->level->sort_order, $grade->sort_order, $grade->id))
            ->values();
    }
}
