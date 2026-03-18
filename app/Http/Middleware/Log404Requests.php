<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Логирует запросы, завершившиеся 404, в storage/logs/404-YYYY-MM-DD.log.
 * Включается/выключается в админке (Безопасность). Формат строки: timestamp \t path \t referer \t ip \t user_agent
 */
class Log404Requests
{
    private const LOG_DIR = 'logs';
    private const LOG_PREFIX = '404-';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }
        if ($request->is('admin') || $request->is('admin/*')) {
            return $response;
        }
        if (app()->environment('local')) {
            return $response;
        }

        try {
            if (SiteSetting::get('log_404_enabled', 'off') !== 'on') {
                return $response;
            }
            $dir = storage_path(self::LOG_DIR);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir . DIRECTORY_SEPARATOR . self::LOG_PREFIX . date('Y-m-d') . '.log';
            $ts = date('Y-m-d H:i:s');
            $path = $request->path();
            $referer = str_replace(["\r", "\n", "\t"], ' ', (string) $request->header('Referer', ''));
            $ip = $request->ip() ?? '';
            $ua = str_replace(["\r", "\n", "\t"], ' ', mb_substr((string) $request->userAgent(), 0, 500));
            $line = $ts . "\t" . $path . "\t" . $referer . "\t" . $ip . "\t" . $ua . "\n";
            @file_put_contents($file, $line, LOCK_EX | FILE_APPEND);
        } catch (\Throwable $e) {
            // не ломаем ответ при сбое логирования
        }

        return $response;
    }
}
