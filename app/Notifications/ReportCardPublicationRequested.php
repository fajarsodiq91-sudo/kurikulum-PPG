<?php

namespace App\Notifications;

use App\Models\ReportCardGroupConfirmation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportCardPublicationRequested extends Notification
{
    public function __construct(private readonly ReportCardGroupConfirmation $confirmation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $publication = $this->confirmation->publication;

        return (new MailMessage)
            ->subject('Konfirmasi Penerbitan Rapor: '.$publication->semester->name.' '.$publication->academicYear->name)
            ->greeting('Assalamu\'alaikum, '.$notifiable->name.'.')
            ->line('PPG Daerah telah mengonfirmasi penerbitan rapor untuk '.$publication->semester->name.' '.$publication->academicYear->name.'.')
            ->line('Sebelum rapor kelompok Anda terbit, mohon konfirmasi generus yang tinggal kelas. Generus yang tidak ditandai akan otomatis naik kelas.')
            ->action('Konfirmasi Sekarang', route('report-card-group-confirmations.edit', $this->confirmation))
            ->line('Alhamdulillahi jaza kumullahu khoiro.')
            ->salutation('Wassalamu\'alaikum warahmatullahi wabarakatuh.');
    }
}
