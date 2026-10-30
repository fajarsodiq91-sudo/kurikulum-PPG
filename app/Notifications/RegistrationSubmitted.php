<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationSubmitted extends Notification
{
    public function __construct(private readonly User $pending) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pendaftaran Baru Menunggu Persetujuan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->pending->name.' ('.$this->pending->email.') baru saja mendaftar dan menunggu persetujuan Anda.')
            ->action('Tinjau Pendaftaran', route('registrations.pending'));
    }
}
