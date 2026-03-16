<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * Общая страница — список всех тегов (/tegi).
     */
    public function index(): View
    {
        $tags = Tag::withCount('poems')->orderBy('sort_order')->orderBy('name')->get();
        return view('tags-index', ['tags' => $tags]);
    }

    /**
     * Страница одного тега — стихи с этим тегом (/tegi/{slug}).
     */
    public function show(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $poems = $tag->poems()
            ->with('author:id,name,slug')
            ->whereNotNull('poems.published_at')
            ->orderBy('poems.title')
            ->paginate(50);

        return view('tag', ['tag' => $tag, 'poems' => $poems]);
    }
}
