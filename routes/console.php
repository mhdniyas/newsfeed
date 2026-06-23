<?php

use Illuminate\Foundation\Inspiring;
use App\Jobs\RunNewsSyncCycle;
use App\Jobs\RunTrendSyncCycle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new RunNewsSyncCycle('Automatic 2-minute scheduler sync'))
    ->everyTwoMinutes()
    ->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty --queue=syncs,default --tries=1 --timeout=900')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('news:prune-old')
    ->daily()
    ->withoutOverlapping();

Schedule::job(new RunTrendSyncCycle('Automatic 5-minute scheduler trend sync'))
    ->everyFiveMinutes()
    ->withoutOverlapping();
