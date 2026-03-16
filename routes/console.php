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
 * Значения: off = выключено, 1|2|3|4|5|10|15|20|30 = интервал в минутах.
 *
 * 1) SEO стихов — deepseek:run-poems (cron_run_poems).
 * 2) Анализы стихов — deepseek:run-analyses (cron_run_analyses).
 * 3) SEO страниц тегов — deepseek:run-tags-seo (cron_run_tags_seo).
 * 4) Разметка стихов по тегам — deepseek:run-poem-tags (cron_run_poem_tags).
 */
$cronRunPoems = Setting::get('cron_run_poems', 'off');
$cronRunAnalyses = Setting::get('cron_run_analyses', '5');
$cronRunTagsSeo = Setting::get('cron_run_tags_seo', 'off');
$cronRunPoemTags = Setting::get('cron_run_poem_tags', 'off');

if ($cronRunPoems !== 'off' && in_array($cronRunPoems, ['1', '2', '3', '4', '5', '10', '15', '20', '30'], true)) {
    match ($cronRunPoems) {
        '1' => Schedule::command('deepseek:run-poems')->cron('* * * * *'),
        '2' => Schedule::command('deepseek:run-poems')->cron('*/2 * * * *'),
        '3' => Schedule::command('deepseek:run-poems')->cron('*/3 * * * *'),
        '4' => Schedule::command('deepseek:run-poems')->cron('*/4 * * * *'),
        '5' => Schedule::command('deepseek:run-poems')->everyFiveMinutes(),
        '10' => Schedule::command('deepseek:run-poems')->everyTenMinutes(),
        '15' => Schedule::command('deepseek:run-poems')->everyFifteenMinutes(),
        '20' => Schedule::command('deepseek:run-poems')->cron('0,20,40 * * * *'),
        '30' => Schedule::command('deepseek:run-poems')->everyThirtyMinutes(),
        default => null,
    };
}

if ($cronRunAnalyses !== 'off' && in_array($cronRunAnalyses, ['1', '2', '3', '4', '5', '10', '15', '20', '30'], true)) {
    match ($cronRunAnalyses) {
        '1' => Schedule::command('deepseek:run-analyses')->cron('* * * * *'),
        '2' => Schedule::command('deepseek:run-analyses')->cron('*/2 * * * *'),
        '3' => Schedule::command('deepseek:run-analyses')->cron('*/3 * * * *'),
        '4' => Schedule::command('deepseek:run-analyses')->cron('*/4 * * * *'),
        '5' => Schedule::command('deepseek:run-analyses')->everyFiveMinutes(),
        '10' => Schedule::command('deepseek:run-analyses')->everyTenMinutes(),
        '15' => Schedule::command('deepseek:run-analyses')->everyFifteenMinutes(),
        '20' => Schedule::command('deepseek:run-analyses')->cron('2,22,42 * * * *'),
        '30' => Schedule::command('deepseek:run-analyses')->everyThirtyMinutes(),
        default => null,
    };
}

if ($cronRunTagsSeo !== 'off' && in_array($cronRunTagsSeo, ['1', '2', '3', '4', '5', '10', '15', '20', '30'], true)) {
    match ($cronRunTagsSeo) {
        '1' => Schedule::command('deepseek:run-tags-seo')->cron('* * * * *'),
        '2' => Schedule::command('deepseek:run-tags-seo')->cron('*/2 * * * *'),
        '3' => Schedule::command('deepseek:run-tags-seo')->cron('*/3 * * * *'),
        '4' => Schedule::command('deepseek:run-tags-seo')->cron('*/4 * * * *'),
        '5' => Schedule::command('deepseek:run-tags-seo')->everyFiveMinutes(),
        '10' => Schedule::command('deepseek:run-tags-seo')->everyTenMinutes(),
        '15' => Schedule::command('deepseek:run-tags-seo')->everyFifteenMinutes(),
        '20' => Schedule::command('deepseek:run-tags-seo')->cron('4,24,44 * * * *'),
        '30' => Schedule::command('deepseek:run-tags-seo')->everyThirtyMinutes(),
        default => null,
    };
}

if ($cronRunPoemTags !== 'off' && in_array($cronRunPoemTags, ['1', '2', '3', '4', '5', '10', '15', '20', '30'], true)) {
    match ($cronRunPoemTags) {
        '1' => Schedule::command('deepseek:run-poem-tags')->cron('* * * * *'),
        '2' => Schedule::command('deepseek:run-poem-tags')->cron('*/2 * * * *'),
        '3' => Schedule::command('deepseek:run-poem-tags')->cron('*/3 * * * *'),
        '4' => Schedule::command('deepseek:run-poem-tags')->cron('*/4 * * * *'),
        '5' => Schedule::command('deepseek:run-poem-tags')->everyFiveMinutes(),
        '10' => Schedule::command('deepseek:run-poem-tags')->everyTenMinutes(),
        '15' => Schedule::command('deepseek:run-poem-tags')->everyFifteenMinutes(),
        '20' => Schedule::command('deepseek:run-poem-tags')->cron('6,26,46 * * * *'),
        '30' => Schedule::command('deepseek:run-poem-tags')->everyThirtyMinutes(),
        default => null,
    };
}
