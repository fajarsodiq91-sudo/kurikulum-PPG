<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountCreated extends Notification
{
    public function __construct(private readonly string $plainPassword) {}

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
            ->subject('Akun Anda Telah Dibuat')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Admin telah membuatkan akun untuk Anda di aplikasi PPG Bidang Pendidikan Karawang Timur.')
            ->line('Berikut detail akun Anda:')
            ->line('Email: '.$notifiable->email)
            ->line('Kata Sandi: '.$this->plainPassword)
            ->action('Masuk ke Aplikasi', route('login'))
            ->line('Demi keamanan, segera ganti kata sandi Anda setelah masuk.');
    }
}
