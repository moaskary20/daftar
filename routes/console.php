<?php

use App\Services\BackupService;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(NotificationService::class)->generateAll())
    ->hourly()
    ->name('daftar-notifications')
    ->withoutOverlapping();

Schedule::call(fn () => app(BackupService::class)->create())
    ->dailyAt('02:00')
    ->name('daftar-daily-backup')
    ->withoutOverlapping();
