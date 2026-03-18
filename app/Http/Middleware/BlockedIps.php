<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Блокирует доступ с IP из списка в настройках (Безопасность).
 * Обращения логируются в storage/logs/404-YYYY-MM-DD.log с пометкой BLOCKED.
 * Блокируется весь сайт, включая админку.
 */
class BlockedIps
{
    private const LOG_DIR = 'logs';
    private const PREFIX_404 = '404-';
    private const SETTING_KEY = 'blocked_ips';

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if ($ip === null || $ip === '') {
            return $next($request);
        }

        try {
            $list = $this->getBlockedIps();
            if ($list === [] || !in_array($ip, $list, true)) {
                return $next($request);
            }
        } catch (\Throwable $e) {
            return $next($request);
        }

        $this->logBlockedRequest($request, $ip);
        View::share('skipCounter', true);
        return response()->view('errors.403', [], 403);
    }

    private function getBlockedIps(): array
    {
        $raw = SiteSetting::get(self::SETTING_KEY, '');
        if ($raw === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $list = array_map('trim', $lines);
        return array_values(array_filter($list, fn ($s) => $s !== ''));
    }

    private function logBlockedRequest(Request $request, string $ip): void
    {
        try {
            $dir = storage_path(self::LOG_DIR);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir . DIRECTORY_SEPARATOR . self::PREFIX_404 . date('Y-m-d') . '.log';
            $ts = date('Y-m-d H:i:s');
            $path = $request->path();
            $referer = str_replace(["\r", "\n", "\t"], ' ', (string) $request->header('Referer', ''));
            $ua = str_replace(["\r", "\n", "\t"], ' ', mb_substr((string) $request->userAgent(), 0, 500));
            $line = "BLOCKED\t{$ts}\t{$path}\t{$referer}\t{$ip}\t{$ua}\n";
            @file_put_contents($file, $line, LOCK_EX | FILE_APPEND);
        } catch (\Throwable $e) {
            // не ломаем ответ при сбое логирования
        }
    }
}
