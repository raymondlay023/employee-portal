<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync JPayroll employees and attendance every day at 09:00 (Asia/Jakarta timezone)
Schedule::command('jpayroll:sync-employees', ['--trigger' => 'scheduled'])->timezone('Asia/Jakarta')->dailyAt('08:00');
Schedule::command('jpayroll:sync-attendance', ['--trigger' => 'scheduled'])->timezone('Asia/Jakarta')->dailyAt('08:00');
Schedule::command('device:sync-attendance', ['--trigger' => 'scheduled'])->timezone('Asia/Jakarta')->dailyAt('08:00');
Schedule::command('device:prune-backups')->timezone('Asia/Jakarta')->dailyAt('02:00');

// Send monthly report notifications on the 4th of each month at 09:00
Schedule::command('reports:send-monthly')
    ->timezone('Asia/Jakarta')
    ->monthlyOn(4, '09:00')
    ->withoutOverlapping()
    ->environments(['production']);
