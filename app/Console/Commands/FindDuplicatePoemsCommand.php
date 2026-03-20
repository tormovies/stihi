<?php

namespace App\Console\Commands;

use App\Models\Poem;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FindDuplicatePoemsCommand extends Command
{
    protected $signature = 'poems:find-duplicates
                            {--length-diff=20 : Макс. разница body_length в группе (знаков)}
                            {--include-unpublished : Учитывать и неопубликованные стихи}';

    protected $description = 'План дублей: один автор, одно название (нормализовано), разница длины текста ≤ порога (по умолчанию ±20 знаков в группе)';

    public function handle(): int
    {
        $maxLenDiff = max(0, (int) $this->option('length-diff'));
        $q = Poem::query()->with('author:id,name,slug');
        if (!$this->option('include-unpublished')) {
            $q->whereNotNull('published_at');
        }
        $poems = $q->orderBy('author_id')->orderBy('title')->get(['id', 'author_id', 'title', 'slug', 'body_length', 'published_at']);

        /** @var Collection<string, Collection<int, Poem>> $groups */
        $groups = $poems->groupBy(function (Poem $p) {
            return $p->author_id . "\t" . $this->normalizeTitle((string) $p->title);
        });

        $found = 0;
        foreach ($groups as $key => $group) {
            if ($group->count() < 2) {
                continue;
            }
            $lengths = $group->pluck('body_length')->map(fn ($l) => (int) $l);
            $minL = $lengths->min();
            $maxL = $lengths->max();
            if (($maxL - $minL) > $maxLenDiff) {
                continue;
            }
            $found++;
            $first = $group->first();
            $author = $first->author;
            $this->newLine();
            $this->warn('--- Группа #' . $found . ' | ' . ($author ? e_decode($author->name) : 'author #' . $first->author_id) . ' | нормализованное название: «' . $this->normalizeTitle((string) $first->title) . '» | длины: ' . $minL . '–' . $maxL . ' ---');
            foreach ($group as $poem) {
                $pub = $poem->published_at ? 'опубл.' : 'черновик';
                $this->line(sprintf(
                    '  id=%d | %s | len=%s | slug=%s | title=%s',
                    $poem->id,
                    $pub,
                    (string) $poem->body_length,
                    $poem->slug,
                    e_decode($poem->title)
                ));
            }
        }

        if ($found === 0) {
            $this->info('Дублей по правилам (один автор, одно нормализованное название, разница длины ≤ ' . $maxLenDiff . ') не найдено.');
        } else {
            $this->newLine();
            $this->info('Всего групп-дублей: ' . $found);
        }

        return self::SUCCESS;
    }

    private function normalizeTitle(string $title): string
    {
        $t = mb_strtolower(trim($title));
        // убираем конечные знаки препинания и пробелы (несколько проходов)
        for ($i = 0; $i < 5; $i++) {
            $next = preg_replace('/[\s\.\!\?…,;:·]+$/u', '', $t);
            if ($next === null || $next === $t) {
                break;
            }
            $t = $next;
        }

        return trim($t);
    }
}
