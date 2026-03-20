<?php

namespace App\Console\Commands;

use App\Models\Poem;
use App\Models\PoemAnalysis;
use App\Models\UrlRedirect;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Фаза 1: как poems:find-duplicates (автор + нормализованное название + разброс длины).
 * Фаза 2: как poems:merge-duplicates — один автор и slug «stem», «stem-2», «stem-3» (без совпадения заголовков).
 * Канон везде: минимальный числовой суффикс в slug, затем меньший id.
 * При --apply: сначала фаза 1, затем фаза 2 по актуальной БД; 301 в url_redirects.
 */
class PoemsMergeTitleDuplicatesCommand extends Command
{
    protected $signature = 'poems:merge-title-duplicates
                            {--length-diff=40 : Макс. разница body_length в группе (фаза 1)}
                            {--apply : Выполнить слияние и 301 (без флага только план)}
                            {--include-unpublished : Учитывать неопубликованные стихи}';

    protected $description = 'Слияние дублей: фаза 1 по заголовку+длине, фаза 2 по slug (stem/-2/-3) + 301';

    public function handle(): int
    {
        @ini_set('memory_limit', '512M');

        $apply = (bool) $this->option('apply');
        $maxLenDiff = max(0, (int) $this->option('length-diff'));

        $titlePlans = $this->buildTitlePlans($maxLenDiff);
        $poemsForStem = $this->loadPoemsForMerge();
        $stemPlans = $this->buildStemPlans($poemsForStem);

        if ($titlePlans === [] && $stemPlans === []) {
            $this->info('Групп не найдено (фаза 1: заголовок+длина ≤ ' . $maxLenDiff . '; фаза 2: slug stem).');

            return self::SUCCESS;
        }

        $this->warn(
            'Фаза 1 (заголовок): ' . count($titlePlans) . ' гр. | Фаза 2 (slug stem): ' . count($stemPlans) . ' гр.'
            . ($apply ? '' : ' — только план; слияние: --apply')
        );

        if ($titlePlans !== []) {
            $this->newLine();
            $this->line('<info>=== Фаза 1: автор + нормализованное название + разброс длины ≤ ' . $maxLenDiff . ' ===</info>');
            foreach ($titlePlans as $i => $plan) {
                $g = collect([$plan['canon'], ...$plan['dupes']->all()]);
                $lengths = $g->pluck('body_length')->map(fn ($l) => (int) $l);
                $this->newLine();
                $author = $plan['canon']->author;
                $this->line('— Гр. ' . ($i + 1) . ' | ' . ($author ? e_decode($author->name) : 'author #' . $plan['canon']->author_id));
                $this->line('  «' . $plan['norm_title'] . '» | длины: ' . $lengths->min() . '–' . $lengths->max());
                $this->line('  канон: id=' . $plan['canon']->id . ' slug=' . $plan['canon']->slug . ' likes=' . $plan['canon']->likes);
                foreach ($plan['dupes'] as $d) {
                    $this->line('  дубль: id=' . $d->id . ' slug=' . $d->slug . ' likes=' . $d->likes . ' analiz=' . ($d->analysis_exists ? 'да' : 'нет'));
                }
            }
        }

        if ($stemPlans !== []) {
            $this->newLine();
            $this->line('<info>=== Фаза 2: автор + slug stem (…, …-2, …-3) — ловит дубли с разными заголовками ===</info>');
            foreach ($stemPlans as $i => $plan) {
                $this->newLine();
                $author = $plan['canon']->author;
                $this->line('— Гр. ' . ($i + 1) . ' stem=<comment>' . $plan['stem'] . '</comment> | ' . ($author ? e_decode($author->name) : 'author #' . $plan['canon']->author_id));
                $this->line('  канон: id=' . $plan['canon']->id . ' slug=' . $plan['canon']->slug . ' likes=' . $plan['canon']->likes . ' | ' . $this->oneLineTitle($plan['canon']->title));
                foreach ($plan['dupes'] as $d) {
                    $this->line('  дубль: id=' . $d->id . ' slug=' . $d->slug . ' likes=' . $d->likes . ' | ' . $this->oneLineTitle($d->title));
                }
            }
        }

        if (!$apply) {
            $this->newLine();
            $this->comment('При --apply сначала выполняется фаза 1, затем фаза 2 по оставшимся стихам (пересечения в плане возможны — после фазы 1 часть групп фазы 2 исчезнет).');
            $this->info('Слияние: php artisan poems:merge-title-duplicates --length-diff=' . $maxLenDiff . ' --apply');

            return self::SUCCESS;
        }

        $merged1 = 0;
        DB::transaction(function () use ($titlePlans, &$merged1) {
            foreach ($titlePlans as $plan) {
                $canon = $plan['canon'];
                $canonHasAnalysis = PoemAnalysis::where('poem_id', $canon->id)->exists();

                foreach ($plan['dupes'] as $dup) {
                    $likesToAdd = (int) $dup->likes;
                    $this->ensureRedirect($dup->slug, $canon->slug);
                    $dupHasAnalysis = (bool) $dup->analysis_exists;
                    if ($dupHasAnalysis && $canonHasAnalysis) {
                        $this->ensureRedirect($dup->slug . '/analiz', $canon->slug . '/analiz');
                    }
                    $canon->increment('likes', $likesToAdd);
                    $dup->delete();
                    $merged1++;
                }
            }
        });

        $poemsAfter = $this->loadPoemsForMerge();
        $stemPlansAfter = $this->buildStemPlans($poemsAfter);
        $merged2 = 0;
        DB::transaction(function () use ($stemPlansAfter, &$merged2) {
            foreach ($stemPlansAfter as $plan) {
                $canon = $plan['canon'];
                $canonHasAnalysis = PoemAnalysis::where('poem_id', $canon->id)->exists();

                foreach ($plan['dupes'] as $dup) {
                    $likesToAdd = (int) $dup->likes;
                    $this->ensureRedirect($dup->slug, $canon->slug);
                    $dupHasAnalysis = (bool) $dup->analysis_exists;
                    if ($dupHasAnalysis && $canonHasAnalysis) {
                        $this->ensureRedirect($dup->slug . '/analiz', $canon->slug . '/analiz');
                    }
                    $canon->increment('likes', $likesToAdd);
                    $dup->delete();
                    $merged2++;
                }
            }
        });

        UrlRedirect::forgetMapCache();

        $this->newLine();
        $this->info("Готово. Фаза 1: удалено дублей {$merged1}. Фаза 2: удалено дублей {$merged2}. Лайки на канон, 301 в url_redirects.");

        return self::SUCCESS;
    }

    private function loadPoemsForMerge(): Collection
    {
        // Не подгружаем analysis (там длинный текст) — только флаг exists, иначе OOM на проде
        $q = Poem::query()
            ->with(['author:id,name,slug'])
            ->withExists('analysis');
        if (!$this->option('include-unpublished')) {
            $q->whereNotNull('published_at');
        }

        return $q->orderBy('author_id')->orderBy('id')->get([
            'id', 'author_id', 'title', 'slug', 'body_length', 'published_at', 'likes',
        ]);
    }

    /**
     * @return list<array{norm_title: string, canon: Poem, dupes: Collection<int, Poem>}>
     */
    private function buildTitlePlans(int $maxLenDiff): array
    {
        $poems = $this->loadPoemsForMerge()->sortBy([
            ['author_id', 'asc'],
            ['title', 'asc'],
        ])->values();

        /** @var Collection<string, Collection<int, Poem>> $byTitle */
        $byTitle = $poems->groupBy(function (Poem $p) {
            return $p->author_id . "\t" . $this->normalizeTitle((string) $p->title);
        });

        $plans = [];
        foreach ($byTitle as $group) {
            if ($group->count() < 2) {
                continue;
            }
            $lengths = $group->pluck('body_length')->map(fn ($l) => (int) $l);
            if (($lengths->max() - $lengths->min()) > $maxLenDiff) {
                continue;
            }

            $sorted = $group->map(function (Poem $p) {
                [, $suffix] = $this->parseSlugSuffix($p->slug);

                return ['poem' => $p, 'suffix' => $suffix];
            })->values()->sort(function (array $a, array $b): int {
                if ($a['suffix'] !== $b['suffix']) {
                    return $a['suffix'] <=> $b['suffix'];
                }

                return $a['poem']->id <=> $b['poem']->id;
            })->values();

            $canon = $sorted->first()['poem'];
            $dupes = $sorted->skip(1)->pluck('poem');

            $plans[] = [
                'norm_title' => $this->normalizeTitle((string) $canon->title),
                'canon' => $canon,
                'dupes' => $dupes,
            ];
        }

        return $plans;
    }

    /**
     * @return list<array{stem: string, canon: Poem, dupes: Collection<int, Poem>}>
     */
    private function buildStemPlans(Collection $poems): array
    {
        /** @var Collection<string, Collection<int, Poem>> $groups */
        $groups = $poems->groupBy(function (Poem $p) {
            [$stem] = $this->parseSlugSuffix($p->slug);

            return $p->author_id . "\t" . $stem;
        });

        $plans = [];
        foreach ($groups as $group) {
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
            $plans[] = [
                'stem' => $canonRow['stem'],
                'canon' => $canonRow['poem'],
                'dupes' => $sorted->skip(1)->pluck('poem'),
            ];
        }

        return $plans;
    }

    private function normalizeTitle(string $title): string
    {
        $t = mb_strtolower(trim($title));
        for ($i = 0; $i < 5; $i++) {
            $next = preg_replace('/[\s\.\!\?…,;:·]+$/u', '', $t);
            if ($next === null || $next === $t) {
                break;
            }
            $t = $next;
        }

        return trim($t);
    }

    /**
     * @return array{0: string, 1: int}
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

        return mb_strlen($t) > 60 ? mb_substr($t, 0, 57) . '…' : $t;
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
