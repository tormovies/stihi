<?php

namespace App\Console\Commands;

use App\Services\DeepSeekOptimizeService;
use Illuminate\Console\Command;

class DeepSeekRunAnalyses extends Command
{
    protected $signature = 'deepseek:run-analyses';
    protected $description = 'Запустить один батч генерации анализов длинных стихов через DeepSeek (для cron/планировщика)';

    public function handle(DeepSeekOptimizeService $service): int
    {
        $this->info('Запуск батча генерации анализов...');
        $result = $service->runAnalysisBatch();

        if ($result['error']) {
            $this->error($result['error']);
            return self::FAILURE;
        }

        if ($result['message']) {
            $this->info($result['message']);
        }
        $this->line('Обработано: ' . $result['processed'] . ', ошибки (id): ' . implode(', ', $result['failed'] ?: ['нет']));
        return self::SUCCESS;
    }
}
