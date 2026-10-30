<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueDateReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

#[Signature('tasks:send-due-reminders')]
#[Description('Send an H-1 due date reminder, and a daily reminder for overdue tasks, to task assignees')]
class SendTaskDueDateReminders extends Command
{
    public function handle(): int
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $tasks = Task::query()
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->where(function (Builder $query) use ($today) {
                $query->whereNull('due_reminder_sent_on')
                    ->orWhereDate('due_reminder_sent_on', '<', $today);
            })
            ->where(function (Builder $query) use ($tomorrow) {
                $query->whereDate('due_date', $tomorrow)
                    ->orWhereDate('due_date', '<', $tomorrow);
            })
            ->get()
            ->filter(fn (Task $task): bool => $task->created_at->hour === $now->hour);

        foreach ($tasks as $task) {
            $daysRemaining = (int) $today->diffInDays($task->due_date, false);

            Notification::send($task->assignees, new TaskDueDateReminder($task, $daysRemaining));

            $task->update(['due_reminder_sent_on' => $today]);
        }

        $this->info($tasks->count().' pengingat tenggat tugas terkirim.');

        return self::SUCCESS;
    }
}
