@extends('admin.layouts.app')

@section('title', 'Безопасность')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Безопасность</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Безопасность</h1>
</div>

{{-- Путь к логам — три строки: каталог и путь в одной строке --}}
<div class="admin-card">
    <h2 class="admin-card-title">Расположение логов на проекте</h2>
    <p class="admin-log-path">Каталог логов: <code>{{ $logsDir ?? storage_path('logs') }}</code></p>
    <p class="admin-log-path">Файл лога ботов за сегодня: <code>{{ $botsLogPath ?? (storage_path('logs') . DIRECTORY_SEPARATOR . 'bots-' . date('Y-m-d') . '.log') }}</code></p>
    <p class="admin-log-path">Файл лога 404 за сегодня: <code>{{ $log404Path ?? (storage_path('logs') . DIRECTORY_SEPARATOR . '404-' . date('Y-m-d') . '.log') }}</code></p>
</div>

{{-- Настройки логирования --}}
<div class="admin-card">
    <h2 class="admin-card-title">Логирование</h2>
    <form method="POST" action="{{ route('admin.security.update') }}" class="admin-form">
        @csrf
        <div class="admin-form-group">
            <span class="admin-form-label">Лог запросов ботов:</span>
            <label class="checkbox-label">
                <input type="radio" name="request_log_mode" value="off" {{ ($logMode ?? '') === 'off' ? 'checked' : '' }}>
                выкл
            </label>
            <label class="checkbox-label" style="margin-left: 1rem;">
                <input type="radio" name="request_log_mode" value="bots" {{ ($logMode ?? '') === 'bots' ? 'checked' : '' }}>
                только боты (по User-Agent)
            </label>
            <label class="checkbox-label" style="margin-left: 1rem;">
                <input type="radio" name="request_log_mode" value="all" {{ ($logMode ?? '') === 'all' ? 'checked' : '' }}>
                все запросы
            </label>
        </div>
        <div class="admin-form-group">
            <span class="admin-form-label">Лог 404 (страница не найдена):</span>
            <label class="checkbox-label">
                <input type="radio" name="log_404_enabled" value="off" {{ ($log404Enabled ?? 'off') === 'off' ? 'checked' : '' }}>
                выкл
            </label>
            <label class="checkbox-label" style="margin-left: 1rem;">
                <input type="radio" name="log_404_enabled" value="on" {{ ($log404Enabled ?? '') === 'on' ? 'checked' : '' }}>
                вкл
            </label>
        </div>
        <div class="admin-form-group">
            <span class="admin-form-label">Счётчик Метрики на страницах 404:</span>
            <label class="checkbox-label">
                <input type="radio" name="counter_show_on_404" value="off" {{ ($counterShowOn404 ?? 'off') === 'off' ? 'checked' : '' }}>
                выкл
            </label>
            <label class="checkbox-label" style="margin-left: 1rem;">
                <input type="radio" name="counter_show_on_404" value="on" {{ ($counterShowOn404 ?? '') === 'on' ? 'checked' : '' }}>
                вкл
            </label>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Сохранить</button>
        </div>
    </form>
</div>

{{-- Просмотр лога ботов за сегодня --}}
<div class="admin-card">
    <h2 class="admin-card-title">Лог ботов за сегодня</h2>
    <p class="admin-card-desc">Файл: <code>bots-{{ date('Y-m-d') }}.log</code></p>
    <div class="admin-form-group">
        <span class="admin-form-label">Показать:</span>
        <a href="{{ route('admin.security.index', ['display' => 'last100', 'display_404' => $display404 ?? 'last100']) }}" class="admin-btn admin-btn-secondary {{ ($display ?? '') === 'last100' ? 'admin-btn-active' : '' }}">Последние 100 строк</a>
        <a href="{{ route('admin.security.index', ['display' => 'full', 'display_404' => $display404 ?? 'last100']) }}" class="admin-btn admin-btn-secondary {{ ($display ?? '') === 'full' ? 'admin-btn-active' : '' }}">Весь лог</a>
    </div>
    <div class="admin-log-wrap" style="width: 100%;">
        <textarea class="admin-log-view" readonly rows="16" aria-label="Лог ботов" id="admin-security-log" style="width: 100%; box-sizing: border-box;"></textarea>
    </div>
    <script type="application/json" id="admin-security-log-data">{!! json_encode($logContent ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script>
        (function() {
            var el = document.getElementById('admin-security-log');
            var data = document.getElementById('admin-security-log-data');
            if (el && data) try { var r = data.textContent; el.value = r ? JSON.parse(r) : ''; } catch (e) { el.value = r || ''; }
        })();
    </script>
</div>

{{-- Просмотр лога 404 за сегодня --}}
<div class="admin-card">
    <h2 class="admin-card-title">Лог 404 за сегодня</h2>
    <p class="admin-card-desc">Файл: <code>404-{{ date('Y-m-d') }}.log</code>. Колонки: дата/время, путь, источник (Referer), IP, User-Agent (разделитель — табуляция).</p>
    <div class="admin-form-group">
        <span class="admin-form-label">Показать:</span>
        <a href="{{ route('admin.security.index', ['display' => $display ?? 'last100', 'display_404' => 'last100']) }}" class="admin-btn admin-btn-secondary {{ ($display404 ?? '') === 'last100' ? 'admin-btn-active' : '' }}">Последние 100 строк</a>
        <a href="{{ route('admin.security.index', ['display' => $display ?? 'last100', 'display_404' => 'full']) }}" class="admin-btn admin-btn-secondary {{ ($display404 ?? '') === 'full' ? 'admin-btn-active' : '' }}">Весь лог</a>
    </div>
    <div class="admin-log-wrap" style="width: 100%;">
        <textarea class="admin-log-view" readonly rows="16" aria-label="Лог 404" id="admin-security-log-404" style="width: 100%; box-sizing: border-box;"></textarea>
    </div>
    <script type="application/json" id="admin-security-log-404-data">{!! json_encode($log404Content ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script>
        (function() {
            var el = document.getElementById('admin-security-log-404');
            var data = document.getElementById('admin-security-log-404-data');
            if (el && data) try { var r = data.textContent; el.value = r ? JSON.parse(r) : ''; } catch (e) { el.value = r || ''; }
        })();
    </script>
</div>
@endsection
