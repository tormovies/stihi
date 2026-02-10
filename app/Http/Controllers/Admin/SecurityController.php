<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    private const LOG_MODE_KEY = 'request_log_mode';
    private const LOG_FILE_PREFIX = 'bots-';
    private const LOG_DIR = 'logs';

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'request_log_mode' => 'required|in:bots,all',
            ]);
            SiteSetting::set(self::LOG_MODE_KEY, $request->input('request_log_mode'));
            return redirect()->route('admin.security.index')->with('success', 'Настройки сохранены.');
        }

        $logMode = SiteSetting::get(self::LOG_MODE_KEY, 'bots');
        $display = $request->query('display', 'last100');
        if (!in_array($display, ['last100', 'full'], true)) {
            $display = 'last100';
        }

        $logContent = $this->readTodayLog($display);

        return view('admin.security.index', [
            'logMode' => $logMode,
            'display' => $display,
            'logContent' => $logContent,
        ]);
    }

    private function readTodayLog(string $display): string
    {
        $dir = storage_path(self::LOG_DIR);
        $filename = self::LOG_FILE_PREFIX . date('Y-m-d') . '.log';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path) || !is_readable($path)) {
            return 'Нет записей за сегодня.';
        }

        $realPath = realpath($path);
        $allowedDir = realpath($dir);
        if ($realPath === false || $allowedDir === false || !str_starts_with($realPath, $allowedDir . DIRECTORY_SEPARATOR)) {
            return 'Ошибка доступа к логу.';
        }
        if (basename($realPath) !== $filename || !str_starts_with(basename($realPath), self::LOG_FILE_PREFIX)) {
            return 'Ошибка доступа к логу.';
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return 'Не удалось прочитать лог.';
        }

        if ($display === 'last100') {
            $lines = explode("\n", $content);
            $lines = array_slice($lines, -100);
            $content = implode("\n", $lines);
        }

        return $content !== '' ? $content : 'Нет записей за сегодня.';
    }
}
