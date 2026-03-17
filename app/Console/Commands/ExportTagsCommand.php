<?php

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;

class ExportTagsCommand extends Command
{
    protected $signature = 'tags:export {--output= : путь к файлу (по умолчанию storage/app/tags_export.json)}';

    protected $description = 'Экспорт тегов и привязок к стихам в JSON (по slug, для переноса на другой сервер)';

    public function handle(): int
    {
        $path = $this->option('output') ?: storage_path('app/tags_export.json');

        $tags = Tag::orderBy('sort_order')->orderBy('name')->get();
        $tagsData = $tags->map(fn ($tag) => [
            'name' => $tag->name,
            'slug' => $tag->slug,
            'meta_title' => $tag->meta_title,
            'meta_description' => $tag->meta_description,
            'h1' => $tag->h1,
            'h1_description' => $tag->h1_description,
            'sort_order' => $tag->sort_order,
        ])->values()->all();

        $poemTagData = [];
        foreach ($tags as $tag) {
            $poems = $tag->poems()->get(['poems.slug']);
            foreach ($poems as $poem) {
                $poemTagData[] = ['poem_slug' => $poem->slug, 'tag_slug' => $tag->slug];
            }
        }

        $export = [
            'tags' => $tagsData,
            'poem_tags' => $poemTagData,
            'exported_at' => now()->toIso8601String(),
        ];

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info("Экспорт записан: {$path}");
        $this->line('Тегов: ' . count($tagsData) . ', привязок стих–тег: ' . count($poemTagData));

        return self::SUCCESS;
    }
}
