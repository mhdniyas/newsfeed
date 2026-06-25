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

Schedule::command('news:extract-articles --limit=10')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::job(new RunTrendSyncCycle('Automatic 5-minute scheduler trend sync'))
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('lottery:sync-kerala-results --limit=1')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Gold Rates Fetch Schedule (10:30 AM, 12:30 PM, 4:30 PM, 8:30 PM IST)
Schedule::command('gold:fetch-rates')
    ->timezone('Asia/Kolkata')
    ->at('10:30')
    ->withoutOverlapping();

Schedule::command('gold:fetch-rates')
    ->timezone('Asia/Kolkata')
    ->at('12:30')
    ->withoutOverlapping();

Schedule::command('gold:fetch-rates')
    ->timezone('Asia/Kolkata')
    ->at('16:30')
    ->withoutOverlapping();

Schedule::command('gold:fetch-rates')
    ->timezone('Asia/Kolkata')
    ->at('20:30')
    ->withoutOverlapping();
