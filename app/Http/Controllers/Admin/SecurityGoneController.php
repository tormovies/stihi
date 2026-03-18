<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\GonePath;
use App\Models\Poem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityGoneController extends Controller
{
    private const LOG_DIR = 'logs';
    private const PREFIX_404 = '404-';

    /** Паттерны User-Agent для определения бота (как в LogBotRequests). */
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'scanner', 'headless',
        'curl', 'wget', 'python', 'java/', 'go-http', 'php/', 'ruby',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'telegrambot',
        'yandex', 'googlebot', 'bingbot', 'duckduckbot', 'baiduspider',
        'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'petalbot', 'bytespider',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            if ($request->has('path') && $request->filled('path')) {
                return $this->storePath($request);
            }
            if ($request->has('paths') && is_array($request->paths)) {
                return $this->storePaths($request);
            }
        }

        $gonePaths = GonePath::orderBy('path')->get();
        $candidates = [];
        $dateFrom = $request->query('date_from', now()->subDays(7)->format('Y-m-d'));
        $dateTo = $request->query('date_to', now()->format('Y-m-d'));
        $filterBot = $request->query('filter_bot', 'all');

        if ($request->has('analyze') && $request->query('analyze') === '1') {
            $candidates = $this->parseLogCandidates($dateFrom, $dateTo, $filterBot);
        }

        return view('admin.security.gone', [
            'gonePaths' => $gonePaths,
            'candidates' => $candidates,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filterBot' => $filterBot,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        GonePath::where('id', $id)->delete();
        return redirect()->route('admin.security.gone')->with('success', 'Путь удалён из списка 410.');
    }

    private function storePath(Request $request): RedirectResponse
    {
        $request->validate(['path' => 'required|string|max:500']);
        $path = trim($request->input('path'));
        if ($path === '') {
            return redirect()->route('admin.security.gone')->with('error', 'Путь не может быть пустым.');
        }
        if ($this->isPhpPath($path)) {
            return redirect()->route('admin.security.gone')->with('error', 'Пути с расширением .php не добавляются (брутфорс файлов).');
        }
        $err = $this->verifyPathNotContent($path);
        if ($err) {
            return redirect()->route('admin.security.gone')->with('error', $err);
        }
        GonePath::firstOrCreate(['path' => $path]);
        return redirect()->route('admin.security.gone')->with('success', 'Путь добавлен в список 410.');
    }

    private function storePaths(Request $request): RedirectResponse
    {
        $paths = array_values(array_filter(array_map('trim', $request->input('paths', []))));
        $added = 0;
        $skipped = [];
        foreach ($paths as $path) {
            if ($path === '') {
                continue;
            }
            if ($this->isPhpPath($path)) {
                $skipped[] = $path;
                continue;
            }
            if (GonePath::where('path', $path)->exists()) {
                $skipped[] = $path;
                continue;
            }
            if ($this->verifyPathNotContent($path)) {
                $skipped[] = $path;
                continue;
            }
            GonePath::create(['path' => $path]);
            $added++;
        }
        $msg = "Добавлено в список 410: {$added}.";
        if (!empty($skipped)) {
            $msg .= ' Пропущено (уже в списке или контент есть): ' . count($skipped) . '.';
        }
        return redirect()->route('admin.security.gone')->with('success', $msg);
    }

    /**
     * Проверка: по этому пути нет контента (стих/автор). Возвращает сообщение об ошибке или null.
     */
    private function verifyPathNotContent(string $path): ?string
    {
        $slug = $path;
        if (Poem::where('slug', $slug)->exists()) {
            return "Стих с slug «{$path}» существует — не добавлено.";
        }
        if (Author::where('slug', $slug)->exists()) {
            return "Автор с slug «{$path}» существует — не добавлено.";
        }
        return null;
    }

    private function parseLogCandidates(string $dateFrom, string $dateTo, string $filterBot): array
    {
        $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
        $to = \Carbon\Carbon::parse($dateTo)->endOfDay();
        if ($from->gt($to)) {
            return [];
        }
        $dir = storage_path(self::LOG_DIR);
        if (!is_dir($dir)) {
            return [];
        }
        $aggregate = [];
        $current = $from->copy();
        while ($current->lte($to)) {
            $filename = self::PREFIX_404 . $current->format('Y-m-d') . '.log';
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (is_file($path) && is_readable($path)) {
                $this->parseLogFile($path, $aggregate);
            }
            $current->addDay();
        }
        $onlyBots = $filterBot === 'bots';
        $candidates = [];
        $existingPaths = GonePath::pluck('path')->all();
        foreach ($aggregate as $path => $data) {
            if ($this->isPhpPath($path)) {
                continue;
            }
            if (in_array($path, $existingPaths, true)) {
                continue;
            }
            if ($onlyBots && !$data['has_bot']) {
                continue;
            }
            $candidates[] = [
                'path' => $path,
                'count' => $data['count'],
                'has_bot' => $data['has_bot'],
                'content_exists' => $this->verifyPathNotContent($path) !== null,
            ];
        }
        usort($candidates, fn ($a, $b) => $b['count'] <=> $a['count']);
        return $candidates;
    }

    private function parseLogFile(string $filePath, array &$aggregate): void
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return;
        }
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode("\t", $line);
            $path = null;
            $ua = '';
            if (isset($parts[0]) && ($parts[0] === 'BLOCKED' || $parts[0] === '410')) {
                $path = $parts[2] ?? '';
                $ua = $parts[5] ?? '';
            } else {
                $path = $parts[1] ?? '';
                $ua = $parts[4] ?? '';
            }
            if ($path === '') {
                continue;
            }
            if (!isset($aggregate[$path])) {
                $aggregate[$path] = ['count' => 0, 'has_bot' => false];
            }
            $aggregate[$path]['count']++;
            if ($this->looksLikeBot($ua)) {
                $aggregate[$path]['has_bot'] = true;
            }
        }
    }

    private function looksLikeBot(string $ua): bool
    {
        $lower = mb_strtolower($ua);
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /** Пути с .php — брутфорс, не попадают в кандидаты и в список 410. Публичных страниц .php нет. */
    private function isPhpPath(string $path): bool
    {
        $path = trim($path, '/');
        if ($path === '') {
            return false;
        }
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if (str_ends_with(mb_strtolower($segment), '.php')) {
                return true;
            }
        }
        return false;
    }
}
