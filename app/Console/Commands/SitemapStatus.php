<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SitemapStatus extends Command
{
    protected $signature = 'sitemap:status {--test-render : Try to render index and first chunk to catch errors}';
    protected $description = 'Check sitemap cache state (for debugging admin vs web mismatch)';

    public function handle(): int
    {
        $countKey = SitemapController::CACHE_ENTRIES_KEY . '_count';
        $updatedKey = SitemapController::CACHE_UPDATED_AT;

        $count = Cache::get($countKey);
        $chunk1 = Cache::get(SitemapController::CACHE_ENTRIES_KEY . '_1');
        $updatedAt = Cache::get($updatedKey);

        $this->line('Cache store: ' . config('cache.default'));
        $this->line('  ' . $countKey . ': ' . ($count === null ? 'null' : (is_int($count) ? $count : gettype($count) . '=' . json_encode($count))));
        $this->line('  chunk _1: ' . ($chunk1 === null ? 'null' : (is_array($chunk1) ? 'array(' . count($chunk1) . ' urls)' : gettype($chunk1))));
        $this->line('  ' . $updatedKey . ': ' . ($updatedAt === null ? 'null' : $updatedAt));

        if ($count === null || $chunk1 === null || !is_array($chunk1) || $chunk1 === []) {
            $this->warn('Cache incomplete or empty. Run "Update sitemap" in admin, then check again.');
            return self::FAILURE;
        }

        if (!$this->option('test-render')) {
            $this->info('Cache looks present. Run with --test-render to try rendering (catches view errors).');
            return self::SUCCESS;
        }

        $this->line('');
        $this->line('Testing render...');

        try {
            $xml = view('sitemap-index', [
                'sitemapUrls' => [
                    ['loc' => rtrim(config('app.url'), '/') . '/sitemap.xml?page=1', 'lastmod' => $updatedAt ? (new \DateTime($updatedAt))->format('c') : null],
                ],
            ])->render();
            $this->info('Index view: OK (' . strlen($xml) . ' bytes)');
        } catch (\Throwable $e) {
            Log::error('Sitemap status test-render (index) failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->error('Index view failed: ' . $e->getMessage());
            $this->line('  at ' . $e->getFile() . ':' . $e->getLine());
            return self::FAILURE;
        }

        try {
            $xml = view('sitemap-urlset', ['urls' => $chunk1])->render();
            $this->info('Urlset view (chunk 1): OK (' . strlen($xml) . ' bytes)');
        } catch (\Throwable $e) {
            Log::error('Sitemap status test-render (urlset) failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->error('Urlset view failed: ' . $e->getMessage());
            $this->line('  at ' . $e->getFile() . ':' . $e->getLine());
            return self::FAILURE;
        }

        $this->info('All OK. If web still fails, compare: run this command via CLI (you) and via web — e.g. add a temporary route that runs this and outputs to response.');
        return self::SUCCESS;
    }
}
