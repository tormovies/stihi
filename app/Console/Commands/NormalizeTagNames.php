<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;

class NormalizeTagNames extends Command
{
    protected $signature = 'tags:normalize-names';
    protected $description = 'Привести названия всех тегов к одному виду: первая буква заглавная, остальные строчные';

    public function handle(): int
    {
        $tags = Tag::all();
        $updated = 0;

        foreach ($tags as $tag) {
            $name = $tag->name;
            if ($name === '') {
                continue;
            }
            $normalized = mb_strtoupper(mb_substr($name, 0, 1)) . mb_strtolower(mb_substr($name, 1));
            if ($name !== $normalized) {
                $tag->update(['name' => $normalized]);
                $updated++;
                $this->line("  {$name} → {$normalized}");
            }
        }

        $this->info("Готово. Обновлено тегов: {$updated} из {$tags->count()}.");
        return self::SUCCESS;
    }
}
