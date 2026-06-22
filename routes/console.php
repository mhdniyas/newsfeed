<?php

use Illuminate\Foundation\Inspiring;
use App\Jobs\RunNewsSyncCycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RunNewsSyncCycle('Automatic 10-minute scheduler sync'))
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty --queue=syncs,default --tries=1 --timeout=900')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
