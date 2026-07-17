<?php

namespace App\Services;

use App\Models\DeepSeekLog;
use App\Models\Poem;
use App\Models\PoemSunoAnalysis;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class PoemSunoDeepSeekService
{
    private const DEEPSEEK_URL = 'https://api.deepseek.com/chat/completions';
    private const DEFAULT_TIMEOUT = 330;
    private const DEFAULT_BATCH_SIZE = 1;
    private const DEFAULT_MAX_TOKENS = 8000;
    private const LENGTH_MIN = 400;
    private const LENGTH_MAX = 2000;

    /**
     * Один тик: обработать до N стихов, каждый — отдельный request.
     *
     * @return array{error: string|null, processed: int, failed: int[], message: string|null, rawResponse: string|null}
     */
    public function runBatch(?int $forcePoemId = null): array
    {
        $apiKey = Setting::get('deepseek_api_key');
        $timeout = (int) (Setting::get('deepseek_timeout') ?? self::DEFAULT_TIMEOUT);
        $batchSize = max(1, min(20, (int) (Setting::get('suno_batch_size') ?? self::DEFAULT_BATCH_SIZE)));
        $maxTokens = (int) (Setting::get('deepseek_max_tokens') ?? self::DEFAULT_MAX_TOKENS);
        $prompt = config('deepseek.prompt_template_suno', '');

        if (!$apiKey) {
            return $this->result('Не задан API-ключ DeepSeek.');
        }
        if (trim($prompt) === '') {
            return $this->result('Не задан prompt_template_suno в config/deepseek.php.');
        }

        if ($forcePoemId) {
            $poem = Poem::with('author')->whereNotNull('published_at')->where('id', $forcePoemId)->first();
            if (!$poem) {
                return $this->result('Стих не найден или не опубликован.');
            }
            $len = (int) ($poem->body_length ?? 0);
            if ($len < self::LENGTH_MIN || $len > self::LENGTH_MAX) {
                return $this->result('Длина стиха вне диапазона ' . self::LENGTH_MIN . '–' . self::LENGTH_MAX . ' знаков.');
            }
            PoemSunoAnalysis::where('poem_id', $poem->id)->delete();
            $poems = collect([$poem]);
        } else {
            $poems = Poem::with('author')
                ->whereNotNull('published_at')
                ->whereBetween('body_length', [self::LENGTH_MIN, self::LENGTH_MAX])
                ->whereDoesntHave('sunoAnalysis')
                ->inRandomOrder()
                ->limit($batchSize)
                ->get();
        }

        if ($poems->isEmpty()) {
            return [
                'error' => null,
                'processed' => 0,
                'failed' => [],
                'message' => 'Нет стихов для Suno-анализа (400–2000 знаков, без анализа).',
                'rawResponse' => null,
            ];
        }

        $processed = 0;
        $failed = [];
        $lastRaw = null;

        foreach ($poems as $poem) {
            $one = $this->analyzeOne($poem, $apiKey, $timeout, $maxTokens, $prompt);
            $lastRaw = $one['rawResponse'];
            if ($one['ok']) {
                $processed++;
            } else {
                $failed[] = $poem->id;
            }
        }

        $message = "Suno: обработано {$processed} из " . $poems->count() . '.'
            . (count($failed) ? ' Ошибки id: ' . implode(', ', $failed) . '.' : '');

        return [
            'error' => null,
            'processed' => $processed,
            'failed' => $failed,
            'message' => $message,
            'rawResponse' => $lastRaw,
        ];
    }

    /**
     * @return array{ok: bool, rawResponse: string|null}
     */
    private function analyzeOne(Poem $poem, string $apiKey, int $timeout, int $maxTokens, string $systemPrompt): array
    {
        $bodyPlain = $this->plainBody((string) $poem->body);
        $userPayload = [
            'id' => $poem->id,
            'author' => $poem->author?->name ?? '',
            'title' => $poem->title,
            'body' => $bodyPlain,
        ];
        $userMessage = json_encode($userPayload, JSON_UNESCAPED_UNICODE);

        $requestBody = [
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
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

        if (!$response->successful()) {
            $raw = $response->body();
            DeepSeekLog::create([
                'status' => 'api_error',
                'entity_type' => 'suno',
                'request_payload' => [$userPayload],
                'request_full' => $requestBodyRaw,
                'response_raw' => $raw,
                'processed_count' => 0,
                'failed_ids' => [$poem->id],
                'error_message' => 'HTTP ' . $response->status(),
            ]);

            return ['ok' => false, 'rawResponse' => $raw];
        }

        $jsonBody = $response->json();
        $content = (string) ($jsonBody['choices'][0]['message']['content'] ?? '');
        $decoded = json_decode($this->extractJson($content), true);

        if (!is_array($decoded)) {
            DeepSeekLog::create([
                'status' => 'parse_error',
                'entity_type' => 'suno',
                'request_payload' => [$userPayload],
                'request_full' => $requestBodyRaw,
                'response_raw' => $content,
                'processed_count' => 0,
                'failed_ids' => [$poem->id],
                'error_message' => 'Невалидный JSON',
            ]);

            return ['ok' => false, 'rawResponse' => $content];
        }

        try {
            $this->saveAnalysis($poem, $decoded, $content);
        } catch (\Throwable $e) {
            DeepSeekLog::create([
                'status' => 'parse_error',
                'entity_type' => 'suno',
                'request_payload' => [$userPayload],
                'request_full' => $requestBodyRaw,
                'response_raw' => $content,
                'processed_count' => 0,
                'failed_ids' => [$poem->id],
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return ['ok' => false, 'rawResponse' => $content];
        }

        DeepSeekLog::create([
            'status' => 'success',
            'entity_type' => 'suno',
            'request_payload' => [$userPayload],
            'request_full' => $requestBodyRaw,
            'response_raw' => $content,
            'processed_count' => 1,
            'failed_ids' => null,
            'error_message' => null,
        ]);

        return ['ok' => true, 'rawResponse' => $content];
    }

    private function saveAnalysis(Poem $poem, array $data, string $raw): void
    {
        $scores = is_array($data['scores'] ?? null) ? $data['scores'] : [];
        $male = is_array($data['male'] ?? null) ? $data['male'] : [];
        $folk = is_array($data['folk'] ?? null) ? $data['folk'] : [];
        $comfort = is_array($data['comfort'] ?? null) ? $data['comfort'] : [];
        $styles = $this->normalizeStyles($data['styles'] ?? []);

        $hook = $this->clampScore($scores['hook'] ?? 0);
        $rhythm = $this->clampScore($scores['rhythm'] ?? 0);
        $dynamics = $this->clampScore($scores['dynamics'] ?? 0);
        $plot = $this->clampScore($scores['plot'] ?? 0);
        $vocal = $this->clampScore($scores['vocal_air'] ?? 0);
        $total = isset($scores['total']) ? (int) $scores['total'] : ($hook + $rhythm + $dynamics + $plot + $vocal);
        $total = max(0, min(25, $total));

        $status = $this->normalizeStatus((string) ($data['status'] ?? 'medium'));
        $marked = trim((string) ($data['marked_lyrics'] ?? ''));
        if ($marked === '') {
            throw new \RuntimeException('Пустой marked_lyrics');
        }

        PoemSunoAnalysis::updateOrCreate(
            ['poem_id' => $poem->id],
            [
                'score_hook' => $hook,
                'score_rhythm' => $rhythm,
                'score_dynamics' => $dynamics,
                'score_plot' => $plot,
                'score_vocal_air' => $vocal,
                'score_total' => $total,
                'status' => $status,
                'suitable_for_suno' => (bool) ($data['suitable_for_suno'] ?? false),
                'male_fit' => $this->clampScore($male['fit'] ?? 0),
                'male_verdict' => $this->normalizeVerdict((string) ($male['verdict'] ?? 'maybe')),
                'male_why' => $this->trimText($male['why'] ?? null, 2000),
                'folk_fit' => $this->clampScore($folk['fit'] ?? 0),
                'folk_verdict' => $this->normalizeVerdict((string) ($folk['verdict'] ?? 'maybe')),
                'folk_why' => $this->trimText($folk['why'] ?? null, 2000),
                'comfort_fit' => $this->clampScore($comfort['fit'] ?? 0),
                'comfort_verdict' => $this->normalizeVerdict((string) ($comfort['verdict'] ?? 'maybe')),
                'comfort_why' => $this->trimText($comfort['why'] ?? null, 2000),
                'marked_lyrics' => $marked,
                'styles' => $styles,
                'best_overall' => $this->trimText($data['best_overall'] ?? null, 255),
                'best_viral' => $this->trimText($data['best_viral'] ?? null, 255),
                'best_cult' => $this->trimText($data['best_cult'] ?? null, 255),
                'structure_notes' => $this->trimText($data['structure_notes'] ?? null, 5000),
                'risks' => array_values(array_filter(array_map(
                    fn ($r) => is_string($r) ? trim($r) : null,
                    is_array($data['risks'] ?? null) ? $data['risks'] : []
                ))),
                'raw_response' => $raw,
            ]
        );
    }

    private function normalizeStyles(mixed $styles): array
    {
        if (!is_array($styles)) {
            return [];
        }
        $out = [];
        foreach ($styles as $style) {
            if (!is_array($style)) {
                continue;
            }
            $prompt = trim((string) ($style['suno_prompt'] ?? ''));
            if (mb_strlen($prompt) > 400) {
                $prompt = mb_substr($prompt, 0, 400);
            }
            $type = strtolower((string) ($style['type'] ?? 'authentic'));
            if (!in_array($type, ['authentic', 'modern'], true)) {
                $type = 'authentic';
            }
            $out[] = [
                'type' => $type,
                'label' => mb_substr(trim((string) ($style['label'] ?? '')), 0, 120),
                'suno_prompt' => $prompt,
                'mass_appeal' => $this->clampScore($style['mass_appeal'] ?? 0),
                'virality' => $this->clampScore($style['virality'] ?? 0),
                'niche_cult' => $this->clampScore($style['niche_cult'] ?? 0),
                'fit_to_text' => $this->clampScore($style['fit_to_text'] ?? 0),
                'why' => mb_substr(trim((string) ($style['why'] ?? '')), 0, 500),
            ];
        }

        return $out;
    }

    private function clampScore(mixed $v): int
    {
        return max(0, min(5, (int) $v));
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $map = [
            'super' => PoemSunoAnalysis::STATUS_SUPER,
            'super-strong' => PoemSunoAnalysis::STATUS_SUPER,
            'супер' => PoemSunoAnalysis::STATUS_SUPER,
            'супер-сильный' => PoemSunoAnalysis::STATUS_SUPER,
            'strong' => PoemSunoAnalysis::STATUS_STRONG,
            'сильный' => PoemSunoAnalysis::STATUS_STRONG,
            'medium' => PoemSunoAnalysis::STATUS_MEDIUM,
            'средний' => PoemSunoAnalysis::STATUS_MEDIUM,
            'weak' => PoemSunoAnalysis::STATUS_WEAK,
            'слабый' => PoemSunoAnalysis::STATUS_WEAK,
        ];

        return $map[$status] ?? PoemSunoAnalysis::STATUS_MEDIUM;
    }

    private function normalizeVerdict(string $v): string
    {
        $v = strtolower(trim($v));
        if (in_array($v, ['yes', 'да', 'true', '1'], true)) {
            return 'yes';
        }
        if (in_array($v, ['no', 'нет', 'false', '0'], true)) {
            return 'no';
        }

        return 'maybe';
    }

    private function plainBody(string $html): string
    {
        if (function_exists('poem_body_plain_export')) {
            return poem_body_plain_export($html);
        }
        $text = preg_replace('/<br\s*\/?>/ui', "\n", $html) ?? $html;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;

        return trim($text);
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/us', $content, $m)) {
            return $m[1];
        }
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            return substr($content, $start, $end - $start + 1);
        }

        return $content;
    }

    private function trimText(mixed $v, int $max): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }

        return mb_substr($s, 0, $max);
    }

    /**
     * @return array{error: string, processed: int, failed: int[], message: null, rawResponse: null}
     */
    private function result(string $error): array
    {
        return ['error' => $error, 'processed' => 0, 'failed' => [], 'message' => null, 'rawResponse' => null];
    }

    public function pendingCount(): int
    {
        return Poem::query()
            ->whereNotNull('published_at')
            ->whereBetween('body_length', [self::LENGTH_MIN, self::LENGTH_MAX])
            ->whereDoesntHave('sunoAnalysis')
            ->count();
    }

    public function wipeAll(): int
    {
        return PoemSunoAnalysis::query()->delete();
    }
}
