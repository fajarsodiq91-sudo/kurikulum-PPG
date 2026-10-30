<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChanged extends Notification
{
    public function __construct(private readonly Task $task, private readonly User $changedBy) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'todo' => 'Belum Dikerjakan',
            'in_progress' => 'Sedang Dikerjakan',
            'done' => 'Selesai',
        ];

        return (new MailMessage)
            ->subject('Status Tugas Diperbarui: '.$this->task->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->changedBy->name.' mengubah status tugas "'.$this->task->title.'" menjadi "'.($statusLabels[$this->task->status] ?? $this->task->status).'".')
            ->action('Lihat Tugas', route('tasks.index'));
    }
}
