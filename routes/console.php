<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled as a closure (not Schedule::command()) because the production host
// disables proc_open/exec/shell_exec, which Schedule::command() needs to spawn a
// subprocess. Schedule::call() runs in-process, so it works under that restriction.
Schedule::call(fn () => Artisan::call('tasks:send-due-reminders'))->hourly();
