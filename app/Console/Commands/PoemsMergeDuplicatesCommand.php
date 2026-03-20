<?php

namespace App\Console\Commands;

use App\Models\Poem;
use App\Models\PoemAnalysis;
use App\Models\UrlRedirect;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Склейка дублей с slug вида «stem», «stem-2», «stem-3» у одного автора.
 * Канон: запись с минимальным числовым суффиксом (0 = сам stem без -N).
 * Лайки суммируются на канон; анализ и теги дубля удаляются каскадом; опционально — 301 в url_redirects.
 */
class PoemsMergeDuplicatesCommand extends Command
{
    protected $signature = 'poems:merge-duplicates
                            {--apply : Выполнить слияние (без флага только план)}
                            {--redirects : При --apply создать 301 из slug дубля → канон (таблица url_redirects)}
                            {--author= : Ограничить author_id}
                            {--include-unpublished : Учитывать неопубликованные стихи}';

    protected $description = 'Склейка дублей по slug (stem / stem-2 / stem-3 у одного автора): сумма лайков, удаление дублей, опционально 301';

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $apply = (bool) $this->option('apply');
        $withRedirects = (bool) $this->option('redirects');
        $authorId = $this->option('author');
        $authorFilter = $authorId !== null && $authorId !== '' ? (int) $authorId : null;

        $q = Poem::query()
            ->withExists('analysis');
        if (!$this->option('include-unpublished')) {
            $q->whereNotNull('published_at');
        }
        if ($authorFilter !== null) {
            $q->where('author_id', $authorFilter);
        }
        $poems = $q->orderBy('author_id')->orderBy('id')->get([
            'id', 'author_id', 'title', 'slug', 'body_length', 'published_at', 'likes',
        ]);

        /** @var Collection<string, Collection<int, Poem>> $groups */
        $groups = $poems->groupBy(function (Poem $p) {
            [$stem] = $this->parseSlugSuffix($p->slug);

            return $p->author_id . "\t" . $stem;
        });

        $plans = [];
        foreach ($groups as $key => $group) {
            if ($group->count() < 2) {
                continue;
            }
            $sorted = $group->map(function (Poem $p) {
                [$stem, $suffix] = $this->parseSlugSuffix($p->slug);

                return ['poem' => $p, 'stem' => $stem, 'suffix' => $suffix];
            })->values()->sort(function (array $a, array $b): int {
                if ($a['suffix'] !== $b['suffix']) {
                    return $a['suffix'] <=> $b['suffix'];
                }

                return $a['poem']->id <=> $b['poem']->id;
            })->values();

            $canonRow = $sorted->first();
            $canon = $canonRow['poem'];
            $dupes = $sorted->skip(1)->pluck('poem');

            $plans[] = [
                'stem' => $canonRow['stem'],
                'canon' => $canon,
                'dupes' => $dupes,
            ];
        }

        if ($plans === []) {
            $this->info('Групп с несколькими slug по одному stem и автору не найдено.');

            return self::SUCCESS;
        }

        $this->warn('Найдено групп: ' . count($plans) . ($apply ? '' : ' (режим плана, без --apply изменений не будет)'));
        foreach ($plans as $i => $plan) {
            $this->newLine();
            $this->line('— Группа ' . ($i + 1) . ', stem=<comment>' . $plan['stem'] . '</comment>, author_id=' . $plan['canon']->author_id);
            $this->line('  канон: id=' . $plan['canon']->id . ' slug=' . $plan['canon']->slug . ' likes=' . $plan['canon']->likes . ' title=' . $this->oneLineTitle($plan['canon']->title));
            foreach ($plan['dupes'] as $d) {
                $hasA = (bool) $d->analysis_exists;
                $this->line('  дубль: id=' . $d->id . ' slug=' . $d->slug . ' likes=' . $d->likes . ' analiz=' . ($hasA ? 'да' : 'нет') . ' | ' . $this->oneLineTitle($d->title));
            }
        }

        if (!$apply) {
            $this->newLine();
            $this->info('Чтобы выполнить: php artisan poems:merge-duplicates --apply');
            $this->info('С автоматическими 301: добавьте --redirects');

            return self::SUCCESS;
        }

        if ($withRedirects) {
            $this->newLine();
            $this->info('Создание/обновление 301 в url_redirects включено.');
        }

        $merged = 0;
        DB::transaction(function () use ($plans, $withRedirects, &$merged) {
            foreach ($plans as $plan) {
                $canon = $plan['canon'];
                $canonHasAnalysis = PoemAnalysis::where('poem_id', $canon->id)->exists();

                foreach ($plan['dupes'] as $dup) {
                    $likesToAdd = (int) $dup->likes;

                    if ($withRedirects) {
                        $this->ensureRedirect($dup->slug, $canon->slug);
                        $dupHasAnalysis = (bool) $dup->analysis_exists;
                        if ($dupHasAnalysis && $canonHasAnalysis) {
                            $this->ensureRedirect($dup->slug . '/analiz', $canon->slug . '/analiz');
                        }
                    }

                    $canon->increment('likes', $likesToAdd);
                    $dup->delete();
                    $merged++;
                }
            }
        });

        UrlRedirect::forgetMapCache();

        $this->newLine();
        $this->info("Готово. Удалено дублей (записей poem): {$merged}. Лайки суммированы на канон. Анализы/теги дублей удалены каскадом.");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: int} [stem, suffix] suffix 0 если slug без хвоста -число
     */
    private function parseSlugSuffix(string $slug): array
    {
        if (preg_match('/^(.+)-(\d+)$/', $slug, $m) === 1 && $m[1] !== '') {
            return [$m[1], (int) $m[2]];
        }

        return [$slug, 0];
    }

    private function oneLineTitle(string $title): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);

        return mb_strlen($t) > 70 ? mb_substr($t, 0, 67) . '…' : $t;
    }

    private function ensureRedirect(string $fromPath, string $toPath): void
    {
        $from = UrlRedirect::normalizeForStorage($fromPath);
        $to = UrlRedirect::normalizeForStorage($toPath);
        if ($from === $to) {
            return;
        }
        UrlRedirect::query()->updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to]
        );
    }
}
