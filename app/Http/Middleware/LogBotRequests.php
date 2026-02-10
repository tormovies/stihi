<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Логирует запросы в storage/logs/bots-YYYY-MM-DD.log.
 * Режим из админки (Безопасность): только боты или все запросы.
 */
class LogBotRequests
{
    /** Паттерны User-Agent, по которым считаем запрос ботом. */
    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'scanner', 'headless',
        'curl', 'wget', 'python', 'java/', 'go-http', 'php/', 'ruby',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'telegrambot',
        'yandex', 'googlebot', 'bingbot', 'duckduckbot', 'baiduspider',
        'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'petalbot', 'bytespider',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('admin') || $request->is('admin/*')) {
            return $response;
        }

        try {
            $mode = SiteSetting::get('request_log_mode', 'bots');
            $ua = $request->userAgent() ?? '';
            $shouldLog = $mode === 'all' || ($ua !== '' && $this->looksLikeBot($ua));
            if (!$shouldLog) {
                return $response;
            }
            Log::channel('bots')->info('request', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_agent' => mb_substr($ua, 0, 500),
            ]);
        } catch (\Throwable $e) {
            // не ломаем ответ при сбое логирования
        }

        return $response;
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
}
