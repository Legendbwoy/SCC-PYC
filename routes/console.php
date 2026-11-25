<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pyc:sync-google-members')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('pyc:generate-monthly-dues')
    ->monthlyOn(1, '00:15')
    ->timezone('Africa/Accra')
    ->withoutOverlapping();
