<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;

class RemoveDuplicateTags extends Command
{
    protected $signature = 'tags:remove-duplicates
                            {--dry-run : Показать, что будет удалено, без удаления}';
    protected $description = 'Найти условные дубли (стихи о X / стихи про X), оставить тег с «про», удалить дубль и перенести связи стихов';

    private const PREFIX_O = 'стихи о ';
    private const PREFIX_PRO = 'стихи про ';
    private const ROOT_LENGTH = 4;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $tags = Tag::withCount('poems')->get();
        $groups = [];

        foreach ($tags as $tag) {
            $nameLower = mb_strtolower($tag->name);
            $prefix = null;
            $topic = null;

            if (mb_strpos($nameLower, self::PREFIX_PRO) === 0) {
                $prefix = 'про';
                $topic = trim(mb_substr($nameLower, mb_strlen(self::PREFIX_PRO)));
            } elseif (mb_strpos($nameLower, self::PREFIX_O) === 0) {
                $prefix = 'о';
                $topic = trim(mb_substr($nameLower, mb_strlen(self::PREFIX_O)));
            }

            if ($topic === null || $topic === '') {
                continue;
            }

            $root = mb_substr(preg_replace('/\s+/u', ' ', $topic), 0, self::ROOT_LENGTH);
            if ($root === '') {
                continue;
            }

            $groups[$root][] = [
                'tag' => $tag,
                'prefix' => $prefix,
            ];
        }

        $deleted = 0;
        foreach ($groups as $root => $items) {
            if (count($items) < 2) {
                continue;
            }

            $withPro = null;
            $withO = null;
            foreach ($items as $item) {
                if ($item['prefix'] === 'про') {
                    $withPro = $item['tag'];
                } else {
                    $withO = $item['tag'];
                }
            }

            if ($withPro === null || $withO === null) {
                continue;
            }

            if ($dryRun) {
                $this->line("  [dry-run] Удалить: «{$withO->name}» (id {$withO->id}), оставить: «{$withPro->name}» (id {$withPro->id}). Стихов у удаляемого: {$withO->poems_count}");
                $deleted++;
                continue;
            }

            foreach ($withO->poems as $poem) {
                $poem->tags()->syncWithoutDetaching([$withPro->id]);
            }
            $withO->delete();
            $this->line("  Удалён «{$withO->name}» (id {$withO->id}), оставлен «{$withPro->name}». Стихи перенесены.");
            $deleted++;
        }

        if ($dryRun) {
            $this->info("Dry-run: найдено пар дублей для удаления: {$deleted}. Запустите без --dry-run для выполнения.");
        } else {
            $this->info("Готово. Удалено дублей: {$deleted}.");
        }

        return self::SUCCESS;
    }
}
