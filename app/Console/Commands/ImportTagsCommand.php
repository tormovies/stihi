<?php

namespace App\Console\Commands;

use App\Models\Poem;
use App\Models\Tag;
use Illuminate\Console\Command;

class ImportTagsCommand extends Command
{
    protected $signature = 'tags:import {file : путь к JSON (например storage/app/tags_export.json)}';

    protected $description = 'Импорт тегов и привязок к стихам из JSON (по slug)';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("Файл не найден: {$path}");
            return self::FAILURE;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data['tags'], $data['poem_tags'])) {
            $this->error('Неверный формат JSON: нужны ключи tags и poem_tags.');
            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        foreach ($data['tags'] as $row) {
            $tag = Tag::where('slug', $row['slug'])->first();
            $fill = [
                'name' => $row['name'] ?? '',
                'slug' => $row['slug'] ?? '',
                'meta_title' => $row['meta_title'] ?? null,
                'meta_description' => $row['meta_description'] ?? null,
                'h1' => $row['h1'] ?? null,
                'h1_description' => $row['h1_description'] ?? null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];
            if ($tag) {
                $tag->update($fill);
                $updated++;
            } else {
                Tag::create($fill);
                $created++;
            }
        }
        $this->info("Теги: создано {$created}, обновлено {$updated}.");

        $poemSlugs = array_unique(array_column($data['poem_tags'], 'poem_slug'));
        $poems = Poem::whereIn('slug', $poemSlugs)->get()->keyBy('slug');
        $tagSlugs = array_unique(array_column($data['poem_tags'], 'tag_slug'));
        $tags = Tag::whereIn('slug', $tagSlugs)->get()->keyBy('slug');

        $attached = 0;
        $skipped = 0;
        foreach ($data['poem_tags'] as $row) {
            $poem = $poems->get($row['poem_slug'] ?? '');
            $tag = $tags->get($row['tag_slug'] ?? '');
            if (! $poem || ! $tag) {
                $skipped++;
                continue;
            }
            if (! $poem->tags()->where('tag_id', $tag->id)->exists()) {
                $poem->tags()->attach($tag->id);
                $attached++;
            }
        }
        $this->info("Привязки: добавлено {$attached}, пропущено (нет стиха/тега) {$skipped}.");

        return self::SUCCESS;
    }
}
