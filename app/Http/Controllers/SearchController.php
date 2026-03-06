<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Poem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const LIMIT = 10;
    private const POEMS_PER_PAGE = 50;

    /**
     * Страница «Все результаты поиска» (по запросу q).
     * Запросы короче 3 символов не обрабатываются — редирект на пустой поиск.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $q = trim((string) $request->input('q', ''));
        if ($q !== '' && mb_strlen($q) < 3) {
            return redirect()->route('search.index');
        }
        $authors = collect();
        $poems = collect();

        if (mb_strlen($q) >= 3) {
            try {
                $authors = $this->searchAuthorsFulltext($q)->get(['id', 'name', 'slug']);
                $poems = $this->searchPoemsFulltext($q)->paginate(self::POEMS_PER_PAGE, ['id', 'slug', 'title', 'author_id'], 'page')->withQueryString();
            } catch (QueryException $e) {
                if ($this->isFulltextError($e)) {
                    $authors = $this->searchAuthorsLike($q)->get(['id', 'name', 'slug']);
                    $poems = $this->searchPoemsLike($q)->paginate(self::POEMS_PER_PAGE, ['id', 'slug', 'title', 'author_id'], 'page')->withQueryString();
                } else {
                    throw $e;
                }
            }
        }

        return view('search', [
            'q' => $q,
            'authors' => $authors,
            'poems' => $poems,
        ]);
    }

    /**
     * Подсказки для поиска: авторы и стихи по запросу.
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json(['authors' => [], 'poems' => []]);
        }
        try {
            $authors = $this->searchAuthorsFulltext($q)->limit(self::LIMIT)->get(['id', 'name', 'slug'])->map(fn ($a) => ['slug' => $a->slug, 'name' => $a->name]);
            $poems = $this->searchPoemsFulltext($q)->limit(self::LIMIT)->get(['id', 'slug', 'title', 'author_id'])->map(fn ($p) => [
                'slug' => $p->slug,
                'title' => $p->title,
                'author' => $p->author ? $p->author->name : '',
            ]);
        } catch (QueryException $e) {
            if ($this->isFulltextError($e)) {
                $authors = $this->searchAuthorsLike($q)->limit(self::LIMIT)->get(['id', 'name', 'slug'])->map(fn ($a) => ['slug' => $a->slug, 'name' => $a->name]);
                $poems = $this->searchPoemsLike($q)->limit(self::LIMIT)->get(['id', 'slug', 'title', 'author_id'])->map(fn ($p) => [
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'author' => $p->author ? $p->author->name : '',
                ]);
            } else {
                throw $e;
            }
        }
        return response()->json([
            'authors' => $authors,
            'poems' => $poems,
        ]);
    }

    private function isFulltextError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'FULLTEXT') || str_contains($e->getMessage(), '1191');
    }

    /**
     * Строка для FULLTEXT BOOLEAN MODE с префиксным поиском: каждое слово как "слово*".
     * Спецсимволы MySQL экранируются.
     */
    private function fulltextBooleanPrefix(string $q): string
    {
        $escaped = preg_replace('/([+\-*"()\\\\])/', '\\\\$1', $q);
        $tokens = array_filter(preg_split('/\s+/u', $escaped));
        $withStar = array_map(fn (string $t) => rtrim($t, '*') . '*', $tokens);

        return implode(' ', $withStar);
    }

    private function searchAuthorsFulltext(string $q): \Illuminate\Database\Eloquent\Builder
    {
        $against = $this->fulltextBooleanPrefix($q);

        return Author::whereFullText('name', $against, ['mode' => 'boolean'])
            ->orderByRaw('MATCH(name) AGAINST(? IN BOOLEAN MODE) DESC', [$against]);
    }

    private function searchAuthorsLike(string $q): \Illuminate\Database\Eloquent\Builder
    {
        $term = '%' . preg_replace('/\s+/', '%', $q) . '%';
        return Author::where('name', 'like', $term)->orderBy('name');
    }

    /**
     * Поиск стихов: FULLTEXT по title+body ИЛИ по токенам (каждое слово — в title, body или имени автора).
     * Сортировка по релевантности: сначала стихи, где совпали и автор и название/текст, затем по FULLTEXT.
     */
    private function searchPoemsFulltext(string $q): \Illuminate\Database\Eloquent\Builder
    {
        $against = $this->fulltextBooleanPrefix($q);
        $tokens = $this->searchTokens($q);

        $builder = Poem::with('author:id,name,slug')
            ->from('poems')
            ->leftJoin('authors', 'poems.author_id', '=', 'authors.id')
            ->whereNotNull('poems.published_at')
            ->where(function ($w) use ($against, $tokens) {
                $w->whereFullText(['poems.title', 'poems.body'], $against, ['mode' => 'boolean']);
                if ($tokens !== []) {
                    $w->orWhere(function ($w2) use ($tokens) {
                        foreach ($tokens as $token) {
                            $term = $this->likeTerm($token);
                            $w2->where(function ($w3) use ($term) {
                                $w3->where('poems.title', 'like', $term)
                                    ->orWhere('poems.body', 'like', $term)
                                    ->orWhere('authors.name', 'like', $term);
                            });
                        }
                    });
                }
            })
            ->select('poems.id', 'poems.slug', 'poems.title', 'poems.author_id');

        $builder = $this->orderPoemsByRelevance($builder, $q, $tokens, true, $against);
        return $builder;
    }

    private function searchPoemsLike(string $q): \Illuminate\Database\Eloquent\Builder
    {
        $wholeTerm = '%' . preg_replace('/\s+/', '%', $q) . '%';
        $tokens = $this->searchTokens($q);

        $builder = Poem::with('author:id,name,slug')
            ->from('poems')
            ->leftJoin('authors', 'poems.author_id', '=', 'authors.id')
            ->whereNotNull('poems.published_at')
            ->where(function ($w) use ($wholeTerm, $tokens) {
                $w->where('poems.title', 'like', $wholeTerm)->orWhere('poems.body', 'like', $wholeTerm);
                if ($tokens !== []) {
                    $w->orWhere(function ($w2) use ($tokens) {
                        foreach ($tokens as $token) {
                            $term = $this->likeTerm($token);
                            $w2->where(function ($w3) use ($term) {
                                $w3->where('poems.title', 'like', $term)
                                    ->orWhere('poems.body', 'like', $term)
                                    ->orWhere('authors.name', 'like', $term);
                            });
                        }
                    });
                }
            })
            ->select('poems.id', 'poems.slug', 'poems.title', 'poems.author_id');

        return $this->orderPoemsByRelevance($builder, $q, $tokens, false, null);
    }

    /**
     * Сортировка: 0 — совпали и автор и название/текст, 1 — остальные. Затем по FULLTEXT или title.
     */
    private function orderPoemsByRelevance(
        \Illuminate\Database\Eloquent\Builder $builder,
        string $q,
        array $tokens,
        bool $useFulltext,
        ?string $against
    ): \Illuminate\Database\Eloquent\Builder {
        $conditions = [];
        $bindings = [];
        foreach ($tokens as $i => $t1) {
            foreach ($tokens as $j => $t2) {
                if ($i >= $j) {
                    continue;
                }
                $term1 = $this->likeTerm($t1);
                $term2 = $this->likeTerm($t2);
                $conditions[] = '(authors.name LIKE ? AND (poems.title LIKE ? OR poems.body LIKE ?)) OR (authors.name LIKE ? AND (poems.title LIKE ? OR poems.body LIKE ?))';
                $bindings = array_merge($bindings, [$term1, $term2, $term2, $term2, $term1, $term1]);
            }
        }

        if ($conditions !== []) {
            $caseSql = '(CASE WHEN ' . implode(' OR ', $conditions) . ' THEN 0 ELSE 1 END)';
            $builder->orderByRaw($caseSql, $bindings);
        }

        if ($useFulltext && $against !== null) {
            $builder->orderByRaw('MATCH(poems.title, poems.body) AGAINST(? IN BOOLEAN MODE) DESC', [$against]);
        }
        $builder->orderBy('poems.title');
        return $builder;
    }

    private function searchTokens(string $q): array
    {
        return array_values(array_filter(preg_split('/\s+/u', trim($q)), fn ($t) => mb_strlen($t) >= 2));
    }

    private function likeTerm(string $token): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $token);
        return '%' . $escaped . '%';
    }
}
