<?php

namespace App\Console\Commands;

use App\Services\DeepSeekOptimizeService;
use Illuminate\Console\Command;

class DeepSeekRunPoemTags extends Command
{
    protected $signature = 'deepseek:run-poem-tags';
    protected $description = 'Запустить один батч разметки стихов по тегам через DeepSeek (для cron/планировщика)';

    public function handle(DeepSeekOptimizeService $service): int
    {
        $this->info('Запуск батча разметки стихов по тегам...');
        $result = $service->runPoemTagsBatch();

        if ($result['error']) {
            $this->error($result['error']);
            return self::FAILURE;
        }

        if ($result['message']) {
            $this->info($result['message']);
        }
        $this->line('Обработано стихов: ' . $result['processed'] . ', ошибки (id): ' . implode(', ', $result['failed'] ?: ['нет']));
        return self::SUCCESS;
    }
}
