<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// LMS: remind learners of assignments/quizzes due soon and upcoming live classes.
Schedule::command('lms:reminders')->dailyAt('06:45')->withoutOverlapping(30);

// LMS: delete abandoned chunked-upload temp files.
Schedule::command('lms:prune-uploads')->dailyAt('03:30')->withoutOverlapping(30);

// Post yesterday's school-fee receipts to the general ledger
Schedule::command('accounting:sync-fees')->dailyAt('01:30')->withoutOverlapping(30);

// Monthly depreciation on fixed assets (for the month just ended)
Schedule::command('assets:depreciate')->monthlyOn(1, '03:00')->withoutOverlapping(30);

// Pull module feature flags from the remote control portal (only if auto-pull is on)
Schedule::command('features:pull')->hourly()->withoutOverlapping(10);

// Government remittance reminders (PAYE, pension, NHF) — 8am daily
Schedule::command('payroll:remittance-reminders')->dailyAt('08:00')->withoutOverlapping(30);

// Financial audit digest to auditors each morning.
Schedule::command('financial-audit:digest')->dailyAt('07:45')->withoutOverlapping(30);
