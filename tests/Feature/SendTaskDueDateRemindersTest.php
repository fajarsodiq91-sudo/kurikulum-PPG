<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueDateReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendTaskDueDateRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => str($name)->slug().'@ppg.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
    }

    public function test_sends_h_minus_one_reminder_for_task_due_tomorrow_at_creation_hour(): void
    {
        Notification::fake();

        $this->travelTo(Carbon::create(2026, 1, 10, 9, 0, 0));

        $creator = $this->makeUser('Pembuat');
        $assignee = $this->makeUser('Penerima');

        $task = Task::create([
            'title' => 'Tugas Rapat',
            'priority' => 'medium',
            'status' => 'todo',
            'assigned_by_id' => $creator->id,
            'due_date' => '2026-01-11',
        ]);
        $task->assignees()->attach($assignee->id);

        $this->artisan('tasks:send-due-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $assignee,
            fn (TaskDueDateReminder $notification) => $notification->toMail($assignee)->subject === 'Pengingat Tenggat Tugas: Tugas Rapat',
        );

        $this->assertSame('2026-01-10', $task->refresh()->due_reminder_sent_on->toDateString());
    }

    public function test_sends_daily_reminder_for_overdue_task_not_closed(): void
    {
        Notification::fake();

        $this->travelTo(Carbon::create(2026, 1, 10, 9, 0, 0));

        $creator = $this->makeUser('Pembuat');
        $assignee = $this->makeUser('Penerima');

        $task = Task::create([
            'title' => 'Tugas Terlambat',
            'priority' => 'medium',
            'status' => 'in_progress',
            'assigned_by_id' => $creator->id,
            'due_date' => '2026-01-05',
        ]);
        $task->assignees()->attach($assignee->id);

        $this->artisan('tasks:send-due-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $assignee,
            fn (TaskDueDateReminder $notification) => $notification->toMail($assignee)->subject === 'Tugas Terlambat: Tugas Terlambat',
        );
    }

    public function test_does_not_send_reminder_twice_on_the_same_day(): void
    {
        Notification::fake();

        $this->travelTo(Carbon::create(2026, 1, 10, 9, 0, 0));

        $creator = $this->makeUser('Pembuat');
        $assignee = $this->makeUser('Penerima');

        $task = Task::create([
            'title' => 'Tugas Terlambat',
            'priority' => 'medium',
            'status' => 'todo',
            'assigned_by_id' => $creator->id,
            'due_date' => '2026-01-05',
        ]);
        $task->assignees()->attach($assignee->id);

        $this->artisan('tasks:send-due-reminders');
        $this->artisan('tasks:send-due-reminders');

        Notification::assertSentToTimes($assignee, TaskDueDateReminder::class, 1);
    }

    public function test_does_not_send_reminder_at_a_different_hour_than_task_creation(): void
    {
        Notification::fake();

        $this->travelTo(Carbon::create(2026, 1, 10, 9, 0, 0));

        $creator = $this->makeUser('Pembuat');
        $assignee = $this->makeUser('Penerima');

        $task = Task::create([
            'title' => 'Tugas Terlambat',
            'priority' => 'medium',
            'status' => 'todo',
            'assigned_by_id' => $creator->id,
            'due_date' => '2026-01-05',
        ]);
        $task->assignees()->attach($assignee->id);

        $this->travelTo(Carbon::create(2026, 1, 10, 14, 0, 0));

        $this->artisan('tasks:send-due-reminders');

        Notification::assertNothingSent();
    }

    public function test_does_not_send_reminder_for_task_marked_as_done(): void
    {
        Notification::fake();

        $this->travelTo(Carbon::create(2026, 1, 10, 9, 0, 0));

        $creator = $this->makeUser('Pembuat');
        $assignee = $this->makeUser('Penerima');

        $task = Task::create([
            'title' => 'Tugas Selesai',
            'priority' => 'medium',
            'status' => 'done',
            'assigned_by_id' => $creator->id,
            'due_date' => '2026-01-05',
        ]);
        $task->assignees()->attach($assignee->id);

        $this->artisan('tasks:send-due-reminders');

        Notification::assertNothingSent();
    }
}
