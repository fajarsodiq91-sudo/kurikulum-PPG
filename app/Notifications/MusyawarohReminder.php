<?php

namespace App\Notifications;

use App\Models\Musyawaroh;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MusyawarohReminder extends Notification
{
    public function __construct(private readonly Musyawaroh $musyawaroh) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Pengingat Musyawaroh'.($this->musyawaroh->title ? ': '.$this->musyawaroh->title : ''))
            ->greeting('Assalamu\'alaikum, '.$notifiable->name.'.')
            ->line('Mengingatkan bahwa Anda diundang untuk menghadiri musyawaroh'.($this->musyawaroh->title ? ' "'.$this->musyawaroh->title.'"' : '').'.')
            ->line('Hari & Tanggal: '.$this->musyawaroh->meeting_date->translatedFormat('l, d F Y'));

        if ($this->musyawaroh->start_time) {
            $message->line('Jam: '.substr($this->musyawaroh->start_time, 0, 5).($this->musyawaroh->end_time ? '–'.substr($this->musyawaroh->end_time, 0, 5) : ''));
        }

        if ($this->musyawaroh->location) {
            $message->line('Lokasi: '.$this->musyawaroh->location);
        }

        return $message->action('Lihat Detail Musyawaroh', route('musyawaroh.show', $this->musyawaroh))
            ->line('Alhamdulillahi jaza kumullahu khoiro.')
            ->salutation('Wassalamu\'alaikum warahmatullahi wabarakatuh.');
    }
}
