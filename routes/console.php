<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync JPayroll attendance every day at 06:00 (covers yesterday + today by default)
Schedule::command('jpayroll:sync-attendance')->dailyAt('06:00');
