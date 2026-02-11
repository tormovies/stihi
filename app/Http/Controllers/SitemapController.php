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
    public const CACHE_ENTRIES_KEY = 'sitemap_entries';
    public const CACHE_UPDATED_AT = 'sitemap_updated_at';
    public const CHUNK_SIZE = 1000;

    public function index(Request $request): Response
    {
        $forceRefresh = $request->query('refresh') === '1';
        $page = $request->query('page');

        if ($forceRefresh || !Cache::has(self::CACHE_ENTRIES_KEY)) {
            $authors = Author::orderBy('name')->get();
            $poems = Poem::whereNotNull('published_at')->orderBy('updated_at', 'desc')->get();
            $pages = Page::where('is_published', true)->where('is_home', false)->get();
            self::regenerate($authors, $poems, $pages);
        }

        $entries = Cache::get(self::CACHE_ENTRIES_KEY);
        $totalChunks = (int) ceil(count($entries) / self::CHUNK_SIZE);

        if ($page !== null && $page !== '') {
            $pageNum = (int) $page;
            if ($pageNum < 1 || $pageNum > $totalChunks) {
                abort(404);
            }
            $offset = ($pageNum - 1) * self::CHUNK_SIZE;
            $chunk = array_slice($entries, $offset, self::CHUNK_SIZE);
            $xml = view('sitemap-urlset', ['urls' => $chunk])->render();
        } else {
            $baseUrl = rtrim(config('app.url'), '/') . '/sitemap.xml';
            $lastmod = Cache::get(self::CACHE_UPDATED_AT);
            $sitemapUrls = [];
            for ($i = 1; $i <= $totalChunks; $i++) {
                $sitemapUrls[] = [
                    'loc' => $baseUrl . '?page=' . $i,
                    'lastmod' => $lastmod ? (new \DateTime($lastmod))->format('c') : null,
                ];
            }
            $xml = view('sitemap-index', ['sitemapUrls' => $sitemapUrls])->render();
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Charset' => 'UTF-8',
        ]);
    }

    public static function getLastUpdatedAt(): ?string
    {
        return Cache::get(self::CACHE_UPDATED_AT);
    }

    /** Regenerate and store sitemap entries (e.g. from admin). */
    public static function regenerate($authors = null, $poems = null, $pages = null): void
    {
        if ($authors === null) {
            $authors = Author::orderBy('name')->get();
            $poems = Poem::whereNotNull('published_at')->orderBy('updated_at', 'desc')->get();
            $pages = Page::where('is_published', true)->where('is_home', false)->get();
        }

        $entries = [];

        $entries[] = [
            'loc' => url('/'),
            'lastmod' => null,
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        foreach ($authors as $author) {
            $entries[] = [
                'loc' => url($author->slug),
                'lastmod' => null,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach ($poems as $poem) {
            $entries[] = [
                'loc' => url($poem->slug),
                'lastmod' => $poem->updated_at?->toW3cString() ?? $poem->published_at?->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        foreach ($pages as $page) {
            $entries[] = [
                'loc' => url($page->slug),
                'lastmod' => null,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        Cache::put(self::CACHE_ENTRIES_KEY, $entries, now()->addDays(7));
        Cache::put(self::CACHE_UPDATED_AT, now()->toIso8601String(), now()->addDays(7));
    }

    /** Clear sitemap cache (after regenerate, next request will rebuild). */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_ENTRIES_KEY);
        Cache::forget(self::CACHE_UPDATED_AT);
    }
}
