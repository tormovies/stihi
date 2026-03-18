<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Логирует запросы, завершившиеся 404, в storage/logs/404-YYYY-MM-DD.log.
 * Для заблокированных IP: вместо 404 отдаём 403 и пишем в лог с пометкой BLOCKED.
 */
class Log404Requests
{
    private const LOG_DIR = 'logs';
    private const LOG_PREFIX = '404-';
    private const SETTING_KEY = 'blocked_ips';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }
        if ($request->is('admin') || $request->is('admin/*')) {
            return $response;
        }

        $ip = $request->ip() ?? '';
        $isBlocked = $ip !== '' && $this->isIpBlocked($ip);

        if ($isBlocked) {
            $this->writeLogLine($request, $ip, true);
            View::share('skipCounter', true);
            return response()->view('errors.403', [], 403);
        }

        if (app()->environment('local')) {
            return $response;
        }

        try {
            if (SiteSetting::get('log_404_enabled', 'off') !== 'on') {
                return $response;
            }
            $this->writeLogLine($request, $ip, false);
        } catch (\Throwable $e) {
            // не ломаем ответ при сбое логирования
        }

        return $response;
    }

    private function isIpBlocked(string $ip): bool
    {
        try {
            $raw = SiteSetting::get(self::SETTING_KEY, '');
            if ($raw === '') {
                return false;
            }
            $lines = preg_split('/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY);
            $list = array_values(array_filter(array_map('trim', $lines), fn ($s) => $s !== ''));
            return in_array($ip, $list, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function writeLogLine(Request $request, string $ip, bool $blocked): void
    {
        try {
            $dir = storage_path(self::LOG_DIR);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir . DIRECTORY_SEPARATOR . self::LOG_PREFIX . date('Y-m-d') . '.log';
            $ts = date('Y-m-d H:i:s');
            $path = $request->path();
            $referer = str_replace(["\r", "\n", "\t"], ' ', (string) $request->header('Referer', ''));
            $ua = str_replace(["\r", "\n", "\t"], ' ', mb_substr((string) $request->userAgent(), 0, 500));
            $prefix = $blocked ? 'BLOCKED\t' : '';
            $line = $prefix . $ts . "\t" . $path . "\t" . $referer . "\t" . $ip . "\t" . $ua . "\n";
            @file_put_contents($file, $line, LOCK_EX | FILE_APPEND);
        } catch (\Throwable $e) {
            //
        }
    }
}
