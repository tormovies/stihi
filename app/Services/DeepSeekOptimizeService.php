<?php

namespace App\Services;

use App\Models\DeepSeekLog;
use App\Models\Poem;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class DeepSeekOptimizeService
{
    private const DEEPSEEK_URL = 'https://api.deepseek.com/chat/completions';
    private const POEM_EXCERPT_LENGTH = 100;
    private const DEFAULT_TIMEOUT = 330;
    private const DEFAULT_BATCH_SIZE = 10;

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

        $requestBody = [
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $userMessage]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
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
