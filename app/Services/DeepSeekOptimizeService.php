<?php

namespace App\Services;

use App\Models\DeepSeekLog;
use App\Models\Poem;
use App\Models\PoemAnalysis;
use App\Models\Setting;
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

        // Анализы — по одному стиху за запуск (один запрос к API)
        $poems = Poem::with('author')
            ->whereNotNull('published_at')
            ->where('body_length', '>=', $analysisLengthMin)
            ->whereDoesntHave('analysis')
            ->orderBy('id')
            ->limit(1)
            ->get();

        if ($poems->isEmpty()) {
            return ['error' => null, 'processed' => 0, 'failed' => [], 'message' => 'Нет стихов для анализа (длина >= ' . $analysisLengthMin . ' знаков, без анализа).', 'rawResponse' => null];
        }

        $systemMessage = $promptTemplate;
        $maxTokens = (int) (Setting::get('deepseek_max_tokens') ?? self::DEFAULT_MAX_TOKENS);
        $failed = [];
        $processed = 0;
        $lastRequestRaw = '';
        $lastResponseContent = '';

        foreach ($poems as $poem) {
            $userPayload = [
                'id' => $poem->id,
                'author' => $poem->author ? $poem->author->name : '',
                'title' => $poem->title,
                'first_lines' => mb_substr(preg_replace('/\s+/', ' ', strip_tags((string) $poem->body)), 0, self::POEM_EXCERPT_LENGTH),
            ];
            $userMessage = json_encode($userPayload, JSON_UNESCAPED_UNICODE);
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
            $lastRequestRaw = $requestBodyRaw;

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->withBody($requestBodyRaw, 'application/json')
                ->post(self::DEEPSEEK_URL);

            if (!$response->successful()) {
                $failed[] = $poem->id;
                $lastResponseContent = $response->body();
                continue;
            }

            $body = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? '';
            $lastResponseContent = $content;
            $decoded = json_decode($content, true);

            if (empty($decoded['success']) || empty($decoded['data'])) {
                $failed[] = $poem->id;
                continue;
            }

            $data = $decoded['data'];
            $seo = isset($data['seo']) && is_array($data['seo']) ? $data['seo'] : [];
            $getSeo = function (string $name) use ($seo): ?string {
                foreach ($seo as $item) {
                    if (isset($item['name']) && $item['name'] === $name && isset($item['content'])) {
                        return trim((string) $item['content']);
                    }
                }
                return null;
            };
            $metaTitle = $this->cleanSeoString($getSeo('meta_title') ?? '', 255);
            $metaDesc = $this->cleanSeoString($getSeo('meta_description') ?? '', 500);
            $h1 = $this->cleanSeoString($getSeo('h1') ?? '', 255);
            $h1Desc = $this->cleanSeoString($data['text_by_h1'] ?? '', 500);
            $analysisMarkdown = isset($data['analysis_markdown']) ? trim((string) $data['analysis_markdown']) : '';
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
            'request_full' => $lastRequestRaw,
            'response_raw' => $lastResponseContent,
            'processed_count' => $processed,
            'failed_ids' => $failed ?: null,
            'error_message' => null,
        ]);

        $message = "Обработано анализов: {$processed}." . (count($failed) > 0 ? ' Не удалось: ' . implode(', ', $failed) . '.' : '');
        return ['error' => null, 'processed' => $processed, 'failed' => $failed, 'message' => $message, 'rawResponse' => $lastResponseContent];
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
