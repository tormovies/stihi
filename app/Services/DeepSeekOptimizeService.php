<?php

namespace App\Services;

use App\Models\DeepSeekLog;
use App\Models\Poem;
use App\Models\PoemAnalysis;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Support\Facades\Http;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

class DeepSeekOptimizeService
{
    private const DEEPSEEK_URL = 'https://api.deepseek.com/chat/completions';
    private const POEM_EXCERPT_LENGTH = 100;
    private const DEFAULT_TIMEOUT = 330;
    private const DEFAULT_BATCH_SIZE = 10;
    private const DEFAULT_ANALYSIS_LENGTH_MIN = 600;
    private const DEFAULT_MAX_TOKENS = 4000;

    /**
     * Запуск одного батча оптимизации стихов. Возвращает массив для отображения или вывода в консоль.
     *
     * @return array{error: string|null, processed: int, failed: int[], message: string|null, rawResponse: string|null}
     */
    public function runPoemBatch(): array
    {
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = (int) (Setting::get('deepseek_batch_size') ?? self::DEFAULT_BATCH_SIZE);
        $promptTemplate = config('deepseek.prompt_template', '');

        if (!$apiKey) {
            return ['error' => 'Не задан API-ключ DeepSeek.', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }
        if (trim($promptTemplate) === '') {
            return ['error' => 'Не задан шаблон в config/deepseek.php ({{POEMS_JSON}}).', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }

        $poems = Poem::with('author')
            ->whereNotNull('published_at')
            ->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        if ($poems->isEmpty()) {
            return ['error' => null, 'processed' => 0, 'failed' => [], 'message' => 'Нет необработанных стихов.', 'rawResponse' => null];
        }

        $payload = $poems->map(fn (Poem $p) => [
            'id' => $p->id,
            'poet' => $p->author ? $p->author->name : '',
            'title' => $p->title,
            'poem' => mb_substr(preg_replace('/\s+/', ' ', strip_tags((string) $p->body)), 0, self::POEM_EXCERPT_LENGTH),
        ])->values()->toArray();

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $userMessage = str_replace('{{POEMS_JSON}}', $json, $promptTemplate);

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
                'entity_type' => 'poem',
                'request_payload' => $payload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $body,
                'processed_count' => 0,
                'failed_ids' => array_column($payload, 'id'),
                'error_message' => 'HTTP ' . $response->status() . ': ' . mb_substr($body, 0, 500),
            ]);
            return ['error' => 'Ошибка API: ' . $response->status(), 'processed' => 0, 'failed' => array_column($payload, 'id'), 'message' => null, 'rawResponse' => $body];
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
                'entity_type' => 'poem',
                'request_payload' => $payload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $content,
                'processed_count' => 0,
                'failed_ids' => array_column($payload, 'id'),
                'error_message' => 'Ответ не содержит items или response',
            ]);
            return ['error' => 'Ответ не содержит items или response.', 'processed' => 0, 'failed' => array_column($payload, 'id'), 'message' => null, 'rawResponse' => $content];
        }

        foreach ($list as $item) {
            $id = (int) ($item['poem_id'] ?? $item['id'] ?? 0);
            if ($id < 1) {
                $failed[] = $item['poem_id'] ?? $item['id'] ?? '?';
                continue;
            }
            $poem = $poems->firstWhere('id', $id);
            if (!$poem) {
                $failed[] = $id;
                continue;
            }
            $metaTitle = isset($item['meta_title']) ? $this->cleanSeoString((string) $item['meta_title'], 255) : null;
            $metaDesc = isset($item['meta_description']) ? $this->cleanSeoString((string) $item['meta_description'], 500) : null;
            $h1 = isset($item['h1']) ? $this->cleanSeoString((string) $item['h1'], 255) : null;
            $h1Desc = isset($item['h1_description']) ? $this->cleanSeoString((string) $item['h1_description'], 500) : null;
            if ($h1Desc === null && isset($item['text_by_h1'])) {
                $h1Desc = $this->cleanSeoString((string) $item['text_by_h1'], 500);
            }
            $poem->update([
                'meta_title' => $metaTitle ?: null,
                'meta_description' => $metaDesc ?: null,
                'h1' => $h1 ?: null,
                'h1_description' => $h1Desc ?: null,
            ]);
            $processed++;
        }

        DeepSeekLog::create([
            'status' => 'success',
            'entity_type' => 'poem',
            'request_payload' => $payload,
            'request_full' => $requestBodyRaw,
            'response_raw' => $content,
            'processed_count' => $processed,
            'failed_ids' => $failed ?: null,
            'error_message' => null,
        ]);

        $message = "Обработано стихов: {$processed}." . (count($failed) > 0 ? ' Не удалось обновить: ' . implode(', ', $failed) . '.' : '');
        return ['error' => null, 'processed' => $processed, 'failed' => $failed, 'message' => $message, 'rawResponse' => $content];
    }

    /**
     * Один батч генерации анализов для длинных стихов (body_length >= порог). Возвращает тот же формат, что и runPoemBatch.
     *
     * @return array{error: string|null, processed: int, failed: int[], message: string|null, rawResponse: string|null}
     */
    public function runAnalysisBatch(): array
    {
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = (int) (Setting::get('deepseek_batch_size') ?? self::DEFAULT_BATCH_SIZE);
        $analysisLengthMin = (int) (Setting::get('analysis_length_min') ?? self::DEFAULT_ANALYSIS_LENGTH_MIN);
        $promptTemplate = config('deepseek.prompt_template_analysis', '');

        if (!$apiKey) {
            return ['error' => 'Не задан API-ключ DeepSeek.', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }
        if (trim($promptTemplate) === '') {
            return ['error' => 'Не задан шаблон анализа в config/deepseek.php (prompt_template_analysis).', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }

        // Анализы — до двух стихов за запуск, один запрос к API (массив стихов → массив анализов в ответе).
        // inRandomOrder() чтобы охватывать разных авторов, а не только с минимальными id.
        $poems = Poem::with('author')
            ->whereNotNull('published_at')
            ->where('body_length', '>=', $analysisLengthMin)
            ->whereDoesntHave('analysis')
            ->inRandomOrder()
            ->limit(2)
            ->get();

        if ($poems->isEmpty()) {
            return ['error' => null, 'processed' => 0, 'failed' => [], 'message' => 'Нет стихов для анализа (длина >= ' . $analysisLengthMin . ' знаков, без анализа).', 'rawResponse' => null];
        }

        $userPayload = $poems->map(fn (Poem $p) => [
            'id' => $p->id,
            'author' => $p->author ? $p->author->name : '',
            'title' => $p->title,
            'first_lines' => mb_substr(preg_replace('/\s+/', ' ', strip_tags((string) $p->body)), 0, self::POEM_EXCERPT_LENGTH),
        ])->values()->toArray();
        $userMessage = json_encode($userPayload, JSON_UNESCAPED_UNICODE);

        $systemMessage = $promptTemplate;
        $maxTokens = (int) (Setting::get('deepseek_max_tokens') ?? self::DEFAULT_MAX_TOKENS);
        $requestBody = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $userMessage],
            ],
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
        $lastResponseContent = '';

        if (!$response->successful()) {
            $failed = $poems->pluck('id')->all();
            $lastResponseContent = $response->body();
            DeepSeekLog::create([
                'status' => 'api_error',
                'entity_type' => 'analysis',
                'request_payload' => $userPayload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $lastResponseContent,
                'processed_count' => 0,
                'failed_ids' => $failed,
                'error_message' => 'HTTP ' . $response->status(),
            ]);
            return ['error' => 'Ошибка API: ' . $response->status(), 'processed' => 0, 'failed' => $failed, 'message' => null, 'rawResponse' => $lastResponseContent];
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';
        $lastResponseContent = $content;
        $content = $this->extractJsonFromContent($content);
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (empty($decoded['success'])) {
            $failed = $poems->pluck('id')->all();
            DeepSeekLog::create([
                'status' => 'parse_error',
                'entity_type' => 'analysis',
                'request_payload' => $userPayload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $lastResponseContent,
                'processed_count' => 0,
                'failed_ids' => $failed,
                'error_message' => 'Ответ без success: true или без items',
            ]);
            return ['error' => 'Ответ не содержит success: true или items.', 'processed' => 0, 'failed' => $failed, 'message' => null, 'rawResponse' => $lastResponseContent];
        }

        // Ответ: items[] с poem_id и data (или один data для обратной совместимости)
        $items = [];
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $items = $decoded['items'];
        } elseif (isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data']['analysis_markdown'])) {
            $firstPoem = $poems->first();
            $items = [['poem_id' => $firstPoem->id, 'data' => $decoded['data']]];
        }

        $poemsById = $poems->keyBy('id');
        foreach ($items as $item) {
            $poemId = (int) ($item['poem_id'] ?? $item['id'] ?? 0);
            $data = $item['data'] ?? $item;
            $poem = $poemsById->get($poemId);
            if (!$poem) {
                $failed[] = $poemId;
                continue;
            }
            $seo = isset($data['seo']) && is_array($data['seo']) ? $data['seo'] : [];
            $getSeo = function (string $name) use ($seo): ?string {
                foreach ($seo as $entry) {
                    if (isset($entry['name']) && $entry['name'] === $name && isset($entry['content'])) {
                        return trim((string) $entry['content']);
                    }
                }
                return null;
            };
            $metaTitle = $this->cleanSeoString($getSeo('meta_title') ?? '', 255);
            $metaDesc = $this->cleanSeoString($getSeo('meta_description') ?? '', 500);
            $h1 = $this->cleanSeoString($getSeo('h1') ?? '', 255);
            $h1Desc = $this->cleanSeoString($data['text_by_h1'] ?? '', 500);
            $analysisMarkdown = isset($data['analysis_markdown']) ? trim((string) $data['analysis_markdown']) : '';
            if ($this->isAnalysisMarkdownRequestEcho($analysisMarkdown)) {
                $failed[] = $poem->id;
                continue;
            }
            $markdownCleaned = $analysisMarkdown !== '' ? $this->cleanSeoString($analysisMarkdown, 50000) : null;
            if ($markdownCleaned === null) {
                $failed[] = $poem->id;
                continue;
            }
            $analysisHtml = $this->markdownToHtml($markdownCleaned);
            if ($analysisHtml === '') {
                $failed[] = $poem->id;
                continue;
            }
            PoemAnalysis::create([
                'poem_id' => $poem->id,
                'analysis_text' => $analysisHtml,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDesc,
                'h1' => $h1,
                'h1_description' => $h1Desc,
            ]);
            $processed++;
        }

        $respondedPoemIds = collect($items)->map(fn ($item) => (int) ($item['poem_id'] ?? $item['id'] ?? 0))->filter()->all();
        $missingInResponse = $poems->pluck('id')->diff($respondedPoemIds)->all();
        $failed = array_values(array_unique(array_merge($failed, $missingInResponse)));

        $payloadForLog = $poems->map(fn (Poem $p) => [
            'id' => $p->id,
            'author' => $p->author ? $p->author->name : '',
            'title' => $p->title,
            'first_lines' => mb_substr(preg_replace('/\s+/', ' ', strip_tags((string) $p->body)), 0, self::POEM_EXCERPT_LENGTH),
        ])->values()->toArray();

        DeepSeekLog::create([
            'status' => count($failed) === $poems->count() ? 'api_error' : 'success',
            'entity_type' => 'analysis',
            'request_payload' => $payloadForLog,
            'request_full' => $requestBodyRaw,
            'response_raw' => $lastResponseContent,
            'processed_count' => $processed,
            'failed_ids' => $failed ?: null,
            'error_message' => null,
        ]);

        $message = "Обработано анализов: {$processed}." . (count($failed) > 0 ? ' Не удалось: ' . implode(', ', $failed) . '.' : '');
        return ['error' => null, 'processed' => $processed, 'failed' => $failed, 'message' => $message, 'rawResponse' => $lastResponseContent];
    }

    /**
     * Один батч SEO для страниц тегов. Берёт теги без meta_title (или все — по необходимости).
     *
     * @return array{error: string|null, processed: int, failed: int[], message: string|null, rawResponse: string|null}
     */
    public function runTagsSeoBatch(): array
    {
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = (int) (Setting::get('deepseek_batch_size') ?? self::DEFAULT_BATCH_SIZE);
        $promptTemplate = config('deepseek.prompt_template_tags_seo', '');

        if (!$apiKey) {
            return ['error' => 'Не задан API-ключ DeepSeek.', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }
        if (trim($promptTemplate) === '') {
            return ['error' => 'Не задан шаблон в config/deepseek.php (prompt_template_tags_seo, {{TAGS_JSON}}).', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }

        $tags = Tag::query()
            ->where(fn ($q) => $q->whereNull('meta_title')->orWhere('meta_title', ''))
            ->orderBy('id')
            ->limit($batchSize)
            ->get(['id', 'name', 'slug']);

        if ($tags->isEmpty()) {
            return ['error' => null, 'processed' => 0, 'failed' => [], 'message' => 'Нет тегов без SEO (все уже обработаны или батч пуст).', 'rawResponse' => null];
        }

        $payload = $tags->map(fn (Tag $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
        ])->values()->toArray();

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $userMessage = str_replace('{{TAGS_JSON}}', $json, $promptTemplate);

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
                'entity_type' => 'tag',
                'request_payload' => $payload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $body,
                'processed_count' => 0,
                'failed_ids' => array_column($payload, 'id'),
                'error_message' => 'HTTP ' . $response->status() . ': ' . mb_substr($body, 0, 500),
            ]);
            return ['error' => 'Ошибка API: ' . $response->status(), 'processed' => 0, 'failed' => array_column($payload, 'id'), 'message' => null, 'rawResponse' => $body];
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';
        $decoded = json_decode($this->extractJsonFromContent($content), true);
        $list = isset($decoded['items']) && is_array($decoded['items'])
            ? $decoded['items']
            : (isset($decoded['response']) && is_array($decoded['response']) ? $decoded['response'] : null);

        if ($list === null) {
            DeepSeekLog::create([
                'status' => 'parse_error',
                'entity_type' => 'tag',
                'request_payload' => $payload,
                'request_full' => $requestBodyRaw,
                'response_raw' => $content,
                'processed_count' => 0,
                'failed_ids' => array_column($payload, 'id'),
                'error_message' => 'Ответ не содержит items или response',
            ]);
            return ['error' => 'Ответ не содержит items или response.', 'processed' => 0, 'failed' => array_column($payload, 'id'), 'message' => null, 'rawResponse' => $content];
        }

        $tagsById = $tags->keyBy('id');
        foreach ($list as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id < 1) {
                $failed[] = $item['id'] ?? '?';
                continue;
            }
            $tag = $tagsById->get($id);
            if (!$tag) {
                $failed[] = $id;
                continue;
            }
            $metaTitle = isset($item['meta_title']) ? $this->cleanSeoString((string) $item['meta_title'], 255) : null;
            $metaDesc = isset($item['meta_description']) ? $this->cleanSeoString((string) $item['meta_description'], 500) : null;
            $h1 = isset($item['h1']) ? $this->cleanSeoString((string) $item['h1'], 255) : null;
            $h1Desc = isset($item['h1_description']) ? $this->cleanSeoString((string) $item['h1_description'], 500) : null;
            if ($h1Desc === null && isset($item['text_by_h1'])) {
                $h1Desc = $this->cleanSeoString((string) $item['text_by_h1'], 500);
            }
            $tag->update([
                'meta_title' => $metaTitle ?: null,
                'meta_description' => $metaDesc ?: null,
                'h1' => $h1 ?: null,
                'h1_description' => $h1Desc ?: null,
            ]);
            $processed++;
        }

        DeepSeekLog::create([
            'status' => 'success',
            'entity_type' => 'tag',
            'request_payload' => $payload,
            'request_full' => $requestBodyRaw,
            'response_raw' => $content,
            'processed_count' => $processed,
            'failed_ids' => $failed ?: null,
            'error_message' => null,
        ]);

        $message = "Обработано тегов (SEO): {$processed}." . (count($failed) > 0 ? ' Не удалось: ' . implode(', ', $failed) . '.' : '');
        return ['error' => null, 'processed' => $processed, 'failed' => $failed, 'message' => $message, 'rawResponse' => $content];
    }

    /**
     * Один батч разметки стихов по тегам: берём стихи без тегов (размер батча из настроек), один запрос к API с массивом стихов, ответ items с poem_id и tag_slugs для каждого.
     *
     * @return array{error: string|null, processed: int, failed: int[], message: string|null, rawResponse: string|null}
     */
    public function runPoemTagsBatch(): array
    {
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = (int) (Setting::get('deepseek_batch_size') ?? self::DEFAULT_BATCH_SIZE);
        $promptTemplate = config('deepseek.prompt_template_poem_tags', '');

        if (!$apiKey) {
            return ['error' => 'Не задан API-ключ DeepSeek.', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }
        if (trim($promptTemplate) === '') {
            return ['error' => 'Не задан шаблон в config/deepseek.php (prompt_template_poem_tags, {{POEMS_JSON}}, {{TAGS_JSON}}).', 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
        }

        $tagsList = Tag::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);
        if ($tagsList->isEmpty()) {
            return ['error' => null, 'processed' => 0, 'failed' => [], 'message' => 'Нет тегов в базе. Сначала создайте теги.', 'rawResponse' => null];
        }

        $tagsJson = json_encode($tagsList->map(fn (Tag $t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])->values()->toArray(), JSON_UNESCAPED_UNICODE);

        $poems = Poem::with('author')
            ->whereNotNull('published_at')
            ->whereDoesntHave('tags')
            ->inRandomOrder()
            ->limit($batchSize)
            ->get();

        if ($poems->isEmpty()) {
            return ['error' => null, 'processed' => 0, 'failed' => [], 'message' => 'Нет стихов без тегов для разметки.', 'rawResponse' => null];
        }

        $poemsPayload = $poems->map(fn (Poem $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'author' => $p->author ? $p->author->name : '',
            'excerpt' => mb_substr(preg_replace('/\s+/', ' ', strip_tags((string) $p->body)), 0, self::POEM_EXCERPT_LENGTH),
        ])->values()->toArray();
        $poemsJson = json_encode($poemsPayload, JSON_UNESCAPED_UNICODE);
        $userMessage = str_replace('{{POEMS_JSON}}', $poemsJson, $promptTemplate);
        $userMessage = str_replace('{{TAGS_JSON}}', $tagsJson, $userMessage);

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
        $lastResponseContent = '';

        if (!$response->successful()) {
            $failed = $poems->pluck('id')->all();
            $lastResponseContent = $response->body();
            DeepSeekLog::create([
                'status' => 'api_error',
                'entity_type' => 'poem_tag',
                'request_payload' => $poems->map(fn (Poem $p) => ['id' => $p->id, 'title' => $p->title])->values()->toArray(),
                'request_full' => $requestBodyRaw,
                'response_raw' => $lastResponseContent,
                'processed_count' => 0,
                'failed_ids' => $failed,
                'error_message' => 'HTTP ' . $response->status(),
            ]);
            return ['error' => 'Ошибка API: ' . $response->status(), 'processed' => 0, 'failed' => $failed, 'message' => null, 'rawResponse' => $lastResponseContent];
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';
        $lastResponseContent = $content;
        $decoded = json_decode($this->extractJsonFromContent($content), true);
        $items = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];

        $poemsById = $poems->keyBy('id');
        foreach ($items as $item) {
            $poemId = (int) ($item['poem_id'] ?? $item['id'] ?? 0);
            $poem = $poemsById->get($poemId);
            if (!$poem) {
                $failed[] = $poemId;
                continue;
            }
            $tagSlugs = isset($item['tag_slugs']) && is_array($item['tag_slugs']) ? $item['tag_slugs'] : [];
            $tagIds = $tagsList->whereIn('slug', $tagSlugs)->pluck('id')->all();
            $poem->tags()->sync($tagIds);
            $processed++;
        }

        $respondedIds = array_map(fn ($i) => (int) ($i['poem_id'] ?? $i['id'] ?? 0), $items);
        $missing = $poems->pluck('id')->diff($respondedIds)->all();
        $failed = array_values(array_unique(array_merge($failed, $missing)));

        DeepSeekLog::create([
            'status' => count($failed) === $poems->count() ? 'api_error' : 'success',
            'entity_type' => 'poem_tag',
            'request_payload' => $poems->map(fn (Poem $p) => ['id' => $p->id, 'title' => $p->title])->values()->toArray(),
            'request_full' => $requestBodyRaw,
            'response_raw' => $lastResponseContent,
            'processed_count' => $processed,
            'failed_ids' => $failed ?: null,
            'error_message' => null,
        ]);

        $message = "Разметка по тегам: обработано стихов {$processed}." . (count($failed) > 0 ? ' Ошибки (id): ' . implode(', ', $failed) . '.' : '');
        return ['error' => null, 'processed' => $processed, 'failed' => $failed, 'message' => $message, 'rawResponse' => $lastResponseContent];
    }

    /**
     * Извлекает JSON из ответа: если обёрнут в ```json ... ``` или ``` ... ``` — убирает обёртку.
     */
    private function extractJsonFromContent(string $content): string
    {
        $content = trim($content);
        if (preg_match('/^```(?:json)?\s*\n?(.*?)\n?```\s*$/s', $content, $m)) {
            return trim($m[1]);
        }
        return $content;
    }

    /**
     * Проверяет, не вернула ли модель в analysis_markdown сам запрос (system/user) — такие ответы не сохраняем.
     */
    private function isAnalysisMarkdownRequestEcho(string $text): bool
    {
        if ($text === '') {
            return true;
        }
        $lower = mb_strtolower($text);
        $signatures = [
            '"role":"system"',
            '"role": "system"',
            '"model":"deepseek-chat"',
            '"model": "deepseek-chat"',
            '"messages":',
            '"messages": [',
        ];
        foreach ($signatures as $sig) {
            if (str_contains($lower, $sig)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Конвертирует Markdown в HTML для сохранения в БД и вывода на странице.
     */
    private function markdownToHtml(string $markdown): string
    {
        $env = Environment::createCommonMarkEnvironment();
        $converter = new MarkdownConverter($env);
        $result = $converter->convert($markdown);
        return trim($result->getContent()) ?: '';
    }

    public function cleanSeoString(string $value, int $maxLength): ?string
    {
        $decoded = trim($value);
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
}
