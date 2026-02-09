<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class CheckSeoViaWeb extends Command
{
    protected $signature = 'seo:check-via-web {--authors=3} {--poems=3} {--pages=2}';
    protected $description = 'Выборочная проверка SEO через внутренний HTTP-запрос: главная, авторы, стихи, страницы';

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/') ?: 'http://localhost';
        $kernel = App::make(\Illuminate\Contracts\Http\Kernel::class);

        $results = [];

        // Главная
        $this->info('Запрос: / (главная)');
        $html = $this->fetch($kernel, $baseUrl . '/');
        $results[] = [
            'url' => '/',
            'type' => 'Главная',
            'title' => $this->extractTitle($html),
            'meta_description' => $this->extractMetaDescription($html),
            'h1' => $this->extractH1($html),
            'under_h1' => $this->extractFirstPAfterH1($html),
        ];

        // Авторы
        $authors = Author::inRandomOrder()->limit((int) $this->option('authors'))->get();
        foreach ($authors as $author) {
            $url = $baseUrl . '/' . $author->slug . '/';
            $this->info('Запрос: /' . $author->slug . '/');
            $html = $this->fetch($kernel, $url);
            $results[] = [
                'url' => '/' . $author->slug . '/',
                'type' => 'Автор: ' . $author->name,
                'title' => $this->extractTitle($html),
                'meta_description' => $this->extractMetaDescription($html),
                'h1' => $this->extractH1($html),
                'under_h1' => $this->extractFirstPAfterH1($html),
            ];
        }

        // Стихи
        $poems = Poem::with('author')->whereNotNull('published_at')->inRandomOrder()->limit((int) $this->option('poems'))->get();
        foreach ($poems as $poem) {
            $url = $baseUrl . '/' . $poem->slug . '/';
            $this->info('Запрос: /' . $poem->slug . '/');
            $html = $this->fetch($kernel, $url);
            $results[] = [
                'url' => '/' . $poem->slug . '/',
                'type' => 'Стих: ' . mb_substr($poem->title, 0, 40) . (mb_strlen($poem->title) > 40 ? '…' : ''),
                'title' => $this->extractTitle($html),
                'meta_description' => $this->extractMetaDescription($html),
                'h1' => $this->extractH1($html),
                'under_h1' => $this->extractFirstPAfterH1($html),
            ];
        }

        // Страницы (не главная)
        $pages = Page::where('is_published', true)->where('is_home', false)->inRandomOrder()->limit((int) $this->option('pages'))->get();
        foreach ($pages as $page) {
            $url = $baseUrl . '/' . $page->slug . '/';
            $this->info('Запрос: /' . $page->slug . '/');
            $html = $this->fetch($kernel, $url);
            $results[] = [
                'url' => '/' . $page->slug . '/',
                'type' => 'Страница: ' . mb_substr($page->title, 0, 40),
                'title' => $this->extractTitle($html),
                'meta_description' => $this->extractMetaDescription($html),
                'h1' => $this->extractH1($html),
                'under_h1' => $this->extractFirstPAfterH1($html),
            ];
        }

        $this->newLine();
        $this->info('========== Собранные SEO-параметры ==========');
        foreach ($results as $i => $r) {
            $this->line('');
            $this->line('--- ' . ($i + 1) . '. ' . $r['type'] . ' ---');
            $this->line('URL: ' . $r['url']);
            $this->line('title: ' . $r['title']);
            $this->line('meta description: ' . mb_substr($r['meta_description'], 0, 120) . (mb_strlen($r['meta_description']) > 120 ? '…' : ''));
            $this->line('h1: ' . $r['h1']);
            $this->line('под h1: ' . $r['under_h1']);
        }
        $this->newLine();

        return self::SUCCESS;
    }

    private function fetch($kernel, string $url): string
    {
        $request = Request::create($url, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        return (string) $response->getContent();
    }

    private function extractTitle(string $html): string
    {
        return preg_match('/<title[^>]*>([^<]*)<\/title>/uis', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    }

    private function extractMetaDescription(string $html): string
    {
        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/ui', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if (preg_match('/<meta\s+content=["\']([^"\']*)["\']\s+name=["\']description["\']/ui', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return '';
    }

    private function extractH1(string $html): string
    {
        return preg_match('/<h1[^>]*>([\s\S]*?)<\/h1>/ui', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    }

    private function extractFirstPAfterH1(string $html): string
    {
        if (preg_match('/<h1[^>]*>[\s\S]*?<\/h1>\s*<p[^>]*>([\s\S]*?)<\/p>/ui', $html, $m)) {
            return trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return '';
    }
}
