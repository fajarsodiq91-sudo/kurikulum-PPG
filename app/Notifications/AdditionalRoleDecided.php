<?php

namespace App\Notifications;

use App\Models\Role;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdditionalRoleDecided extends Notification
{
    public function __construct(private readonly bool $approved, private readonly Role $role) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject('Pengajuan Peran Tambahan Disetujui')
                ->greeting('Halo '.$notifiable->name.',')
                ->line('Pengajuan peran tambahan Anda sebagai "'.$this->role->name.'" telah disetujui.');
        }

        return (new MailMessage)
            ->subject('Pengajuan Peran Tambahan Ditolak')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Mohon maaf, pengajuan peran tambahan Anda sebagai "'.$this->role->name.'" tidak dapat disetujui. Silakan hubungi admin untuk informasi lebih lanjut.');
    }
}
