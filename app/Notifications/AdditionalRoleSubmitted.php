<?php

namespace App\Notifications;

use App\Models\Role;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdditionalRoleSubmitted extends Notification
{
    public function __construct(private readonly User $member, private readonly Role $role) {}

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
            ->subject('Pengajuan Peran Tambahan Menunggu Persetujuan')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->member->name.' ('.$this->member->email.') mengajukan peran tambahan "'.$this->role->name.'" dan menunggu persetujuan Anda.')
            ->action('Tinjau Pengajuan', route('registrations.pending'));
    }
}
