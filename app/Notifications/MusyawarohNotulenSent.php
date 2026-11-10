<?php

namespace App\Notifications;

use App\Models\Musyawaroh;
use App\Models\Notulen;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MusyawarohNotulenSent extends Notification
{
    /**
     * @param  Collection<int, Notulen>  $notulens
     */
    public function __construct(private readonly Musyawaroh $musyawaroh, private readonly Collection $notulens, private readonly User $sentBy) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Resume Notulen Musyawaroh: '.($this->musyawaroh->title ?: 'Musyawaroh'))
            ->greeting('Assalamu\'alaikum, '.$notifiable->name.'.')
            ->line($this->sentBy->name.' telah mengirimkan resume notulen musyawaroh'.($this->musyawaroh->title ? ' "'.$this->musyawaroh->title.'"' : '').' tanggal '.$this->musyawaroh->meeting_date->translatedFormat('d F Y').'.');

        foreach ($this->notulens as $index => $notulen) {
            $mail->line(($index + 1).'. '.$notulen->title);

            if ($notulen->content !== null && $notulen->content !== $notulen->title) {
                $mail->line('   '.$notulen->content);
            }
        }

        return $mail->action('Lihat Musyawaroh', route('musyawaroh.show', $this->musyawaroh))
            ->line('Alhamdulillahi jaza kumullahu khoiro.')
            ->salutation('Wassalamu\'alaikum warahmatullahi wabarakatuh.');
    }
}
