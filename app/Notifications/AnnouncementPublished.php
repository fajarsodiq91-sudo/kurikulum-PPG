<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification
{
    public function __construct(private readonly Announcement $announcement) {}

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
            ->subject('Berita Baru: '.$this->announcement->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Ada berita baru yang baru saja dipublikasikan: "'.$this->announcement->title.'".')
            ->action('Lihat Berita', route('announcements.index'));
    }
}
