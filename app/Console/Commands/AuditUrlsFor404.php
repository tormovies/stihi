<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use App\Models\SeoPage;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AuditUrlsFor404 extends Command
{
    protected $signature = 'audit:urls-404';
    protected $description = 'Проверить все URL авторов, стихов, страниц и SEO-страниц; собрать данные по 404';

    /** Маршрут принимает только slug из [a-z0-9\-]+ */
    private const SLUG_REGEX = '/^[a-z0-9\-]+$/';

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/') ?: 'http://localhost';
        $kernel = App::make(\Illuminate\Contracts\Http\Kernel::class);

        $notFound = [];
        $total = 0;

        // 1) Сначала собираем «невалидные» slug (не проходят маршрут) — без запроса
        $this->info('Проверка slug на соответствие маршруту [a-z0-9\-]+...');

        foreach (Author::orderBy('id')->get() as $author) {
            $total++;
            if (!$this->slugMatchesRoute($author->slug)) {
                $notFound[] = ['type' => 'Автор', 'url' => '/' . $author->slug . '/', 'slug_or_id' => $author->slug, 'name' => $author->name, 'id' => $author->id, 'reason' => 'slug не совпадает с маршрутом'];
            }
        }

        $poems = Poem::whereNotNull('published_at')->orderBy('id')->get();
        foreach ($poems as $poem) {
            $total++;
            if (!$this->slugMatchesRoute($poem->slug)) {
                $notFound[] = ['type' => 'Стих', 'url' => '/' . $poem->slug . '/', 'slug_or_id' => $poem->slug, 'name' => $poem->title, 'id' => $poem->id, 'reason' => 'slug не совпадает с маршрутом'];
            }
        }

        foreach (Page::where('is_published', true)->where('is_home', false)->orderBy('id')->get() as $page) {
            $total++;
            if (!$this->slugMatchesRoute($page->slug)) {
                $notFound[] = ['type' => 'Страница', 'url' => '/' . $page->slug . '/', 'slug_or_id' => $page->slug, 'name' => $page->title, 'id' => $page->id, 'reason' => 'slug не совпадает с маршрутом'];
            }
        }

        foreach (SeoPage::orderBy('id')->get() as $sp) {
            $total++;
            if (!$this->slugMatchesRoute($sp->path)) {
                $notFound[] = ['type' => 'SEO-страница', 'url' => '/' . $sp->path . '/', 'slug_or_id' => $sp->path, 'name' => $sp->meta_title ?: $sp->path, 'id' => $sp->id, 'reason' => 'path не совпадает с маршрутом'];
            }
        }

        // 2) Запросы только для валидных slug (главная + те, кто прошёл regex)
        $this->info('Проверка ответа сервера (главная, авторы, страницы, SEO-страницы)...');

        $url = $baseUrl . '/';
        $total++;
        if ($this->getStatusCode($kernel, $url) === 404) {
            $notFound[] = ['type' => 'Главная', 'url' => '/', 'slug_or_id' => '/', 'name' => 'Главная', 'reason' => 'ответ 404'];
        }

        foreach (Author::orderBy('id')->get() as $author) {
            if (!$this->slugMatchesRoute($author->slug)) {
                continue;
            }
            $path = '/' . $author->slug . '/';
            if ($this->getStatusCode($kernel, $baseUrl . $path) === 404) {
                $notFound[] = ['type' => 'Автор', 'url' => $path, 'slug_or_id' => $author->slug, 'name' => $author->name, 'id' => $author->id, 'reason' => 'ответ 404'];
            }
        }

        foreach (Page::where('is_published', true)->where('is_home', false)->orderBy('id')->get() as $page) {
            if (!$this->slugMatchesRoute($page->slug)) {
                continue;
            }
            $path = '/' . $page->slug . '/';
            if ($this->getStatusCode($kernel, $baseUrl . $path) === 404) {
                $notFound[] = ['type' => 'Страница', 'url' => $path, 'slug_or_id' => $page->slug, 'name' => $page->title, 'id' => $page->id, 'reason' => 'ответ 404'];
            }
        }

        foreach (SeoPage::orderBy('id')->get() as $sp) {
            if (!$this->slugMatchesRoute($sp->path)) {
                continue;
            }
            $path = '/' . $sp->path . '/';
            if ($this->getStatusCode($kernel, $baseUrl . $path) === 404) {
                $notFound[] = ['type' => 'SEO-страница', 'url' => $path, 'slug_or_id' => $sp->path, 'name' => $sp->meta_title ?: $sp->path, 'id' => $sp->id, 'reason' => 'ответ 404'];
            }
        }

        $this->info('Проверка стихов по HTTP (может занять минуту)...');
        $bar = $this->output->createProgressBar($poems->count());
        $bar->start();
        foreach ($poems as $poem) {
            if ($this->slugMatchesRoute($poem->slug)) {
                $path = '/' . $poem->slug . '/';
                if ($this->getStatusCode($kernel, $baseUrl . $path) === 404) {
                    $notFound[] = ['type' => 'Стих', 'url' => $path, 'slug_or_id' => $poem->slug, 'name' => $poem->title, 'id' => $poem->id, 'reason' => 'ответ 404'];
                }
            }
            $bar->advance();
        }
        $bar->finish();

        $this->newLine(2);
        $this->info('Проверено записей: ' . $total);
        $this->info('Найдено 404 (или невалидный slug): ' . count($notFound));

        if (count($notFound) > 0) {
            $this->newLine();
            $this->error('========== Список 404 / невалидный slug ==========');
            foreach ($notFound as $row) {
                $reason = $row['reason'] ?? '';
                $this->line(sprintf(
                    "[%s] URL: %s | slug: %s | id: %s | %s | %s",
                    $row['type'],
                    $row['url'],
                    $row['slug_or_id'],
                    $row['id'] ?? '-',
                    mb_substr($row['name'], 0, 50),
                    $reason
                ));
            }
            $this->newLine();
            $path = storage_path('app/audit-404-' . date('Y-m-d-His') . '.json');
            file_put_contents($path, json_encode($notFound, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->info('Данные сохранены: ' . $path);
        }

        return count($notFound) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function slugMatchesRoute(?string $slug): bool
    {
        return $slug !== null && $slug !== '' && (bool) preg_match(self::SLUG_REGEX, $slug);
    }

    private function getStatusCode($kernel, string $url): int
    {
        $request = Request::create($url, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        return $response->getStatusCode();
    }
}
