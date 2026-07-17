<?php

namespace App\Console\Commands;

use App\Services\PoemSunoDeepSeekService;
use Illuminate\Console\Command;

class DeepSeekRunSuno extends Command
{
    protected $signature = 'deepseek:run-suno {--poem= : ID стиха для принудительного переанализа}';
    protected $description = 'Запустить батч Suno-анализа стихов через DeepSeek (1 стих = 1 request)';

    public function handle(PoemSunoDeepSeekService $service): int
    {
        $poemId = $this->option('poem');
        $forceId = $poemId !== null && $poemId !== '' ? (int) $poemId : null;

        $this->info('Запуск батча Suno-анализа...');
        $result = $service->runBatch($forceId);

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
