<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueDateReminder extends Notification
{
    public function __construct(private readonly Task $task, private readonly int $daysRemaining) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->task->due_date->translatedFormat('d F Y');

        $mail = (new MailMessage)
            ->greeting('Halo '.$notifiable->name.',')
            ->when($this->task->description !== null, fn (MailMessage $mail) => $mail->line($this->task->description))
            ->line('Tenggat: '.$dueDate)
            ->action('Lihat Tugas', route('tasks.index'));

        if ($this->daysRemaining > 0) {
            return $mail->subject('Pengingat Tenggat Tugas: '.$this->task->title)
                ->line('Tugas "'.$this->task->title.'" akan berakhir dalam '.$this->daysRemaining.' hari lagi.');
        }

        $daysOverdue = abs($this->daysRemaining);

        return $mail->subject('Tugas Terlambat: '.$this->task->title)
            ->line('Tugas "'.$this->task->title.'" sudah melewati tenggat sejak '.$daysOverdue.' hari yang lalu dan belum diselesaikan.');
    }
}
