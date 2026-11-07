<?php

namespace App\Notifications;

use App\Models\Notulen;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotulenFinalized extends Notification
{
    public function __construct(private readonly Notulen $notulen, private readonly User $sentBy) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $musyawaroh = $this->notulen->musyawaroh;

        return (new MailMessage)
            ->subject('Notulen Musyawaroh: '.($musyawaroh?->title ?: 'Musyawaroh'))
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->sentBy->name.' telah mengirimkan notulen musyawaroh'.($musyawaroh?->title ? ' "'.$musyawaroh->title.'"' : '').'.')
            ->line('Poin notulen: '.$this->notulen->title)
            ->when($this->notulen->content && $this->notulen->content !== $this->notulen->title, fn (MailMessage $mail) => $mail->line($this->notulen->content))
            ->action('Lihat Musyawaroh', $musyawaroh ? route('musyawaroh.show', $musyawaroh) : route('musyawaroh.index'));
    }
}
