<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationDecided extends Notification
{
    public function __construct(private readonly bool $approved) {}

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
                ->subject('Pendaftaran Anda Disetujui')
                ->greeting('Assalamu\'alaikum, '.$notifiable->name.'.')
                ->line('Pendaftaran akun Anda telah disetujui. Anda sekarang dapat masuk ke aplikasi.')
                ->action('Masuk', route('login'))
                ->line('Alhamdulillahi jaza kumullahu khoiro.')
                ->salutation('Wassalamu\'alaikum warahmatullahi wabarakatuh.');
        }

        return (new MailMessage)
            ->subject('Pendaftaran Anda Ditolak')
            ->greeting('Assalamu\'alaikum, '.$notifiable->name.'.')
            ->line('Mohon maaf, pendaftaran akun Anda tidak dapat disetujui. Silakan hubungi admin untuk informasi lebih lanjut.')
            ->line('Alhamdulillahi jaza kumullahu khoiro.')
            ->salutation('Wassalamu\'alaikum warahmatullahi wabarakatuh.');
    }
}
