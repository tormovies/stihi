<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;

class ListTagNamesCommand extends Command
{
    protected $signature = 'tags:list-names';

    protected $description = 'Вывести названия тегов по одному в строке (для массового добавления на другом сервере)';

    public function handle(): int
    {
        $names = Tag::orderBy('sort_order')->orderBy('name')->pluck('name');
        $this->line($names->implode("\n"));
        return self::SUCCESS;
    }
}
