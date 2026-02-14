<?php

namespace App\Console\Commands;

use App\Models\Poem;
use Illuminate\Console\Command;

class PoemBodyLength extends Command
{
    protected $signature = 'poem:body-length {--dry-run : Не менять БД, только показать}';
    protected $description = 'Заполнить body_length: длина текста стиха без HTML (знаки с пробелами)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $updated = 0;

        Poem::query()->chunkById(200, function ($poems) use ($dryRun, &$updated) {
            foreach ($poems as $poem) {
                $textOnly = strip_tags((string) $poem->body);
                $length = mb_strlen($textOnly);

                if ((int) $poem->body_length !== $length) {
                    $this->line("id {$poem->id}: body_length = {$length}");
                    if (!$dryRun) {
                        $poem->update(['body_length' => $length]);
                        $updated++;
                    }
                }
            }
        });

        $this->info($dryRun ? 'Режим dry-run: изменений нет.' : "Обновлено записей: {$updated}.");
        return self::SUCCESS;
    }
}
