<?php

namespace App\Console\Commands;

use App\Models\Musyawaroh;
use App\Notifications\MusyawarohReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

#[Signature('musyawaroh:send-reminders')]
#[Description('Send the scheduled reminder email to invited attendees of upcoming musyawaroh')]
class SendMusyawarohReminders extends Command
{
    public function handle(): int
    {
        $now = Carbon::now();

        $musyawarohs = Musyawaroh::query()
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', $now)
            ->whereDate('meeting_date', '>=', $now->toDateString())
            ->with('attendees.user')
            ->get();

        foreach ($musyawarohs as $musyawaroh) {
            $recipients = $musyawaroh->attendees
                ->map(fn ($attendee) => $attendee->user)
                ->filter(fn ($user) => $user !== null && $user->status === 'active')
                ->unique('id')
                ->values();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new MusyawarohReminder($musyawaroh));
            }

            $musyawaroh->update(['reminder_sent_at' => $now]);
        }

        $this->info($musyawarohs->count().' pengingat musyawaroh terkirim.');

        return self::SUCCESS;
    }
}
