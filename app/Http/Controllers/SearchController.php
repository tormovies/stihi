<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private const LIMIT = 10;

    /**
     * Подсказки для поиска: авторы и стихи по запросу.
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 3) {
            return response()->json(['authors' => [], 'poems' => []]);
        }
        $term = '%' . preg_replace('/\s+/', '%', $q) . '%';
        $authors = Author::where('name', 'like', $term)
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($a) => ['slug' => $a->slug, 'name' => $a->name]);
        $poems = Poem::with('author:id,name,slug')
            ->whereNotNull('published_at')
            ->where(function ($w) use ($term) {
                $w->where('title', 'like', $term)->orWhere('body', 'like', $term);
            })
            ->orderBy('title')
            ->limit(self::LIMIT)
            ->get(['id', 'slug', 'title', 'author_id'])
            ->map(fn ($p) => [
                'slug' => $p->slug,
                'title' => $p->title,
                'author' => $p->author ? $p->author->name : '',
            ]);
        return response()->json([
            'authors' => $authors,
            'poems' => $poems,
        ]);
    }
}
