<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

app()->booted(function () {
    $schedule = app(Schedule::class);

    $schedule->command('send:school-notifications')
        ->everyMinute();
});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
