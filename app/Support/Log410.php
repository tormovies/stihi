<?php

namespace App\Support;

use Illuminate\Http\Request;

class Log410
{
    private const LOG_DIR = 'logs';
    private const LOG_PREFIX = '404-';

    public static function write(Request $request): void
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
            $ip = $request->ip() ?? '';
            $ua = str_replace(["\r", "\n", "\t"], ' ', mb_substr((string) $request->userAgent(), 0, 500));
            $line = "410\t{$ts}\t{$path}\t{$referer}\t{$ip}\t{$ua}\n";
            @file_put_contents($file, $line, LOCK_EX | FILE_APPEND);
        } catch (\Throwable $e) {
            //
        }
    }
}
