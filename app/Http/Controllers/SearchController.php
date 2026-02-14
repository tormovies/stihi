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
                $poems = $this->searchPoemsFulltext($q)->paginate(self::POEMS_PER_PAGE, ['id', 'slug', 'title', 'author_id'], 'page');
            } catch (QueryException $e) {
                if ($this->isFulltextError($e)) {
                    $authors = $this->searchAuthorsLike($q)->get(['id', 'name', 'slug']);
                    $poems = $this->searchPoemsLike($q)->paginate(self::POEMS_PER_PAGE, ['id', 'slug', 'title', 'author_id'], 'page');
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

    private function searchPoemsFulltext(string $q): \Illuminate\Database\Eloquent\Builder
    {
        $against = $this->fulltextBooleanPrefix($q);

        return Poem::with('author:id,name,slug')
            ->whereNotNull('published_at')
            ->whereFullText(['title', 'body'], $against, ['mode' => 'boolean'])
            ->orderByRaw('MATCH(title, body) AGAINST(? IN BOOLEAN MODE) DESC', [$against]);
    }

    private function searchPoemsLike(string $q): \Illuminate\Database\Eloquent\Builder
    {
        $term = '%' . preg_replace('/\s+/', '%', $q) . '%';
        return Poem::with('author:id,name,slug')
            ->whereNotNull('published_at')
            ->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('body', 'like', $term))
            ->orderBy('title');
    }
}
