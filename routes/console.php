<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Каждые 10 минут — один батч SEO-оптимизации стихов через DeepSeek (размер батча и таймаут в админке)
Schedule::command('deepseek:run-poems')->everyTenMinutes();
