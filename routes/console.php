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
