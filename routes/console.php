<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Shared hosting (cPanel, no SSH/Supervisor) can't run a persistent
 * `queue:work` daemon - a single `* * * * * php artisan schedule:run` cron
 * entry driving everything from here is the standard workaround: schedule:run
 * itself does nothing but check what's due each minute, so the actual queue
 * processing is defined as a scheduled command instead of a second cron line.
 * --stop-when-empty exits as soon as the queue drains; --max-time=55 forces
 * an exit before the next minute's schedule:run invocation either way.
 */
Schedule::command('queue:work --stop-when-empty --max-time=55 --sleep=1 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

/**
 * Nightly reconciliation safety net - webhook delivery isn't 100% guaranteed
 * by Shopify, so this catches anything a missed webhook would otherwise leave
 * stale. See README "Scheduled / manual sync commands".
 */
Schedule::command('shopify:sync')
    ->dailyAt('03:00')
    ->withoutOverlapping();
