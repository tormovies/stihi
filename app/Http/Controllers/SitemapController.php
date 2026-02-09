<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap_xml';
    public const CACHE_UPDATED_AT = 'sitemap_updated_at';

    public function index(Request $request): Response
    {
        $forceRefresh = $request->query('refresh') === '1';
        if (!$forceRefresh && Cache::has(self::CACHE_KEY)) {
            return response(Cache::get(self::CACHE_KEY), 200, [
                'Content-Type' => 'application/xml',
                'Charset' => 'UTF-8',
            ]);
        }

        $authors = Author::orderBy('name')->get();
        $poems = Poem::whereNotNull('published_at')->orderBy('updated_at', 'desc')->get();
        $pages = Page::where('is_published', true)->where('is_home', false)->get();

        self::regenerate($authors, $poems, $pages);

        return response(Cache::get(self::CACHE_KEY), 200, [
            'Content-Type' => 'application/xml',
            'Charset' => 'UTF-8',
        ]);
    }

    public static function getLastUpdatedAt(): ?string
    {
        return Cache::get(self::CACHE_UPDATED_AT);
    }

    /** Regenerate and store sitemap (e.g. from admin). */
    public static function regenerate($authors = null, $poems = null, $pages = null): void
    {
        if ($authors === null) {
            $authors = Author::orderBy('name')->get();
            $poems = Poem::whereNotNull('published_at')->orderBy('updated_at', 'desc')->get();
            $pages = Page::where('is_published', true)->where('is_home', false)->get();
        }
        $xml = view('sitemap', [
            'authors' => $authors,
            'poems' => $poems,
            'pages' => $pages,
        ])->render();
        Cache::put(self::CACHE_KEY, $xml, now()->addDays(7));
        Cache::put(self::CACHE_UPDATED_AT, now()->toIso8601String(), now()->addDays(7));
    }

    /** Clear sitemap cache (after regenerate, next request will rebuild). */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_UPDATED_AT);
    }
}
