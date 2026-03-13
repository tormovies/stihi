<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\DeepSeekLog;
use App\Models\Poem;
use App\Models\Setting;
use App\Services\DeepSeekOptimizeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class DeepSeekController extends Controller
{
    private const DEEPSEEK_URL = 'https://api.deepseek.com/chat/completions';
    private const POEM_EXCERPT_LENGTH = 100;
    private const DEFAULT_TIMEOUT = 330;
    private const DEFAULT_BATCH_SIZE = 10;
    private const DEFAULT_ANALYSIS_LENGTH_MIN = 600;
    private const DEFAULT_MAX_TOKENS = 4000;

    public function index(): View
    {
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = (int) (Setting::get('deepseek_batch_size') ?? self::DEFAULT_BATCH_SIZE);
        $analysisLengthMin = (int) (Setting::get('analysis_length_min') ?? self::DEFAULT_ANALYSIS_LENGTH_MIN);
        $maxTokens = (int) (Setting::get('deepseek_max_tokens') ?? self::DEFAULT_MAX_TOKENS);

        $unprocessedPoems = Poem::whereNotNull('published_at')
            ->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', '') )
            ->count();
        $unprocessedAuthors = Author::where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))
            ->count();
        $unprocessedAnalyses = Poem::whereNotNull('published_at')
            ->where('body_length', '>=', $analysisLengthMin)
            ->whereDoesntHave('analysis')
            ->count();

        $cronRunPoems = Setting::get('cron_run_poems', 'off');
        $cronRunAnalyses = Setting::get('cron_run_analyses', '5');

        return view('admin.deepseek.index', [
            'apiKeySet' => $apiKey !== null && $apiKey !== '',
            'timeout' => $timeout,
            'batchSize' => $batchSize,
            'analysisLengthMin' => $analysisLengthMin,
            'maxTokens' => $maxTokens,
            'unprocessedPoems' => $unprocessedPoems,
            'unprocessedAuthors' => $unprocessedAuthors,
            'unprocessedAnalyses' => $unprocessedAnalyses,
            'cronRunPoems' => $cronRunPoems,
            'cronRunAnalyses' => $cronRunAnalyses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'timeout' => 'nullable|integer|min:60|max:600',
            'batch_size' => 'nullable|integer|min:1|max:50',
            'analysis_length_min' => 'nullable|integer|min:100|max:5000',
            'max_tokens' => 'nullable|integer|min:500|max:32000',
            'cron_run_poems' => 'nullable|string|in:off,5,10,15,20,30',
            'cron_run_analyses' => 'nullable|string|in:off,5,10,15,20,30',
        ]);

        Setting::set('cron_run_poems', $request->input('cron_run_poems', 'off'));
        Setting::set('cron_run_analyses', $request->input('cron_run_analyses', '5'));
        if ($request->filled('timeout')) {
            Setting::set('deepseek_timeout', (string) $request->timeout);
        }
        if ($request->filled('batch_size')) {
            Setting::set('deepseek_batch_size', (string) $request->batch_size);
        }
        if ($request->filled('analysis_length_min')) {
            Setting::set('analysis_length_min', (string) $request->analysis_length_min);
        }
        if ($request->filled('max_tokens')) {
            Setting::set('deepseek_max_tokens', (string) $request->max_tokens);
        }
        if ($request->filled('api_key')) {
            Setting::set('deepseek_api_key', $request->api_key);
        }

        return redirect()->route('admin.deepseek.index')->with('success', 'Настройки сохранены.');
    }

    public function wipePoems(): RedirectResponse
    {
        Poem::query()->update([
            'meta_title' => null,
            'meta_description' => null,
            'h1' => null,
            'h1_description' => null,
        ]);
        return redirect()->route('admin.deepseek.index')->with('success', 'SEO-параметры всех стихов сброшены.');
    }

    public function wipeAuthors(): RedirectResponse
    {
        Author::query()->update([
            'meta_title' => null,
            'meta_description' => null,
            'h1' => null,
            'h1_description' => null,
        ]);
        return redirect()->route('admin.deepseek.index')->with('success', 'SEO-параметры всех авторов сброшены.');
    }

    public function run(DeepSeekOptimizeService $service): View
    {
        set_time_limit(600);
        $result = $service->runPoemBatch();
        return view('admin.deepseek.run', [
            'error' => $result['error'],
            'processed' => $result['processed'],
            'failed' => $result['failed'],
            'entity_label' => 'стихов',
            'message' => $result['message'],
            'rawResponse' => $result['rawResponse'],
        ]);
    }

    public function runAuthors(): View
    {
        set_time_limit(600);
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = (int) (Setting::get('deepseek_batch_size') ?? self::DEFAULT_BATCH_SIZE);
        $promptTemplate = config('deepseek.prompt_template_authors', '');

        if (!$apiKey) {
            return view('admin.deepseek.run', [
                'error' => 'Не задан API-ключ DeepSeek. Сохраните его в настройках.',
                'processed' => 0,
                'failed' => [],
                'entity_label' => 'авторов',
                'rawResponse' => null,
            ]);
        }
        if (trim($promptTemplate) === '') {
            return view('admin.deepseek.run', [
                'error' => 'Не задан шаблон для авторов в config/deepseek.php (ключ prompt_template_authors, плейсхолдер {{AUTHORS_JSON}}).',
                'processed' => 0,
                'failed' => [],
                'entity_label' => 'авторов',
                'rawResponse' => null,
            ]);
        }

        $authors = Author::query()
            ->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))
            ->orderBy('id')
            ->limit($batchSize)
            ->get(['id', 'name', 'years_of_life', 'slug']);

        if ($authors->isEmpty()) {
            return view('admin.deepseek.run', [
                'error' => null,
                'processed' => 0,
                'failed' => [],
                'entity_label' => 'авторов',
                'message' => 'Нет необработанных авторов (все уже имеют SEO или батч пуст).',
                'rawResponse' => null,
            ]);
        }

        $payload = $authors->map(fn (Author $a) => [
            'id' => $a->id,
            'name' => $a->name ?? '',
            'years_of_life' => $a->years_of_life ?? '',
        ])->values()->toArray();

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $userMessage = str_replace('{{AUTHORS_JSON}}', $json, $promptTemplate);

        $maxTokens = (int) (Setting::get('deepseek_max_tokens') ?? self::DEFAULT_MAX_TOKENS);
        $requestBody = [
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $userMessage]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
            'max_tokens' => $maxTokens,
        ];
        $requestBodyRaw = json_encode($requestBody, JSON_UNESCAPED_UNICODE);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->withBody($requestBodyRaw, 'application/json')
            ->post(self::DEEPSEEK_URL);

        $failed = [];
        $processed = 0;

        if (!$response->successful()) {
            $body = $response->body();
            DeepSeekLog::create([
                'status' => 'api_error',
                'entity_type' => 'author',
                'request_payload' => $payload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $body,
                'processed_count' => 0,
                'failed_ids' => array_column($payload, 'id'),
                'error_message' => 'HTTP ' . $response->status() . ': ' . mb_substr($body, 0, 500),
            ]);
            return view('admin.deepseek.run', [
                'error' => 'Ошибка API: ' . $response->status() . ' — ' . $body,
                'processed' => 0,
                'failed' => array_column($payload, 'id'),
                'entity_label' => 'авторов',
                'rawResponse' => $body,
            ]);
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';
        $decoded = json_decode($content, true);
        $list = isset($decoded['items']) && is_array($decoded['items'])
            ? $decoded['items']
            : (isset($decoded['response']) && is_array($decoded['response']) ? $decoded['response'] : null);

        if ($list === null) {
            DeepSeekLog::create([
                'status' => 'parse_error',
                'entity_type' => 'author',
                'request_payload' => $payload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $content,
                'processed_count' => 0,
                'failed_ids' => array_column($payload, 'id'),
                'error_message' => 'Ответ не содержит items или response',
            ]);
            return view('admin.deepseek.run', [
                'error' => 'Ответ API не содержит items или response.',
                'processed' => 0,
                'failed' => array_column($payload, 'id'),
                'entity_label' => 'авторов',
                'rawResponse' => $content,
            ]);
        }

        foreach ($list as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id < 1) {
                $failed[] = $item['id'] ?? '?';
                continue;
            }
            $author = $authors->firstWhere('id', $id);
            if (!$author) {
                $failed[] = $id;
                continue;
            }
            $metaTitle = isset($item['meta_title']) ? self::cleanSeoString((string) $item['meta_title'], 255) : null;
            $metaDesc = isset($item['meta_description']) ? self::cleanSeoString((string) $item['meta_description'], 500) : null;
            $h1 = isset($item['h1']) ? self::cleanSeoString((string) $item['h1'], 255) : null;
            $h1Desc = isset($item['h1_description']) ? self::cleanSeoString((string) $item['h1_description'], 500) : null;
            if ($h1Desc === null && isset($item['text_by_h1'])) {
                $h1Desc = self::cleanSeoString((string) $item['text_by_h1'], 500);
            }
            $author->update([
                'meta_title' => $metaTitle ?: null,
                'meta_description' => $metaDesc ?: null,
                'h1' => $h1 ?: null,
                'h1_description' => $h1Desc ?: null,
            ]);
            $processed++;
        }

        DeepSeekLog::create([
            'status' => 'success',
            'entity_type' => 'author',
            'request_payload' => $payload,
            'request_full' => $requestBodyRaw,
            'response_raw' => $content,
            'processed_count' => $processed,
            'failed_ids' => $failed ?: null,
            'error_message' => null,
        ]);

        return view('admin.deepseek.run', [
            'error' => null,
            'processed' => $processed,
            'failed' => $failed,
            'entity_label' => 'авторов',
            'message' => "Обработано авторов: {$processed}." . (count($failed) > 0 ? ' Не удалось обновить: ' . implode(', ', $failed) . '.' : ''),
            'rawResponse' => $content,
        ]);
    }

    public function runAnalyses(DeepSeekOptimizeService $service): View
    {
        set_time_limit(600);
        $result = $service->runAnalysisBatch();
        return view('admin.deepseek.run', [
            'error' => $result['error'],
            'processed' => $result['processed'],
            'failed' => $result['failed'],
            'entity_label' => 'анализов',
            'message' => $result['message'],
            'rawResponse' => $result['rawResponse'],
        ]);
    }

    /**
     * Декодирует HTML-сущности и обрезает строку для SEO-полей (в БД храним обычный текст).
     * Убирает &#039;, &apos;, &quot;, двойное кодирование и т.п.
     */
    private static function cleanSeoString(string $value, int $maxLength): ?string
    {
        $decoded = trim($value);
        // Явная замена апострофных сущностей (часто приходят от API)
        $decoded = str_replace(["&#039;", "&#39;", "&apos;", "&#x27;"], "'", $decoded);
        for ($i = 0; $i < 3; $i++) {
            $prev = $decoded;
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
            $decoded = preg_replace_callback('/&#(\d+);?/', fn ($m) => mb_chr((int) $m[1], 'UTF-8'), $decoded);
            $decoded = preg_replace_callback('/&#x([0-9a-fA-F]+);?/', fn ($m) => mb_chr((int) hexdec($m[1]), 'UTF-8'), $decoded);
            if ($decoded === $prev) {
                break;
            }
        }
        $decoded = trim($decoded);
        if ($decoded === '') {
            return null;
        }
        return mb_substr($decoded, 0, $maxLength) ?: null;
    }

    public function log(): View
    {
        $logs = DeepSeekLog::orderByDesc('id')->paginate(20);
        foreach ($logs as $log) {
            $requestIds = $log->request_payload ? array_column($log->request_payload, 'id') : [];
            $failedIds = $log->failed_ids ?? [];
            $updatedIds = array_values(array_diff($requestIds, $failedIds));
            $entityType = $log->entity_type ?? 'poem';
            if ($entityType === 'author') {
                $log->updated_poems = collect();
                $log->updated_authors = $updatedIds
                    ? Author::whereIn('id', $updatedIds)->get(['id', 'name', 'slug'])
                    : collect();
            } else {
                $log->updated_poems = $updatedIds
                    ? Poem::with('author:id,name,slug')->whereIn('id', $updatedIds)->get(['id', 'slug', 'title', 'author_id'])
                    : collect();
                $log->updated_authors = collect();
            }
            $log->is_analysis = $entityType === 'analysis';
        }
        return view('admin.deepseek.log', compact('logs'));
    }
}
