<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Domain;
use App\Jobs\CheckDomainJob;
use App\Jobs\CheckPageSpeedJob;
use App\Jobs\CleanupJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 1. Domain Check (Uptime, SSL) - Every 15 Minutes
Schedule::call(function () {
    $domains = Domain::all();
    foreach ($domains as $domain) {
        CheckDomainJob::dispatch($domain);
    }
})->everyFifteenMinutes()->name('check_domains')->withoutOverlapping();

// 2. Spectora Audit (Performance, SEO, Security) - Hourly
Schedule::call(function () {
    $domains = Domain::all();
    foreach ($domains as $domain) {
        \App\Jobs\PerformSpectoraAudit::dispatch($domain);
    }
})->hourly()->name('spectora_audit')->withoutOverlapping();

// 3. Cleanup Old Data - Daily (at midnight)
Schedule::command('model:prune')->daily();

// 4. Monthly Reports - 1st of Month at 08:00
Schedule::job(new \App\Jobs\SendMonthlyReportsJob)->monthlyOn(1, '08:00');
