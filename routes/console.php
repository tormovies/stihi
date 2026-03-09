<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * DeepSeek: два независимых процесса, не пересекаются по данным.
 *
 * 1) SEO стихов — meta_title, meta_description, h1, h1_description для страницы стиха (таблица poems).
 *    Запуск: кнопка «Запустить оптимизацию» в админке или cron: php artisan deepseek:run-poems
 *    Обрабатывает стихи, у которых ещё нет SEO (пачкой по N из настроек).
 *
 * 2) Анализы стихов — полный текст анализа + SEO для страницы /{slug}/analiz/ (таблица poem_analyses).
 *    Запуск: кнопка «Запустить анализы» в админке или cron: php artisan deepseek:run-analyses
 *    Обрабатывает по одному длинному стиху (body_length >= порог) без записи в poem_analyses.
 *
 * Запуски по крону разнесены по минутам, чтобы не дергать API одновременно.
 */
Schedule::command('deepseek:run-poems')->everyTenMinutes();
Schedule::command('deepseek:run-analyses')->cron('5,15,25,35,45,55 * * * *');
