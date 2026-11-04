<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Shared weekly-recurrence logic for DefaultSchedule and GroupDefaultSchedule: both store
 * days_of_week + a date range and relate to an exceptions table keyed the same way.
 */
trait HasRecurringSchedulePattern
{
    /**
     * Whether this pattern applies to $date: within its date range and on one of its weekdays.
     * True even on a date marked as a holiday — use occursOn() to also exclude those.
     */
    public function appliesTo(Carbon $date): bool
    {
        if ($date->lt($this->start_date) || $date->gt($this->end_date)) {
            return false;
        }

        return in_array($date->isoWeekday(), $this->days_of_week ?? [], true);
    }

    /**
     * Whether this pattern lands on $date: applies to it, and not marked as a holiday.
     */
    public function occursOn(Carbon $date): bool
    {
        return $this->appliesTo($date) && ! $this->exceptionOn($date);
    }

    public function exceptionOn(Carbon $date): ?object
    {
        return $this->exceptions->first(fn (object $exception): bool => $exception->exception_date->isSameDay($date));
    }
}
