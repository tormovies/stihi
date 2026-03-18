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
    private const LOG_404_KEY = 'log_404_enabled';
    private const COUNTER_ON_404_KEY = 'counter_show_on_404';
    private const BLOCKED_IPS_KEY = 'blocked_ips';
    private const LOG_DIR = 'logs';
    private const BOT_PREFIX = 'bots-';
    private const PREFIX_404 = '404-';

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'request_log_mode' => 'required|in:off,bots,all',
                'log_404_enabled' => 'nullable|in:on,off',
                'counter_show_on_404' => 'nullable|in:on,off',
                'blocked_ips' => 'nullable|string|max:10000',
            ]);
            SiteSetting::set(self::LOG_MODE_KEY, $request->input('request_log_mode'));
            SiteSetting::set(self::LOG_404_KEY, $request->input('log_404_enabled', 'off') === 'on' ? 'on' : 'off');
            SiteSetting::set(self::COUNTER_ON_404_KEY, $request->input('counter_show_on_404', 'off') === 'on' ? 'on' : 'off');
            SiteSetting::set(self::BLOCKED_IPS_KEY, $request->input('blocked_ips', ''));
            return redirect()->route('admin.security.index')->with('success', 'Настройки сохранены.');
        }

        $logMode = SiteSetting::get(self::LOG_MODE_KEY, 'bots');
        $log404Enabled = SiteSetting::get(self::LOG_404_KEY, 'off');
        $counterShowOn404 = SiteSetting::get(self::COUNTER_ON_404_KEY, 'off');
        $blockedIps = SiteSetting::get(self::BLOCKED_IPS_KEY, '');
        $currentIp = $request->ip();

        $display = $request->query('display', 'last100');
        if (!in_array($display, ['last100', 'full'], true)) {
            $display = 'last100';
        }
        $display404 = $request->query('display_404', 'last100');
        if (!in_array($display404, ['last100', 'full'], true)) {
            $display404 = 'last100';
        }

        $logsDir = storage_path(self::LOG_DIR);
        $botsLogPath = $logsDir . DIRECTORY_SEPARATOR . self::BOT_PREFIX . date('Y-m-d') . '.log';
        $log404Path = $logsDir . DIRECTORY_SEPARATOR . self::PREFIX_404 . date('Y-m-d') . '.log';

        $logContent = $this->readTodayLog(self::BOT_PREFIX, $display);
        $log404Content = $this->readTodayLog(self::PREFIX_404, $display404);

        return view('admin.security.index', [
            'logMode' => $logMode,
            'log404Enabled' => $log404Enabled,
            'counterShowOn404' => $counterShowOn404,
            'blockedIps' => $blockedIps,
            'currentIp' => $currentIp,
            'display' => $display,
            'display404' => $display404,
            'logContent' => $logContent,
            'log404Content' => $log404Content,
            'logsDir' => $logsDir,
            'botsLogPath' => $botsLogPath,
            'log404Path' => $log404Path,
        ]);
    }

    private function readTodayLog(string $filePrefix, string $display): string
    {
        $dir = storage_path(self::LOG_DIR);
        $filename = $filePrefix . date('Y-m-d') . '.log';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($path) || !is_readable($path)) {
            return 'Нет записей за сегодня.';
        }

        $realPath = realpath($path);
        $allowedDir = realpath($dir);
        if ($realPath === false || $allowedDir === false || !str_starts_with($realPath, $allowedDir . DIRECTORY_SEPARATOR)) {
            return 'Ошибка доступа к логу.';
        }
        if (basename($realPath) !== $filename || !str_starts_with(basename($realPath), $filePrefix)) {
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
