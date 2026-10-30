<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskNotification extends Notification
{
    public function __construct(private readonly Task $task, private readonly string $action, private readonly User $actor) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->action === 'unassigned') {
            return (new MailMessage)
                ->subject('Tugas Dibatalkan: '.$this->task->title)
                ->greeting('Halo '.$notifiable->name.',')
                ->line($this->actor->name.' telah menghapus Anda dari tugas "'.$this->task->title.'". Anda tidak perlu lagi mengerjakannya.')
                ->action('Lihat Papan Tugas', route('tasks.index'));
        }

        $verb = $this->action === 'created' ? 'menugaskan Anda pada tugas baru' : 'memperbarui tugas';

        return (new MailMessage)
            ->subject(($this->action === 'created' ? 'Tugas Baru: ' : 'Tugas Diperbarui: ').$this->task->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->actor->name.' telah '.$verb.' "'.$this->task->title.'".')
            ->when($this->task->description !== null, fn (MailMessage $mail) => $mail->line($this->task->description))
            ->line('Prioritas: '.ucfirst($this->task->priority))
            ->when($this->task->due_date !== null, fn (MailMessage $mail) => $mail->line('Tenggat: '.$this->task->due_date->translatedFormat('d F Y')))
            ->action('Lihat Tugas', route('tasks.index'));
    }
}
