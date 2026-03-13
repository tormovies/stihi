<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * DeepSeek: расписание берётся из настроек в админке (DeepSeek → Расписание cron).
 * Значения: off = выключено, 5|10|15|20|30 = интервал в минутах.
 *
 * 1) SEO стихов — deepseek:run-poems (настройка cron_run_poems).
 * 2) Анализы стихов — deepseek:run-analyses (настройка cron_run_analyses).
 */
$cronRunPoems = Setting::get('cron_run_poems', 'off');
$cronRunAnalyses = Setting::get('cron_run_analyses', '5');

if ($cronRunPoems !== 'off' && in_array($cronRunPoems, ['5', '10', '15', '20', '30'], true)) {
    match ($cronRunPoems) {
        '5' => Schedule::command('deepseek:run-poems')->everyFiveMinutes(),
        '10' => Schedule::command('deepseek:run-poems')->everyTenMinutes(),
        '15' => Schedule::command('deepseek:run-poems')->everyFifteenMinutes(),
        '20' => Schedule::command('deepseek:run-poems')->cron('0,20,40 * * * *'),
        '30' => Schedule::command('deepseek:run-poems')->everyThirtyMinutes(),
        default => null,
    };
}

if ($cronRunAnalyses !== 'off' && in_array($cronRunAnalyses, ['5', '10', '15', '20', '30'], true)) {
    match ($cronRunAnalyses) {
        '5' => Schedule::command('deepseek:run-analyses')->everyFiveMinutes(),
        '10' => Schedule::command('deepseek:run-analyses')->everyTenMinutes(),
        '15' => Schedule::command('deepseek:run-analyses')->everyFifteenMinutes(),
        '20' => Schedule::command('deepseek:run-analyses')->cron('2,22,42 * * * *'),
        '30' => Schedule::command('deepseek:run-analyses')->everyThirtyMinutes(),
        default => null,
    };
}
