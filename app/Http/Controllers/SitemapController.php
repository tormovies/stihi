<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SitemapController extends Controller
{
    public const CACHE_ENTRIES_KEY = 'sitemap_entries';
    public const CACHE_UPDATED_AT = 'sitemap_updated_at';
    public const CHUNK_SIZE = 1000;

    public function index(Request $request): Response
    {
        try {
            return $this->buildSitemapResponse($request);
        } catch (\Throwable $e) {
            Log::error('Sitemap generation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // Для /sitemap.xml отдаём XML, а не HTML-страницу 500
            $message = config('app.debug') ? $e->getMessage() : 'Sitemap temporarily unavailable.';
            $xml = '<?xml version="1.0" encoding="UTF-8"?><error><message>' . htmlspecialchars($message, ENT_XML1, 'UTF-8') . '</message></error>';
            return response($xml, 503, [
                'Content-Type' => 'application/xml',
                'Charset' => 'UTF-8',
                'Retry-After' => '300',
            ]);
        }
    }

    private function buildSitemapResponse(Request $request): Response
    {
        $forceRefresh = $request->query('refresh') === '1';
        $page = $request->query('page');

        if ($forceRefresh || !Cache::has(self::CACHE_ENTRIES_KEY . '_count')) {
            self::regenerate();
        }

        $totalChunks = (int) Cache::get(self::CACHE_ENTRIES_KEY . '_count', 1);

        if ($page !== null && $page !== '') {
            $pageNum = (int) $page;
            if ($pageNum < 1 || $pageNum > $totalChunks) {
                abort(404);
            }
            $chunk = Cache::get(self::CACHE_ENTRIES_KEY . '_' . $pageNum, []);
            $xml = view('sitemap-urlset', ['urls' => $chunk])->render();
        } else {
            $baseUrl = rtrim(config('app.url'), '/') . '/sitemap.xml';
            $lastmod = Cache::get(self::CACHE_UPDATED_AT);
            $lastmodFormatted = $this->formatLastmod($lastmod);
            $sitemapUrls = [];
            for ($i = 1; $i <= $totalChunks; $i++) {
                $sitemapUrls[] = [
                    'loc' => $baseUrl . '?page=' . $i,
                    'lastmod' => $lastmodFormatted,
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

    private function formatLastmod(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return (new \DateTime($value))->format('c');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Regenerate and store sitemap entries (e.g. from admin). Uses chunking to avoid memory limit. */
    public static function regenerate(): void
    {
        $entries = [];

        $entries[] = [
            'loc' => url('/'),
            'lastmod' => null,
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ];

        foreach (Author::orderBy('name')->select('slug')->cursor() as $author) {
            $entries[] = [
                'loc' => url($author->slug),
                'lastmod' => null,
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach (Poem::whereNotNull('published_at')->orderBy('updated_at', 'desc')->select('slug', 'updated_at', 'published_at')->cursor() as $poem) {
            $entries[] = [
                'loc' => url($poem->slug),
                'lastmod' => $poem->updated_at?->toW3cString() ?? $poem->published_at?->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        foreach (Page::where('is_published', true)->where('is_home', false)->select('slug')->cursor() as $page) {
            $entries[] = [
                'loc' => url($page->slug),
                'lastmod' => null,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        $chunks = array_chunk($entries, self::CHUNK_SIZE);
        $ttl = now()->addDays(7);
        Cache::put(self::CACHE_ENTRIES_KEY . '_count', count($chunks), $ttl);
        foreach ($chunks as $i => $chunk) {
            Cache::put(self::CACHE_ENTRIES_KEY . '_' . ($i + 1), $chunk, $ttl);
        }
        Cache::put(self::CACHE_UPDATED_AT, now()->toIso8601String(), $ttl);
    }

    /** Clear sitemap cache (after regenerate, next request will rebuild). */
    public static function clearCache(): void
    {
        $count = (int) Cache::get(self::CACHE_ENTRIES_KEY . '_count', 0);
        Cache::forget(self::CACHE_ENTRIES_KEY . '_count');
        for ($i = 1; $i <= $count; $i++) {
            Cache::forget(self::CACHE_ENTRIES_KEY . '_' . $i);
        }
        Cache::forget(self::CACHE_UPDATED_AT);
    }
}
