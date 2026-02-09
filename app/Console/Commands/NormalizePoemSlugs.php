<?php

namespace App\Console\Commands;

use App\Models\Poem;
use App\Support\SlugNormalizer;
use Illuminate\Console\Command;

class NormalizePoemSlugs extends Command
{
    protected $signature = 'poem:normalize-slugs {--dry-run : Не менять БД, только показать}';
    protected $description = 'Нормализовать slug стихов до [a-z0-9\-]+: подчёркивание → дефис, № (и %e2%84%96) → -no-';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $poems = Poem::whereNotNull('published_at')->get();
        $updated = 0;
        $duplicates = [];

        foreach ($poems as $poem) {
            if (SlugNormalizer::isValid($poem->slug)) {
                continue;
            }
            $newSlug = SlugNormalizer::normalize($poem->slug);
            if ($newSlug === $poem->slug) {
                continue;
            }
            // Проверка уникальности
            $exists = Poem::where('slug', $newSlug)->where('id', '!=', $poem->id)->exists();
            if ($exists) {
                $duplicates[] = ['id' => $poem->id, 'title' => $poem->title, 'old' => $poem->slug, 'new' => $newSlug];
                $newSlug = $newSlug . '-' . $poem->id;
            }
            $this->line("id {$poem->id}: " . $poem->slug . " → " . $newSlug);
            if (!$dryRun) {
                $poem->update(['slug' => $newSlug]);
                $updated++;
            }
        }

        if (count($duplicates) > 0) {
            $this->warn('Для уникальности к slug добавлен id у записей: ' . count($duplicates));
        }
        $this->info($dryRun ? 'Режим dry-run: изменений нет.' : "Обновлено slug: {$updated}.");
        return self::SUCCESS;
    }
}
